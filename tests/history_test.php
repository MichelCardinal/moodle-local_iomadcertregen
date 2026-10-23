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

use local_iomadcertregen\local\records;
use local_iomadcertregen\local\service;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/certificate_testcase.php');

/**
 * Historical records remain independent of enrolment and reviewed copies stay immutable.
 *
 * @covers \local_iomadcertregen\local\service
 * @covers \local_iomadcertregen\local\records
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\local\service::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\local\records::class)]
final class history_test extends certificate_testcase {
    /**
     * A beneficiary identity change requires another review before confirmation.
     */
    public function test_changed_beneficiary_requires_new_review(): void {
        global $DB;
        [, $learner, $cm, , $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('13', 16), sesskey());
        $DB->set_field('user', 'lastname', 'Updated example', ['id' => $learner->id]);
        try {
            service::confirm($cm->id, $operation->id, sesskey());
            $this->fail('A changed beneficiary must require a new review.');
        } catch (\moodle_exception $error) {
            $this->assertSame('sourcechanged', $error->errorcode);
        }
        $this->assertSame('prepared', records::operation($cm->id, $operation->id)->status);
    }

    /**
     * Language and timezone changes are included in the source fingerprint.
     */
    public function test_beneficiary_locale_change_requires_new_review(): void {
        global $DB;
        [, $learner, $cm, , $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('14', 16), sesskey());
        $DB->update_record('user', (object)[
            'id' => $learner->id, 'lang' => 'es', 'timezone' => 'Pacific/Auckland',
        ]);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('sourcechanged', 'local_iomadcertregen'));
        service::confirm($cm->id, $operation->id, sesskey());
    }

    /**
     * Confirmation never regenerates a PDF when the administrator changes locale.
     */
    public function test_actor_locale_change_preserves_reviewed_bytes(): void {
        global $USER;
        [, , $cm, , $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('15', 16), sesskey());
        $bytes = service::file($operation)->get_content();
        $oldtimezone = $USER->timezone;
        $USER->timezone = 'Pacific/Auckland';
        $oldlanguage = force_current_language('en');
        try {
            $confirmed = service::confirm($cm->id, $operation->id, sesskey());
            $this->assertSame('confirmed', $confirmed->status);
            $this->assertSame($bytes, service::file($confirmed)->get_content());
        } finally {
            $USER->timezone = $oldtimezone;
            force_current_language($oldlanguage);
        }
    }

    /**
     * Enrolment removal and reinstatement do not reissue historical certificates.
     */
    public function test_unenrolment_and_reenrolment_preserve_history(): void {
        global $DB;
        [, $learner, $cm, $course, $issue] = $this->fixture();
        $generator = $this->getDataGenerator();
        $generator->enrol_user($learner->id, $course->id);
        $enrolment = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $before = $DB->get_record('iomadcertificate_issues', ['id' => $issue->id]);
        enrol_get_plugin('manual')->unenrol_user($enrolment, $learner->id);
        $operation = service::prepare($cm->id, $issue->id, str_repeat('16', 16), sesskey());
        $this->assertSame(0, $DB->count_records('user_enrolments', ['userid' => $learner->id]));
        $generator->enrol_user($learner->id, $course->id);
        $confirmed = service::confirm($cm->id, $operation->id, sesskey());
        $this->assertSame('confirmed', $confirmed->status);
        $this->assertEquals($before, $DB->get_record('iomadcertificate_issues', ['id' => $issue->id]));
        $this->assertSame(1, $DB->count_records('iomadcertificate_issues'));
        $this->assertSame(1, $DB->count_records('user_enrolments', ['userid' => $learner->id]));
    }

    /**
     * A lost prepared file cannot become a confirmed copy; a fresh request can recover.
     */
    public function test_missing_prepared_file_requires_fresh_preparation(): void {
        global $DB;
        [, , $cm, , $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('17', 16), sesskey());
        service::file($operation)->delete();
        try {
            service::confirm($cm->id, $operation->id, sesskey());
            $this->fail('A missing prepared PDF cannot be confirmed.');
        } catch (\moodle_exception $error) {
            $this->assertSame('unavailable', $error->errorcode);
        }
        $this->assertSame('prepared', records::operation($cm->id, $operation->id)->status);
        $fresh = service::prepare($cm->id, $issue->id, str_repeat('18', 16), sesskey());
        $this->assertNotEquals($operation->id, $fresh->id);
        $this->assertSame('confirmed', service::confirm($cm->id, $fresh->id, sesskey())->status);
        $this->assertSame(1, $DB->count_records('iomadcertificate_issues'));
    }
}
