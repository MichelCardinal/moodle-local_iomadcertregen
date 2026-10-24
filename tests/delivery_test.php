<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * IOMAD Certificate Regenerator.
 *
 * @package    local_iomadcertregen
 * @copyright  2026 Michel Cardinal
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcertregen;

use local_iomadcertregen\local\{delivery, mail_transport, records, service};
use local_iomadcertregen\privacy\provider;
use core_privacy\local\request\{approved_contextlist, writer};

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/delivery_testcase.php');

/**
 * Explicit PDF mail delivery, transport failures, duplicate protection and privacy.
 *
 * @covers \local_iomadcertregen\local\delivery
 * @covers \local_iomadcertregen\local\mail_transport
 */
#[\PHPUnit\Framework\Attributes\CoversClass(delivery::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(mail_transport::class)]
final class delivery_test extends delivery_testcase {
    /**
     * The actual Moodle MIME message attaches the exact reviewed PDF once to its beneficiary.
     */
    public function test_moodle_mail_contains_exact_pdf_and_prevents_duplicates(): void {
        global $DB;
        [$data, $operation] = $this->confirmed();
        $sink = $this->redirectEmails();
        $events = $this->redirectEvents();
        $before = $DB->get_record('iomadcertificate_issues', ['id' => $operation->issueid]);
        $bytes = service::file($operation)->get_content();
        $key = str_repeat('b7', 16);
        $sent = delivery::send($operation->cmid, $operation->id, $key, delivery::recipient_hash($data[1]), sesskey());
        $this->assertSame('submitted', $sent->mailstatus);
        $this->assertGreaterThan(0, $sent->mailtime);
        delivery::send($operation->cmid, $operation->id, $key, delivery::recipient_hash($data[1]), sesskey());
        delivery::send($operation->cmid, $operation->id, str_repeat('c7', 16), delivery::recipient_hash($data[1]), sesskey());
        $this->assertCount(1, $sink->get_messages());
        $mail = $sink->get_messages()[0];
        $this->assertSame('learner@example.com', $mail->to);
        $this->assertStringContainsString('corrected-certificate.pdf', $mail->body);
        $this->assertStringContainsString(rtrim(chunk_split(base64_encode($bytes), 76, "\r\n")), $mail->body);
        $this->assertSame($bytes, service::file($operation)->get_content());
        $this->assertEquals($before, $DB->get_record('iomadcertificate_issues', ['id' => $operation->issueid]));
        $attempts = array_filter($events->get_events(), static function ($event): bool {
            return $event instanceof \local_iomadcertregen\event\copy_email_attempted;
        });
        $this->assertCount(1, $attempts);
        $this->assertSame('submitted', reset($attempts)->other['status']);
    }

    /**
     * Confirmation alone never submits email, including when native activity delivery is email.
     */
    public function test_confirmation_does_not_automatically_email(): void {
        global $DB;
        [$data, $operation] = $this->confirmed();
        $sink = $this->redirectEmails();
        $DB->set_field('iomadcertificate', 'delivery', 2, ['id' => $data[0]->id]);
        service::confirm($operation->cmid, $operation->id, sesskey());
        $this->assertSame(0, $sink->count());
        $this->assertSame('notrequested', records::operation($operation->cmid, $operation->id)->mailstatus);
    }

    /**
     * A definite failure can be retried only by a new explicit request; replay is harmless.
     */
    public function test_failure_and_explicit_retry(): void {
        [$data, $operation] = $this->confirmed();
        $transport = $this->createMock(mail_transport::class);
        $transport->expects($this->once())->method('send')->willReturn(false);
        $hash = delivery::recipient_hash($data[1]);
        $key = str_repeat('d7', 16);
        $result = delivery::send($operation->cmid, $operation->id, $key, $hash, sesskey(), $transport);
        $this->assertSame('failed', $result->mailstatus);
        $result = delivery::send($operation->cmid, $operation->id, $key, $hash, sesskey(), $transport);
        $this->assertSame('failed', $result->mailstatus);
        $retry = $this->createMock(mail_transport::class);
        $retry->expects($this->once())->method('send')->willReturn(true);
        $result = delivery::send($operation->cmid, $operation->id, str_repeat('e7', 16), $hash, sesskey(), $retry);
        $this->assertSame('submitted', $result->mailstatus);
    }

    /**
     * A transport exception is uncertain and cannot cause an automatic duplicate.
     */
    public function test_uncertain_transport_is_not_retried(): void {
        [$data, $operation] = $this->confirmed();
        $transport = $this->createMock(mail_transport::class);
        $transport->expects($this->once())->method('send')->willThrowException(new \RuntimeException('Fictional interruption'));
        $hash = delivery::recipient_hash($data[1]);
        $result = delivery::send($operation->cmid, $operation->id, str_repeat('f7', 16), $hash, sesskey(), $transport);
        $this->assertSame('unknown', $result->mailstatus);
        $result = delivery::send($operation->cmid, $operation->id, str_repeat('a8', 16), $hash, sesskey(), $transport);
        $this->assertSame('unknown', $result->mailstatus);
    }

    /**
     * Privacy exports include bounded attempt history but exclude the private anti-duplicate token.
     */
    public function test_email_metadata_privacy_export_and_deletion(): void {
        global $DB;
        [$data, $operation] = $this->confirmed();
        $transport = $this->createMock(mail_transport::class);
        $transport->method('send')->willReturn(true);
        delivery::send(
            $operation->cmid,
            $operation->id,
            str_repeat('b9', 16),
            delivery::recipient_hash($data[1]),
            sesskey(),
            $transport
        );
        $approved = new approved_contextlist($data[1], 'local_iomadcertregen', [$data[5]->id]);
        provider::export_user_data($approved);
        $path = [get_string('pluginname', 'local_iomadcertregen'), (string)$operation->id];
        $export = writer::with_context($data[5])->get_data($path);
        $this->assertSame('submitted', $export->mailstatus);
        $this->assertFalse(property_exists($export, 'mailrequestkey'));
        provider::delete_data_for_user($approved);
        $this->assertFalse($DB->record_exists('local_iomadcertregen_op', ['id' => $operation->id]));
    }
    /**
     * A failed attachment copy never degrades into a message without its corrected PDF.
     */
    public function test_attachment_copy_failure_does_not_send(): void {
        [$data] = $this->confirmed();
        $sink = $this->redirectEmails();
        $file = $this->createMock(\stored_file::class);
        $file->expects($this->once())->method('copy_content_to')->willReturn(false);
        $this->assertFalse((new mail_transport())->send($data[1], $data[3], $file));
        $this->assertSame(0, $sink->count());
    }
}
