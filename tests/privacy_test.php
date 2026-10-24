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

use local_iomadcertregen\local\service;
use local_iomadcertregen\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/certificate_testcase.php');

/**
 * Integration tests for privacy behaviour.
 *
 * @covers \local_iomadcertregen\privacy\provider
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\privacy\provider::class)]
final class privacy_test extends certificate_testcase {
    /**
     * Privacy discovers, exports and erases beneficiary data and its PDF.
     */
    public function test_privacy_beneficiary_export_and_deletion(): void {
        global $DB, $USER;
        [, $learner, $cm, , $issue, $context] = $this->fixture();
        $actor = clone $USER;
        $operation = service::prepare($cm->id, $issue->id, str_repeat('3', 32), sesskey());
        $this->assertSame(
            [(int)$context->id],
            array_map('intval', provider::get_contexts_for_userid($learner->id)->get_contextids())
        );
        $this->assertSame(
            [(int)$context->id],
            array_map('intval', provider::get_contexts_for_userid($actor->id)->get_contextids())
        );
        $users = new userlist($context, 'local_iomadcertregen');
        provider::get_users_in_context($users);
        $this->assertEqualsCanonicalizing([$actor->id, $learner->id], $users->get_userids());
        $DB->set_field('local_iomadcertregen_op', 'correctionreason', 'Template correction audit', ['id' => $operation->id]);
        $DB->set_field('local_iomadcertregen_op', 'keeporiginal', 0, ['id' => $operation->id]);
        $approved = new approved_contextlist($learner, 'local_iomadcertregen', [$context->id]);
        provider::export_user_data($approved);
        $this->assertTrue(writer::with_context($context)->has_any_data());
        $path = [get_string('pluginname', 'local_iomadcertregen'), (string)$operation->id];
        $metadata = writer::with_context($context)->get_data($path);
        $this->assertSame('Template correction audit', $metadata->correctionreason);
        $this->assertSame(0, (int)$metadata->keeporiginal);
        $exportedfiles = writer::with_context($context)->get_files($path);
        $this->assertCount(1, $exportedfiles);
        $this->assertSame(service::file($operation)->get_content(), reset($exportedfiles)->get_content());
        provider::delete_data_for_user($approved);
        $this->assertFalse($DB->record_exists('local_iomadcertregen_op', ['id' => $operation->id]));
        $this->assertFalse(service::file($operation));
        $this->assertTrue($DB->record_exists('iomadcertificate_issues', ['id' => $issue->id]));
    }

    /**
     * Privacy deletion for an actor removes shared copies, without native deletion.
     */
    public function test_privacy_actor_bulk_and_context_deletion(): void {
        global $DB, $USER;
        [, , $cm, , $issue, $context] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('4', 32), sesskey());
        $approved = new approved_userlist($context, 'local_iomadcertregen', [$USER->id]);
        provider::delete_data_for_users($approved);
        $this->assertFalse(service::file($operation));
        $operation = service::prepare($cm->id, $issue->id, str_repeat('5', 32), sesskey());
        provider::delete_data_for_all_users_in_context($context);
        $this->assertEquals(0, $DB->count_records('local_iomadcertregen_op'));
        $this->assertFalse(service::file($operation));
        $this->assertTrue($DB->record_exists('iomadcertificate_issues', ['id' => $issue->id]));
    }

    /**
     * An actor receives action metadata without another learner's personal PDF.
     */
    public function test_actor_export_excludes_other_beneficiary_pdf(): void {
        global $USER;
        [, , $cm, , $issue, $context] = $this->fixture();
        $actor = clone $USER;
        $operation = service::prepare($cm->id, $issue->id, str_repeat('a5', 16), sesskey());
        $approved = new approved_contextlist($actor, 'local_iomadcertregen', [$context->id]);
        provider::export_user_data($approved);
        $path = [get_string('pluginname', 'local_iomadcertregen'), (string)$operation->id];
        $export = writer::with_context($context)->get_data($path);
        $this->assertSame('prepared', $export->status);
        $this->assertFalse(property_exists($export, 'requestkey'));
        $this->assertCount(0, writer::with_context($context)->get_files($path));
        $this->assertNotFalse(service::file($operation));
    }
}
