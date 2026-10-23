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
 * Individual corrections with idempotence and unchanged native evidence.
 */
final class service {
    /**
     * Prepare one corrected copy.
     *
     * @param int $cmid Course module.
     * @param int $issueid Existing native issue.
     * @param string $requestkey One-time form identifier.
     * @param string $sesskey Moodle session protection.
     * @return \stdClass Prepared operation.
     */
    public static function prepare(int $cmid, int $issueid, string $requestkey, string $sesskey): \stdClass {
        global $DB, $USER;
        if (!confirm_sesskey($sesskey) || !preg_match('/^[a-f0-9]{32}$/D', $requestkey)) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }
        $data = records::issue($cmid, $issueid);
        [$certificate, $user, $cm, $course, $issue, $context] = $data;
        $key = hash('sha256', $USER->id . ':' . $cmid . ':' . $issueid . ':' . $requestkey);
        $lock = \core\lock\lock_config::get_lock_factory('local_iomadcertregen')->get_lock('issue:' . $issueid, 0);
        if (!$lock) {
            throw new \moodle_exception('busy', 'local_iomadcertregen');
        }
        try {
            if ($existing = $DB->get_record('local_iomadcertregen_op', ['requestkey' => $key])) {
                return records::operation($cmid, $existing->id);
            }
            // Confirm saved native evidence without creating or replacing anything.
            adapter::original_url($certificate, $issue, $context);
            $data = records::issue($cmid, $issueid);
            $fingerprint = records::fingerprint($data);
            [$certificate, $user, $cm, $course, $issue, $context] = $data;
            $pdf = adapter::render($certificate, $user, $cm, $course, $issue);
            if (!hash_equals($fingerprint, records::fingerprint(records::issue($cmid, $issueid)))) {
                throw new \moodle_exception('sourcechanged', 'local_iomadcertregen');
            }
            $now = time();
            $operation = (object)[
                'contextid' => $context->id, 'cmid' => $cmid, 'issueid' => $issueid,
                'userid' => $user->id, 'actorid' => $USER->id, 'status' => 'prepared',
                'requestkey' => $key, 'sourcehash' => $fingerprint,
                'pdfhash' => hash('sha256', $pdf), 'timecreated' => $now,
                'timemodified' => $now, 'timeexpires' => retention::expiry('prepared', $now),
            ];
            $transaction = $DB->start_delegated_transaction();
            try {
                $operation->id = $DB->insert_record('local_iomadcertregen_op', $operation);
                get_file_storage()->create_file_from_string([
                    'contextid' => $context->id, 'component' => 'local_iomadcertregen',
                    'filearea' => 'document', 'itemid' => $operation->id, 'filepath' => '/',
                    'filename' => 'corrected-certificate.pdf', 'mimetype' => 'application/pdf',
                    'userid' => $user->id,
                ], $pdf);
                \local_iomadcertregen\event\copy_prepared::create([
                    'context' => $context, 'objectid' => $operation->id, 'relateduserid' => $user->id,
                ])->trigger();
                $transaction->allow_commit();
            } catch (\Throwable $error) {
                $transaction->rollback($error);
            }
            return $operation;
        } catch (\Throwable $error) {
            \local_iomadcertregen\event\copy_failed::create([
                'context' => $context, 'relateduserid' => $user->id,
                'other' => ['issueid' => $issueid],
            ])->trigger();
            throw $error;
        } finally {
            $lock->release();
        }
    }

    /**
     * Confirm the exact prepared bytes, never a fresh rendering.
     *
     * @param int $cmid Course module.
     * @param int $id Prepared operation.
     * @param string $sesskey Moodle session protection.
     * @return \stdClass Confirmed operation.
     */
    public static function confirm(int $cmid, int $id, string $sesskey): \stdClass {
        global $DB, $USER;
        if (!confirm_sesskey($sesskey)) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }
        $operation = records::operation($cmid, $id);
        if ((int)$operation->actorid !== (int)$USER->id) {
            throw new \moodle_exception('unavailable', 'local_iomadcertregen');
        }
        $lock = \core\lock\lock_config::get_lock_factory('local_iomadcertregen')->get_lock('issue:' . $operation->issueid, 0);
        if (!$lock) {
            throw new \moodle_exception('busy', 'local_iomadcertregen');
        }
        try {
            $operation = records::operation($cmid, $id);
            if ($operation->status === 'confirmed') {
                return $operation;
            }
            $data = records::issue($cmid, $operation->issueid);
            adapter::original_url($data[0], $data[4], $data[5]);
            if (!hash_equals($operation->sourcehash, records::fingerprint($data))) {
                throw new \moodle_exception('sourcechanged', 'local_iomadcertregen');
            }
            $file = self::file($operation);
            if (!$file || !hash_equals($operation->pdfhash, hash('sha256', $file->get_content()))) {
                throw new \moodle_exception('unavailable', 'local_iomadcertregen');
            }
            $operation->status = 'confirmed';
            $operation->timemodified = time();
            $operation->timeexpires = retention::expiry('confirmed', $operation->timemodified);
            $transaction = $DB->start_delegated_transaction();
            $DB->update_record('local_iomadcertregen_op', $operation);
            \local_iomadcertregen\event\copy_confirmed::create([
                'context' => $data[5], 'objectid' => $id, 'relateduserid' => $operation->userid,
            ])->trigger();
            $transaction->allow_commit();
            return $operation;
        } finally {
            $lock->release();
        }
    }

    /**
     * Retrieve an owned PDF after access policy has been checked.
     *
     * @param \stdClass $operation Operation.
     * @return \stored_file|false Stored file.
     */
    public static function file(\stdClass $operation) {
        return get_file_storage()->get_file(
            $operation->contextid,
            'local_iomadcertregen',
            'document',
            $operation->id,
            '/',
            'corrected-certificate.pdf'
        );
    }
}
