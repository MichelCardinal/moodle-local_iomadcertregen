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
 * Administrative archive choices, expiry and correction records.
 *
 * @covers \local_iomadcertregen\local\publication
 * @covers \local_iomadcertregen\local\publication_files
 */
#[\PHPUnit\Framework\Attributes\CoversClass(publication::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(publication_files::class)]
final class archive_policy_test extends publication_testcase {
    /**
     * Choosing no archive publishes reviewed bytes and records the reason without retaining former PDFs.
     */
    public function test_publication_without_archive_preserves_audit_and_native_identity(): void {
        global $DB;
        [$data, $operation, $files] = $this->correction();
        $issue = clone $data[4];
        $reason = 'Wrong certificate template selected when creating the course.';
        $published = publication::publish(
            $data[2]->id,
            $operation->id,
            publication_files::fingerprint($files),
            sesskey(),
            ['keep' => false, 'reason' => $reason]
        );
        $this->assertSame(0, (int)(int)$published->keeporiginal);
        $this->assertSame($reason, $published->correctionreason);
        $this->assertGreaterThan(0, $published->correctiontime);
        $this->assertSame(0, (int)$published->originalexpires);
        $this->assertCount(0, get_file_storage()->get_area_files(
            $data[5]->id,
            'local_iomadcertregen',
            'original',
            $operation->id,
            'id',
            false
        ));
        foreach ($files as $file) {
            $stored = get_file_storage()->get_file_by_id($file->get_id());
            $this->assertSame(service::file($operation)->get_content(), $stored->get_content());
        }
        $this->assertEquals($issue, $DB->get_record('iomadcertificate_issues', ['id' => $issue->id]));
        $replayed = publication::publish(
            $data[2]->id,
            $operation->id,
            str_repeat('0', 64),
            sesskey(),
            ['keep' => true, 'reason' => 'Different reason']
        );
        $this->assertSame(0, (int)$replayed->keeporiginal);
        $this->assertSame($reason, $replayed->correctionreason);
    }

    /**
     * Expired administrative archives are inaccessible and removed without erasing the corrected PDF or audit.
     */
    public function test_archive_expiry_preserves_publication_and_correction_record(): void {
        global $DB;
        [$data, $operation, $files] = $this->correction();
        set_config('archivelifetime', HOURSECS, 'local_iomadcertregen');
        $published = $this->publish($data, $operation);
        $this->assertSame($published->correctiontime + HOURSECS, $published->originalexpires);
        $archives = get_file_storage()->get_area_files(
            $data[5]->id,
            'local_iomadcertregen',
            'original',
            $operation->id,
            'id',
            false
        );
        $archive = reset($archives);
        $this->assertNotFalse(\local_iomadcertregen\local\documents::file($published, 'original', $archive->get_filename()));
        set_config('archivelifetime', 0, 'local_iomadcertregen');
        $this->assertSame($published->originalexpires, (int)$DB->get_field('local_iomadcertregen_op', 'originalexpires', [
            'id' => $operation->id,
        ]));
        $DB->set_field('local_iomadcertregen_op', 'originalexpires', time() - 1, ['id' => $operation->id]);
        $published = records::operation($data[2]->id, $operation->id);
        $this->assertFalse(\local_iomadcertregen\local\documents::file($published, 'original', $archive->get_filename()));
        (new \local_iomadcertregen\task\cleanup())->execute();
        $this->assertCount(0, get_file_storage()->get_area_files(
            $data[5]->id,
            'local_iomadcertregen',
            'original',
            $operation->id,
            'id',
            false
        ));
        $this->assertSame($published->correctionreason, records::operation($data[2]->id, $operation->id)->correctionreason);
        $this->assertNotFalse(service::file($published));
        foreach ($files as $file) {
            $stored = get_file_storage()->get_file_by_id($file->get_id());
            $this->assertSame(service::file($operation)->get_content(), $stored->get_content());
        }
    }

