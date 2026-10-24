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

require_once(__DIR__ . '/fixtures/publication_testcase.php');

/**
 * Exercise real Moodle archive export and restore with native issue mappings.
 *
 * @covers \backup_local_iomadcertregen_plugin
 * @covers \restore_local_iomadcertregen_plugin
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\backup_local_iomadcertregen_plugin::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_local_iomadcertregen_plugin::class)]
final class backup_restore_test extends publication_testcase {
    /** @var string Exported file metadata, retained after Moodle cleans the temporary archive. */
    private string $filesxml = '';

    /** @var array Exported certificate-module metadata. */
    private array $modulesxml = [];

    /**
     * Back up and restore into a new course with the requested native settings.
     *
     * @param \stdClass $course Source course.
     * @param array $options Backup user-data and restored start-date options.
     * @return \stdClass Restored course.
     */
    private function round_trip(\stdClass $course, array $options = []): \stdClass {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $CFG->backup_file_logger_level = \backup::LOG_NONE;
        $backup = new \backup_controller(
            $options['type'] ?? \backup::TYPE_1COURSE,
            $options['itemid'] ?? $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $options['ownerid'] ?? $USER->id
        );
        $backup->get_plan()->get_setting('users')->set_value($options['users'] ?? true);
        $backup->execute_plan();
        $results = $backup->get_results();
        $restoreid = 'icr' . bin2hex(random_bytes(12));
        $destination = make_backup_temp_directory($restoreid);
        $extracted = $results['backup_destination']->extract_to_pathname(
            get_file_packer('application/vnd.moodle.backup'),
            $destination
        );
        $this->assertIsArray($extracted);
        $this->assertNotContains(false, $extracted);
        $this->filesxml = file_get_contents($destination . '/files.xml');
        $this->modulesxml = array_map('file_get_contents', glob($destination . '/activities/iomadcertificate_*/module.xml'));
        if (isset($options['amendarchive'])) {
            $options['amendarchive']($destination);
        }
        $backup->destroy();
        $restored = $this->getDataGenerator()->create_course(['startdate' => $course->startdate]);
        $restoreowner = $options['restorerid'] ?? $USER->id;
        if ($restoreowner !== $USER->id) {
            global $DB;
            $this->getDataGenerator()->enrol_user($restoreowner, $restored->id, 'editingteacher');
            $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
            assign_capability('moodle/restore:userinfo', CAP_ALLOW, $roleid, \context_course::instance($restored->id));
        }
        $restore = new \restore_controller(
            $restoreid,
            $restored->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $restoreowner,
            $options['target'] ?? \backup::TARGET_NEW_COURSE
        );
        if (isset($options['startdate'])) {
            $restore->get_plan()->get_setting('course_startdate')->set_value($options['startdate']);
        }
        $this->assertTrue($restore->execute_precheck());
        $restore->execute_plan();
        $restore->destroy();
        return $restored;
    }

    /**
     * Only confirmed, unexpired copies round-trip with new module and issue IDs.
     */
    public function test_confirmed_copy_round_trip_preserves_reviewed_bytes(): void {
        global $DB;
        [, $learner, $cm, $course, $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('c2', 16), sesskey());
        $confirmed = service::confirm($cm->id, $operation->id, sesskey());
        $bytes = service::file($confirmed)->get_content();
        $native = get_file_storage()->get_area_files(
            \context_module::instance($cm->id)->id,
            'mod_iomadcertificate',
            'issue',
            $issue->id,
            'id',
            false
        );
        $originalbytes = reset($native)->get_content();
        $data = records::issue($cm->id, $issue->id);
        $this->publish($data, $confirmed);
        $DB->update_record('local_iomadcertregen_op', (object)[
            'id' => $confirmed->id, 'mailstatus' => 'submitted', 'mailtime' => 1705320000,
            'mailrequestkey' => str_repeat('7', 64),
        ]);
        service::prepare($cm->id, $issue->id, str_repeat('d2', 16), sesskey());
        $expired = service::prepare($cm->id, $issue->id, str_repeat('e2', 16), sesskey());
        service::confirm($cm->id, $expired->id, sesskey());
        $DB->set_field('local_iomadcertregen_op', 'timeexpires', time() - 1, ['id' => $expired->id]);
        $restored = $this->round_trip($course);
        $activity = $DB->get_record('iomadcertificate', ['course' => $restored->id], '*', MUST_EXIST);
        $newcm = get_coursemodule_from_instance('iomadcertificate', $activity->id);
        $copies = $DB->get_records('local_iomadcertregen_op', ['cmid' => $newcm->id]);
        $this->assertCount(1, $copies);
        $copy = reset($copies);
        $this->assertSame('submitted', $copy->mailstatus);
        $this->assertSame(1705320000, (int)$copy->mailtime);
        $this->assertSame('', $copy->mailrequestkey);
        $this->assertSame(0, (int)$copy->publishedtime);
        $this->assertSame('', $copy->issuehash);
        $this->assertSame('', $copy->batchkey);
        $archives = get_file_storage()->get_area_files(
            $copy->contextid,
            'local_iomadcertregen',
            'original',
            $copy->id,
            'id',
            false
        );
        $this->assertCount(1, $archives);
        $this->assertSame($originalbytes, reset($archives)->get_content());
        $this->assertNotEquals($confirmed->id, $copy->id);
        $this->assertNotEquals($issue->id, $copy->issueid);
        $this->assertEquals($learner->id, $copy->userid);
        $this->assertEquals($confirmed->actorid, $copy->actorid);
        $this->assertSame($bytes, service::file(records::operation($newcm->id, $copy->id))->get_content());
        $this->assertSame((int)$confirmed->timecreated, (int)$copy->timecreated);
        $this->assertSame((int)$confirmed->timeexpires, (int)$copy->timeexpires);
        $this->assertNotSame($confirmed->requestkey, $copy->requestkey);
        $this->assertSame($bytes, service::file($confirmed)->get_content());
        $this->assertEmpty(get_file_storage()->get_area_files(
            \context_module::instance($newcm->id)->id,
            'mod_iomadcertificate',
            'issue',
            $copy->issueid,
            'id',
            false
        ));
        try {
            service::prepare($newcm->id, $copy->issueid, str_repeat('e4', 16), sesskey());
            $this->fail('The qualified native restore omits its saved original: regeneration must be refused.');
        } catch (\moodle_exception $error) {
            $this->assertSame('nooriginal', $error->errorcode);
        }
    }

    /**
     * A course template without user data contains no administrative copies.
     */
    public function test_backup_without_user_data_excludes_copies(): void {
        global $DB;
        [, , $cm, $course, $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('f2', 16), sesskey());
        service::confirm($cm->id, $operation->id, sesskey());
        $restored = $this->round_trip($course, ['users' => false]);
        $activity = $DB->get_record('iomadcertificate', ['course' => $restored->id], '*', MUST_EXIST);
        $newcm = get_coursemodule_from_instance('iomadcertificate', $activity->id);
        $this->assertSame(0, $DB->count_records('local_iomadcertregen_op', ['cmid' => $newcm->id]));
        $this->assertSame(0, $DB->count_records('iomadcertificate_issues', ['iomadcertificateid' => $activity->id]));
        $this->assertStringNotContainsString('local_iomadcertregen', $this->filesxml);
    }

    /**
     * Offsetting native award dates cannot silently relabel an historical PDF.
     */
    public function test_changed_native_issue_date_omits_historical_copy(): void {
        global $DB;
        [, , $cm, $course, $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('a3', 16), sesskey());
        service::confirm($cm->id, $operation->id, sesskey());
        $restored = $this->round_trip($course, ['startdate' => $course->startdate + 7 * DAYSECS]);
        $activity = $DB->get_record('iomadcertificate', ['course' => $restored->id], '*', MUST_EXIST);
        $newcm = get_coursemodule_from_instance('iomadcertificate', $activity->id);
        $this->assertSame(0, $DB->count_records('local_iomadcertregen_op', ['cmid' => $newcm->id]));
        $newissue = $DB->get_record('iomadcertificate_issues', ['iomadcertificateid' => $activity->id], '*', MUST_EXIST);
        $this->assertNotEquals($issue->timecreated, $newissue->timecreated);
        $this->assertNotFalse(service::file($operation));
    }

    /**
     * Backup ownership, rather than the current worker's session, controls export.
     */
    public function test_nonadmin_backup_owner_cannot_export_administrative_copies(): void {
        global $DB;
        [, , $cm, $course, $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('b3', 16), sesskey());
        service::confirm($cm->id, $operation->id, sesskey());
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        $coursecontext = \context_course::instance($course->id);
        assign_capability('moodle/backup:userinfo', CAP_ALLOW, $roleid, $coursecontext);
        $this->assertTrue(has_capability('moodle/backup:userinfo', $coursecontext, $teacher->id));
        $restored = $this->round_trip($course, ['ownerid' => $teacher->id]);
        $activity = $DB->get_record('iomadcertificate', ['course' => $restored->id], '*', MUST_EXIST);
        $newcm = get_coursemodule_from_instance('iomadcertificate', $activity->id);
        $this->assertSame(0, $DB->count_records('local_iomadcertregen_op', ['cmid' => $newcm->id]));
        $this->assertStringNotContainsString('local_iomadcertregen', $this->filesxml);
        $this->assertCount(1, $this->modulesxml);
        $this->assertStringNotContainsString('plugin_local_iomadcertregen', reset($this->modulesxml));
        $this->assertSame(1, $DB->count_records('iomadcertificate_issues', ['iomadcertificateid' => $activity->id]));
    }

    /**
     * A malformed copy cannot be restored by guessing old user IDs or trusting its hash.
     *
     * @dataProvider malformed_copy_provider
     * @param string $element Metadata field to alter inside the archive.
     * @param string $value Invalid replacement value.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('malformed_copy_provider')]
    public function test_invalid_backup_copy_is_omitted(string $element, string $value): void {
        global $DB;
        [, , $cm, $course, $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('c3', 16), sesskey());
        service::confirm($cm->id, $operation->id, sesskey());
        $restored = $this->round_trip($course, ['amendarchive' => function (string $directory) use ($element, $value): void {
            $files = glob($directory . '/activities/iomadcertificate_*/module.xml');
            $this->assertCount(1, $files);
            $file = reset($files);
            $xml = file_get_contents($file);
            $xml = preg_replace(
                '/<' . $element . '>[^<]+<\/' . $element . '>/',
                '<' . $element . '>' . $value . '</' . $element . '>',
                $xml
            );
            file_put_contents($file, $xml);
        }]);
        $activity = $DB->get_record('iomadcertificate', ['course' => $restored->id], '*', MUST_EXIST);
        $newcm = get_coursemodule_from_instance('iomadcertificate', $activity->id);
        $context = \context_module::instance($newcm->id);
        $this->assertSame(0, $DB->count_records('local_iomadcertregen_op', ['cmid' => $newcm->id]));
        $files = get_file_storage()->get_area_files($context->id, 'local_iomadcertregen', 'document', false, 'id', false);
        $this->assertSame([], $files);
        $this->assertSame(1, $DB->count_records('iomadcertificate_issues', ['iomadcertificateid' => $activity->id]));
        $this->assertNotFalse(service::file($operation));
    }

    /**
     * A teacher's restore cannot import administrative copies from an admin archive.
     */
    public function test_nonadmin_restore_owner_cannot_import_administrative_copies(): void {
        global $DB;
        [, , $cm, $course, $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('d3', 16), sesskey());
        service::confirm($cm->id, $operation->id, sesskey());
        $teacher = $this->getDataGenerator()->create_user();
        $restored = $this->round_trip($course, ['restorerid' => $teacher->id]);
        $this->assertStringContainsString('local_iomadcertregen', $this->filesxml);
        $activity = $DB->get_record('iomadcertificate', ['course' => $restored->id], '*', MUST_EXIST);
        $newcm = get_coursemodule_from_instance('iomadcertificate', $activity->id);
        $this->assertSame(1, $DB->count_records('iomadcertificate_issues', ['iomadcertificateid' => $activity->id]));
        $this->assertSame(0, $DB->count_records('local_iomadcertregen_op', ['cmid' => $newcm->id]));
        $context = \context_module::instance($newcm->id);
        $files = get_file_storage()->get_area_files($context->id, 'local_iomadcertregen', 'document', false, 'id', false);
        $this->assertSame([], $files);
    }

    /**
     * The backup MIME metadata cannot make a restored copy serve HTML.
     */
    public function test_restored_copy_cannot_use_html_mime_metadata(): void {
        global $DB;
        [, , $cm, $course, $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('e3', 16), sesskey());
        service::confirm($cm->id, $operation->id, sesskey());
        $restored = $this->round_trip($course, ['amendarchive' => function (string $directory): void {
            $document = new \DOMDocument();
            $document->loadXML(file_get_contents($directory . '/files.xml'), LIBXML_NONET);
            $xpath = new \DOMXPath($document);
            $nodes = $xpath->query('//file[component="local_iomadcertregen" and filename="corrected-certificate.pdf"]/mimetype');
            $this->assertCount(1, $nodes);
            $nodes->item(0)->nodeValue = 'text/html';
            file_put_contents($directory . '/files.xml', $document->saveXML());
        }]);
        $activity = $DB->get_record('iomadcertificate', ['course' => $restored->id], '*', MUST_EXIST);
        $newcm = get_coursemodule_from_instance('iomadcertificate', $activity->id);
        $copy = $DB->get_record('local_iomadcertregen_op', ['cmid' => $newcm->id], '*', MUST_EXIST);
        $file = service::file(records::operation($newcm->id, $copy->id));
        $this->assertSame('application/pdf', $file->get_mimetype());
        $this->assertSame(service::file($operation)->get_content(), $file->get_content());
    }

    /**
     * An activity-only archive uses the same safe issue and file remapping.
     */
    public function test_activity_backup_round_trip_keeps_confirmed_copy(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        [, , $cm, $course, $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('f4', 16), sesskey());
        service::confirm($cm->id, $operation->id, sesskey());
        $restored = $this->round_trip($course, [
            'type' => \backup::TYPE_1ACTIVITY, 'itemid' => $cm->id, 'target' => \backup::TARGET_CURRENT_ADDING,
        ]);
        $activity = $DB->get_record('iomadcertificate', ['course' => $restored->id], '*', MUST_EXIST);
        $newcm = get_coursemodule_from_instance('iomadcertificate', $activity->id);
        $copy = $DB->get_record('local_iomadcertregen_op', ['cmid' => $newcm->id], '*', MUST_EXIST);
        $this->assertSame('confirmed', $copy->status);
        $this->assertSame(service::file($operation)->get_content(), service::file($copy)->get_content());
    }

    /**
     * Provide invalid metadata without changing the native issue in the archive.
     *
     * @return array Named malformed-copy scenarios.
     */
    public static function malformed_copy_provider(): array {
        return [
            'unmapped actor' => ['actorid', '999999999'],
            'mismatched PDF hash' => ['pdfhash', str_repeat('0', 64)],
            'negative expiry' => ['timeexpires', '-1'],
            'missing issue date' => ['issuedate', ''],
        ];
    }
}
