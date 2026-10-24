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

namespace local_iomadcertregen\local;

/**
 * Explicit publication of a reviewed PDF, retaining the previous native bytes.
 */
final class publication {
    /**
     * Publish the confirmed copy atomically in Moodle's File API metadata.
     *
     * @param int $cmid Activity.
     * @param int $id Confirmed operation.
     * @param string $reviewhash Reviewed native file fingerprint.
     * @param string $sesskey Session protection.
     * @return \stdClass Published operation.
     */
    public static function publish(int $cmid, int $id, string $reviewhash, string $sesskey): \stdClass {
        global $DB;
        if (!confirm_sesskey($sesskey)) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }
        $operation = records::operation($cmid, $id);
        self::require_eligible($operation);
        $lock = \core\lock\lock_config::get_lock_factory('local_iomadcertregen')->get_lock('issue:' . $operation->issueid, 0);
        if (!$lock) {
            throw new \moodle_exception('busy', 'local_iomadcertregen');
        }
        try {
            $operation = records::operation($cmid, $id);
            if ($operation->publishedtime) {
                return $operation;
            }
            if (
                get_file_storage()->get_area_files(
                    $operation->contextid,
                    'local_iomadcertregen',
                    'original',
                    $operation->id,
                    'id',
                    false
                )
            ) {
                throw new \moodle_exception('publicationunavailable', 'local_iomadcertregen');
            }
            $data = self::source($operation);
            $files = publication_files::targets($data);
            if (!hash_equals(publication_files::fingerprint($files), $reviewhash)) {
                throw new \moodle_exception('sourcechanged', 'local_iomadcertregen');
            }
            $pdf = self::pdf($operation);
            $transaction = $DB->start_delegated_transaction();
            try {
                foreach ($files as $file) {
                    // Retain every previous native PDF before replacing its content; preserve its route and filename.
                    get_file_storage()->create_file_from_storedfile([
                    'contextid' => $operation->contextid, 'component' => 'local_iomadcertregen',
                    'filearea' => 'original', 'itemid' => $operation->id, 'filepath' => '/',
                    'filename' => 'native-' . $file->get_id() . '.pdf', 'userid' => $operation->userid,
                    'source' => json_encode(['nativefileid' => $file->get_id(), 'sha256' => hash('sha256', $file->get_content())]),
                    ], $file);
                    $file->replace_file_with($pdf);
                }
                self::source($operation);
                $DB->set_field('local_iomadcertregen_op', 'publishedtime', 0, ['cmid' => $cmid, 'userid' => $operation->userid]);
                $operation->publishedtime = time();
                $operation->issuehash = self::issue_hash($data[4]);
                $DB->update_record('local_iomadcertregen_op', $operation);
                \local_iomadcertregen\event\copy_published::create([
                'context' => $data[5], 'objectid' => $id, 'relateduserid' => $operation->userid,
                'other' => ['files' => count($files)],
                ])->trigger();
                $transaction->allow_commit();
            } catch (\Throwable $error) {
                $transaction->rollback($error);
            }
            return $operation;
        } finally {
            $lock->release();
        }
    }

    /**
     * Require exact confirmed bytes, also when serving the learner.
     *
     * @param \stdClass $operation Confirmed operation.
     * @return \stored_file Verified PDF.
     */
    public static function pdf(\stdClass $operation): \stored_file {
        $file = service::file($operation);
        if (
            !$file || $file->get_mimetype() !== 'application/pdf' || $file->get_filesize() > adapter::MAX_PDF_BYTES ||
                !hash_equals($operation->pdfhash, hash('sha256', $file->get_content()))
        ) {
            throw new \moodle_exception('unavailable', 'local_iomadcertregen');
        }
        adapter::require_valid_pdf($file->get_content());
        return $file;
    }

    /**
     * Bind publication to the native issue's identity, code and historical date.
     *
     * @param \stdClass $issue Native issue.
     * @return string Identity fingerprint.
     */
    public static function issue_hash(\stdClass $issue): string {
        return hash('sha256', implode(':', [$issue->id, $issue->iomadcertificateid, $issue->userid,
            $issue->code, $issue->timecreated]));
    }
    /**
     * Require a confirmed, non-expiring copy owned by the publishing administrator.
     *
     * @param \stdClass $operation Correction.
     */
    private static function require_eligible(\stdClass $operation): void {
        global $USER;
        if ((int)$operation->actorid !== (int)$USER->id || $operation->status !== 'confirmed' || $operation->timeexpires) {
            throw new \moodle_exception('publicationunavailable', 'local_iomadcertregen');
        }
    }
    /**
     * Verify the current source again before and after native file replacement.
     *
     * @param \stdClass $operation Reviewed correction.
     * @return array Current native source.
     */
    private static function source(\stdClass $operation): array {
        $data = records::issue($operation->cmid, $operation->issueid);
        if (!hash_equals($operation->sourcehash, records::fingerprint($data))) {
            throw new \moodle_exception('sourcechanged', 'local_iomadcertregen');
        }
        return $data;
    }
}
