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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/certificate_testcase.php');

/**
 * Integration tests for lifecycle behaviour.
 *
 * @covers \local_iomadcertregen\observer
 * @covers \local_iomadcertregen\task\cleanup
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\observer::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\task\cleanup::class)]
final class lifecycle_test extends certificate_testcase {
    /**
     * The cleanup task removes expired preparations, preserving native evidence.
     */
    public function test_cleanup_only_removes_expired_owned_files(): void {
        global $DB;
        [, , $cm, , $issue, $context] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('2', 32), sesskey());
        $DB->set_field('local_iomadcertregen_op', 'timeexpires', time() - 1, ['id' => $operation->id]);
        (new \local_iomadcertregen\task\cleanup())->execute();
        $this->assertFalse($DB->record_exists('local_iomadcertregen_op', ['id' => $operation->id]));
        $this->assertFalse(service::file($operation));
        $this->assertNotFalse(get_file_storage()->get_file(
            $context->id,
            'mod_iomadcertificate',
            'issue',
            $issue->id,
            '/',
            'original.pdf'
        ));
    }

    /**
     * Deleting an activity cleans its owned copies and operation metadata.
     */
    public function test_module_deletion_cleans_owned_data(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        [, , $cm, , $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('a1', 16), sesskey());
        if ((int)$CFG->branch >= 502) {
            \core_courseformat\formatactions::cm($cm->course)->delete($cm->id);
        } else {
            course_delete_module($cm->id);
        }
        $this->assertFalse($DB->record_exists('local_iomadcertregen_op', ['id' => $operation->id]));
        $this->assertFalse(service::file($operation));
    }

    /**
     * Reset cleanup removes a copy only if the native issue has been removed.
     */
    public function test_course_reset_retains_existing_issues_only(): void {
        global $DB;
        [, , $cm, $course, $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('b1', 16), sesskey());
        $event = \core\event\course_reset_ended::create([
            'context' => \context_course::instance($course->id), 'courseid' => $course->id,
            'other' => ['reset_options' => []],
        ]);
        \local_iomadcertregen\observer::course_reset($event);
        $this->assertTrue($DB->record_exists('local_iomadcertregen_op', ['id' => $operation->id]));
        $DB->delete_records('iomadcertificate_issues', ['id' => $issue->id]);
        \local_iomadcertregen\observer::course_reset($event);
        $this->assertFalse($DB->record_exists('local_iomadcertregen_op', ['id' => $operation->id]));
        $this->assertFalse(service::file($operation));
    }

    /**
     * Deleting a beneficiary removes their owned PDFs.
     */
    public function test_deleted_user_cleans_owned_data(): void {
        global $DB;
        [, $learner, $cm, , $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('c1', 16), sesskey());
        delete_user($learner);
        $this->assertFalse($DB->record_exists('local_iomadcertregen_op', ['id' => $operation->id]));
        $this->assertFalse(service::file($operation));
    }
}
