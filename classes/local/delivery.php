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
 * Explicit, idempotent delivery of an already confirmed administrative copy.
 */
final class delivery {
    /**
     * Bind the reviewed destination to the current beneficiary profile without storing the address.
     *
     * @param \stdClass $user Beneficiary.
     * @return string Destination fingerprint.
     */
    public static function recipient_hash(\stdClass $user): string {
        return hash('sha256', $user->id . ':' . $user->email);
    }

    /**
     * Send a confirmed copy once, preserving an uncertain result instead of retrying blindly.
     *
     * @param int $cmid Activity identifier.
     * @param int $id Confirmed operation identifier.
     * @param string $requestkey Explicit form request identifier.
     * @param string $recipienthash Destination reviewed by the administrator.
     * @param string $sesskey Session protection.
     * @param mail_transport|null $transport Moodle mail transport, injectable for failure tests.
     * @return \stdClass Updated operation.
     */
    public static function send(
        int $cmid,
        int $id,
        string $requestkey,
        string $recipienthash,
        string $sesskey,
        ?mail_transport $transport = null
    ): \stdClass {
        global $DB, $USER;
        if (!confirm_sesskey($sesskey) || !preg_match('/^[a-f0-9]{32}$/D', $requestkey)) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }
        $operation = records::operation($cmid, $id);
        if ($operation->status !== 'confirmed' || (int)$operation->actorid !== (int)$USER->id) {
            throw new \moodle_exception('unavailable', 'local_iomadcertregen');
        }
        $lock = \core\lock\lock_config::get_lock_factory('local_iomadcertregen')->get_lock('issue:' . $operation->issueid, 0);
        if (!$lock) {
            throw new \moodle_exception('busy', 'local_iomadcertregen');
        }
        try {
            $operation = records::operation($cmid, $id);
            $key = hash('sha256', $USER->id . ':' . $id . ':' . $requestkey);
            if (
                in_array($operation->mailstatus, ['submitted', 'sending', 'unknown'], true) ||
                    hash_equals($operation->mailrequestkey, $key)
            ) {
                return $operation;
            }
            [, $user, , $course] = records::issue($cmid, $operation->issueid);
            self::require_recipient($user, $recipienthash);
            $file = self::confirmed_file($operation);
            // SMTP cannot be rolled back. Persist the attempt before contacting the transport.
            $operation->mailstatus = 'sending';
            $operation->mailtime = time();
            $operation->mailrequestkey = $key;
            $DB->update_record('local_iomadcertregen_op', $operation);
            $operation->mailstatus = self::submit($transport ?? new mail_transport(), $user, $course, $file);
            $operation->mailtime = time();
            $DB->update_record('local_iomadcertregen_op', $operation);
            \local_iomadcertregen\event\copy_email_attempted::create([
                'context' => \context_module::instance($cmid), 'objectid' => $id,
                'relateduserid' => $user->id, 'other' => ['status' => $operation->mailstatus],
            ])->trigger();
            return $operation;
        } finally {
            $lock->release();
        }
    }

    /**
     * Classify transport results without exposing transport errors or retrying uncertain submissions.
     *
     * @param mail_transport $transport Moodle mail transport.
     * @param \stdClass $user Current beneficiary.
     * @param \stdClass $course Native course.
     * @param \stored_file $file Verified attachment.
     * @return string Bounded submission outcome.
     */
    private static function submit(mail_transport $transport, \stdClass $user, \stdClass $course, \stored_file $file): string {
        try {
            return $transport->send($user, $course, $file) ? 'submitted' : 'failed';
        } catch (\Throwable $error) {
            // A transport exception may occur after acceptance; never imply that a retry is safe.
            return 'unknown';
        }
    }

    /**
     * Require the bounded attachment with the exact confirmed hash.
     *
     * @param \stdClass $operation Confirmed operation.
     * @return \stored_file Verified PDF.
     */
    private static function confirmed_file(\stdClass $operation): \stored_file {
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
     * Refuse disabled mail and destinations Moodle would silently skip.
     *
     * @param \stdClass $user Current recipient.
     * @param string $hash Reviewed destination fingerprint.
     */
    private static function require_recipient(\stdClass $user, string $hash): void {
        global $CFG;
        if (!empty($CFG->noemailever)) {
            throw new \moodle_exception('emaildisabled', 'local_iomadcertregen');
        }
        if (!hash_equals(self::recipient_hash($user), $hash)) {
            throw new \moodle_exception('emailrecipientchanged', 'local_iomadcertregen');
        }
        if (
            empty($user->confirmed) || !empty($user->emailstop) || $user->auth === 'nologin' ||
                !validate_email($user->email) || str_ends_with(strtolower($user->email), '.invalid') ||
                over_bounce_threshold($user)
        ) {
            throw new \moodle_exception('emailrecipientinvalid', 'local_iomadcertregen');
        }
    }
}