    /**
     * An oversized audit reason is refused before replacing any native content.
     */
    public function test_invalid_reason_does_not_replace_native_files(): void {
        [$data, $operation, $files, $original] = $this->correction();
        try {
            publication::publish(
                $data[2]->id,
                $operation->id,
                publication_files::fingerprint($files),
                sesskey(),
                ['keep' => false, 'reason' => str_repeat('x', 256)]
            );
            $this->fail('Oversized reason must be refused.');
        } catch (\moodle_exception $error) {
            $this->assertSame('invalidreason', $error->errorcode);
        }
        foreach ($files as $file) {
            $this->assertSame($original, get_file_storage()->get_file_by_id($file->get_id())->get_content());
        }
    }
    /**
     * A superseded correction cannot be republished or have its historical archive decision rewritten.
     */
    public function test_superseded_no_archive_correction_cannot_be_republished(): void {
        [$data, $operation, $files] = $this->correction();
        publication::publish($data[2]->id, $operation->id, publication_files::fingerprint($files), sesskey(), [
            'keep' => false, 'reason' => 'First correction',
        ]);
        $next = service::prepare($data[2]->id, $data[4]->id, str_repeat('f9', 16), sesskey());
        service::confirm($data[2]->id, $next->id, sesskey());
        $this->publish($data, $next);
        $previous = records::operation($data[2]->id, $operation->id);
        $this->assertSame(0, (int)$previous->publishedtime);
        $this->assertSame('First correction', $previous->correctionreason);
        $this->expectException(\moodle_exception::class);
        publication::publish($data[2]->id, $operation->id, str_repeat('0', 64), sesskey());
    }
    /**
     * Missing or unknown archive choices cannot silently turn into destructive no-archive publication.
     */
    public function test_archive_choice_is_validated_without_javascript(): void {
        $this->assertArrayHasKey('keeporiginal', \local_iomadcertregen\form\publication_options::validation([]));
        $this->assertArrayHasKey('keeporiginal', \local_iomadcertregen\form\publication_options::validation(['keeporiginal' => 2]));
        $this->assertSame([], \local_iomadcertregen\form\publication_options::validation(['keeporiginal' => 0]));
        $this->assertSame([], \local_iomadcertregen\form\publication_options::validation(['keeporiginal' => 1]));
    }
    /**
     * A forced institutional choice is enforced by the publication service and both form paths.
     *
     * @dataProvider forced_choices
     * @param bool $keep Whether the institution retains old PDFs.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('forced_choices')]
    public function test_forced_archive_policy(bool $keep): void {
        [$data, $operation, $files] = $this->correction();
        set_config('archivepolicy', $keep ? 'keep' : 'discard', 'local_iomadcertregen');
        $this->assertNotEmpty(\local_iomadcertregen\form\publication_options::validation(['keeporiginal' => (int)!$keep]));
        try {
            publication::publish($data[2]->id, $operation->id, publication_files::fingerprint($files), sesskey(), [
                'keep' => !$keep,
            ]);
            $this->fail('A forged or stale archive decision must be rejected.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('archivepolicychanged', $exception->errorcode);
        }
        foreach ($files as $file) {
            $this->assertSame($file->get_contenthash(), get_file_storage()->get_file_by_id($file->get_id())->get_contenthash());
        }
        $published = publication::publish(
            $data[2]->id,
            $operation->id,
            publication_files::fingerprint($files),
            sesskey(),
            ['keep' => $keep]
        );
        $this->assertSame((int)$keep, (int)$published->keeporiginal);
        $archives = get_file_storage()->get_area_files($data[5]->id, 'local_iomadcertregen', 'original', $operation->id, '', false);
        $this->assertCount($keep ? count($files) : 0, $archives);
        set_config('archivepolicy', $keep ? 'discard' : 'keep', 'local_iomadcertregen');
        $this->assertSame((int)$keep, (int)records::operation($data[2]->id, $operation->id)->keeporiginal);
        $this->assertCount(count($archives), get_file_storage()->get_area_files(
            $data[5]->id,
            'local_iomadcertregen',
            'original',
            $operation->id,
            '',
            false
        ));
    }

    /**
     * Provide both institutional retention choices.
     *
     * @return array Forced archive decisions.
     */
    public static function forced_choices(): array {
        return [[true], [false]];
    }
}
