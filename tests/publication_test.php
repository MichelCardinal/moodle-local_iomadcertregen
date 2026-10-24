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

use local_iomadcertregen\local\{learner, publication, publication_files, records, service};
use local_iomadcertregen\privacy\provider;
use core_privacy\local\request\{approved_contextlist, writer};

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/publication_testcase.php');

/**
 * Explicit native file publication, retained originals, privacy and lifecycle invariants.
 *
 * @covers \local_iomadcertregen\local\publication
 * @covers \local_iomadcertregen\local\publication_files
 */
#[\PHPUnit\Framework\Attributes\CoversClass(publication::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(publication_files::class)]
final class publication_test extends publication_testcase {
    /**
     * Only reviewed native file variants are accepted; unknown or missing files fail closed.
     */
    public function test_unknown_or_missing_native_file_is_refused(): void {
        global $CFG;
        publication_files::require_compatible();
        $originalroot = $CFG->dirroot;
        $temporaryroot = make_request_directory();
        $profiles = json_decode(file_get_contents(__DIR__ . '/../classes/local/publication_profiles.json'), true);
        foreach (array_keys($profiles[(string)$CFG->branch]) as $path) {
            check_dir_exists(dirname($temporaryroot . '/' . $path), true, true);
            copy($originalroot . '/' . $path, $temporaryroot . '/' . $path);
        }
        $target = $temporaryroot . '/mod/iomadcertificate/view.php';
        try {
            $CFG->dirroot = $temporaryroot;
            publication_files::require_compatible();
            file_put_contents($target, "\n// Unreviewed change.\n", FILE_APPEND);
            try {
                publication_files::require_compatible();
                $this->fail('Unknown native bytes must be refused.');
            } catch (\moodle_exception $error) {
                $this->assertSame('unsupported', $error->errorcode);
            }
            unlink($target);
            try {
                publication_files::require_compatible();
                $this->fail('Missing native file must be refused.');
            } catch (\moodle_exception $error) {
                $this->assertSame('unsupported', $error->errorcode);
            }
        } finally {
            $CFG->dirroot = $originalroot;
        }
    }

    /**
     * Activity and exact history files contain reviewed bytes; originals and native identity survive.
     */
    public function test_native_publication_retains_originals_and_is_idempotent(): void {
        global $DB;
        [$data, $operation, $files, $original] = $this->correction();
        $this->assertCount(2, $files);
        $issue = $DB->get_record('iomadcertificate_issues', ['id' => $data[4]->id]);
        $sink = $this->redirectEmails();
        $events = $this->redirectEvents();
        $corrected = service::file($operation)->get_content();
        $this->assertNotSame($original, $corrected);
        $published = $this->publish($data, $operation);
        $this->assertGreaterThan(0, $published->publishedtime);
        foreach ($files as $file) {
            $this->assertSame($corrected, get_file_storage()->get_file_by_id($file->get_id())->get_content());
        }
        $archives = get_file_storage()->get_area_files(
            $data[5]->id,
            'local_iomadcertregen',
            'original',
            $operation->id,
            'id',
            false
        );
        $this->assertCount(2, $archives);
        foreach ($archives as $archive) {
            $this->assertSame($original, $archive->get_content());
        }
        $this->publish($data, $operation);
        $this->assertSame(0, $sink->count());
        $this->assertCount(1, array_filter($events->get_events(), static function ($event): bool {
            return $event instanceof \local_iomadcertregen\event\copy_published;
        }));
        $this->assertEquals($issue, $DB->get_record('iomadcertificate_issues', ['id' => $issue->id]));
        $this->assertSame(1, $DB->count_records('iomadcertificate_issues', ['iomadcertificateid' => $data[0]->id]));
    }

    /**
     * Merely confirming a correction does not publish it or alter native history.
     */
    public function test_confirmation_does_not_publish(): void {
        [$data, $operation, $files, $original] = $this->correction();
        $this->assertSame(0, (int)$operation->publishedtime);
        foreach ($files as $file) {
            $this->assertSame($original, $file->get_content());
        }
        $this->assertNull(learner::operation($data[2]->id, $data[1]->id));
    }

    /**
     * Old completion certificates must not be overwritten by a current issue correction.
     */
    public function test_old_history_is_excluded(): void {
        [$data, $operation] = $this->correction();
        $old = $this->history_file($data, $data[4]->timecreated - DAYSECS);
        $bytes = $old->get_content();
        $this->publish($data, $operation);
        $this->assertSame($bytes, get_file_storage()->get_file_by_id($old->get_id())->get_content());
    }

    /**
     * Duplicate native filenames are refused before replacing the module PDF.
     */
    public function test_ambiguous_activity_names_refuse_publication(): void {
        [$data, $operation, $files, $original] = $this->correction();
        $this->getDataGenerator()->create_module('iomadcertificate', ['course' => $data[3]->id, 'name' => $data[0]->name]);
        try {
            $this->publish($data, $operation);
            $this->fail('Ambiguous history must be refused.');
        } catch (\moodle_exception $error) {
            $this->assertSame('ambiguoushistory', $error->errorcode);
        }
        foreach ($files as $file) {
            $this->assertSame($original, get_file_storage()->get_file_by_id($file->get_id())->get_content());
        }
    }

    /**
     * Native changes after review invalidate the publication intent.
     */
    public function test_changed_native_target_is_refused(): void {
        [$data, $operation, $files] = $this->correction();
        $hash = publication_files::fingerprint($files);
        reset($files)->replace_file_with(service::file($operation));
        $this->expectException(\moodle_exception::class);
        publication::publish($data[2]->id, $operation->id, $hash, sesskey());
    }

    /**
     * Privacy exports retained original PDFs and removes owned copies without touching native ownership.
     */
    public function test_privacy_exports_and_removes_archives(): void {
        global $DB;
        [$data, $operation] = $this->correction();
        $this->publish($data, $operation);
        $approved = new approved_contextlist($data[1], 'local_iomadcertregen', [$data[5]->id]);
        provider::export_user_data($approved);
        $path = [get_string('pluginname', 'local_iomadcertregen'), (string)$operation->id];
        $export = writer::with_context($data[5])->get_data($path);
        $this->assertGreaterThan(0, $export->publishedtime);
        $this->assertCount(3, writer::with_context($data[5])->get_files($path));
        $this->assertFalse(property_exists($export, 'batchkey'));
        provider::delete_data_for_user($approved);
        $this->assertFalse($DB->record_exists('local_iomadcertregen_op', ['id' => $operation->id]));
        $this->assertEmpty(get_file_storage()->get_area_files(
            $data[5]->id,
            'local_iomadcertregen',
            'original',
            $operation->id,
            'id',
            false
        ));
        $this->assertTrue($DB->record_exists('iomadcertificate_issues', ['id' => $data[4]->id]));
    }

    /**
     * A newer explicit publication supersedes the old mapping, retaining each previous version.
     */
    public function test_new_publication_supersedes_without_deleting_previous_archives(): void {
        global $DB;
        [$data, $first] = $this->correction();
        $this->publish($data, $first);
        $second = service::prepare($data[2]->id, $data[4]->id, str_repeat('b8', 16), sesskey());
        $second = service::confirm($data[2]->id, $second->id, sesskey());
        $this->publish($data, $second);
        $this->assertSame((int)$second->id, (int)learner::operation($data[2]->id, $data[1]->id)->id);
        $this->assertSame(0, (int)$DB->get_field('local_iomadcertregen_op', 'publishedtime', ['id' => $first->id]));
        $this->assertCount(2, get_file_storage()->get_area_files(
            $data[5]->id,
            'local_iomadcertregen',
            'original',
            $first->id,
            'id',
            false
        ));
    }
}
