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
 * Bounded administrative batches, reusing the individually authorised operations.
 */
final class batch {
    /** @var int Maximum issues processed in one explicit web request. */
    public const LIMIT = 30;

    /**
     * Prepare selected existing issues, recording partial failures without inventing success.
     *
     * @param int $cmid Activity.
     * @param array $issueids Distinct native issue IDs.
     * @param string $requestkey Form intent.
     * @param string $sesskey Session protection.
     * @return array Batch key and failed issue IDs.
     */
    public static function prepare(int $cmid, array $issueids, string $requestkey, string $sesskey): array {
        global $USER;
        access::require_permission(\context_module::instance($cmid));
        access::require_intent($sesskey, $requestkey);
        self::validate_selection($cmid, $issueids);
        $selected = array_map('intval', $issueids);
        sort($selected);
        $key = hash('sha256', $USER->id . ':' . $cmid . ':' . $requestkey . ':' . implode(',', $selected));
        $failed = [];
        foreach ($issueids as $issueid) {
            try {
                service::prepare($cmid, (int)$issueid, substr(hash('sha256', $key . ':' . $issueid), 0, 32), $sesskey, $key);
            } catch (\moodle_exception $error) {
                $failed[] = (int)$issueid;
            }
        }
        return [$key, $failed];
    }

    /**
     * Resolve only this administrator's bounded batch in this activity.
     *
     * @param int $cmid Activity.
     * @param string $key Batch key.
     * @return array Validated operations.
     */
    public static function operations(int $cmid, string $key): array {
        global $DB, $USER;
        access::require_permission(\context_module::instance($cmid));
        if (!preg_match('/^[a-f0-9]{64}$/D', $key)) {
            throw new \moodle_exception('bulkinvalid', 'local_iomadcertregen');
        }
        $operations = $DB->get_records('local_iomadcertregen_op', [
            'cmid' => $cmid, 'actorid' => $USER->id, 'batchkey' => $key,
        ], 'id');
        if (!$operations || count($operations) > self::LIMIT) {
            throw new \moodle_exception('bulkinvalid', 'local_iomadcertregen');
        }
        foreach ($operations as $operation) {
            records::operation($cmid, $operation->id);
        }
        return $operations;
    }

    /**
     * Fingerprint the reviewed PDFs and, for publication, all exact native targets.
     *
     * @param array $operations Batch operations.
     * @param bool $publish Whether to include native target files.
     * @return string Batch review hash.
     */
    public static function fingerprint(array $operations, bool $publish): string {
        return hash('sha256', json_encode(self::review($operations, $publish), JSON_THROW_ON_ERROR));
    }

    /**
     * Capture native targets once so mutations use exactly the reviewed fingerprint.
     *
     * @param array $operations Operations.
     * @param bool $publish Include native targets.
     * @return array Stable values keyed by operation ID.
     */
    private static function review(array $operations, bool $publish): array {
        $values = [];
        foreach ($operations as $operation) {
            $values[$operation->id] = [$operation->pdfhash, $operation->sourcehash];
            if ($publish) {
                if ($operation->status !== 'confirmed') {
                    throw new \moodle_exception('publicationunavailable', 'local_iomadcertregen');
                }
                $values[$operation->id][] = publication_files::fingerprint(
                    publication_files::targets(records::issue($operation->cmid, $operation->issueid))
                );
            }
        }
        return $values;
    }

    /**
     * Confirm or publish after a fresh review, with a visible result for each issue.
     *
     * @param int $cmid Activity.
     * @param string $key Batch key.
     * @param string $reviewhash Batch review fingerprint.
     * @param bool $publish Publish instead of confirm.
     * @param string $sesskey Session protection.
     * @param array $archivepolicy Archive choice and administrative reason.
     * @return array Failed issue IDs; successful operations remain available after an interruption.
     */
    public static function apply(
        int $cmid,
        string $key,
        string $reviewhash,
        bool $publish,
        string $sesskey,
        array $archivepolicy = []
    ): array {
        if (!confirm_sesskey($sesskey)) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }
        $operations = self::operations($cmid, $key);
        $review = self::review($operations, $publish);
        if (!hash_equals(hash('sha256', json_encode($review, JSON_THROW_ON_ERROR)), $reviewhash)) {
            throw new \moodle_exception('sourcechanged', 'local_iomadcertregen');
        }
        $failed = [];
        foreach ($operations as $operation) {
            try {
                if ($publish) {
                    publication::publish($cmid, $operation->id, $review[$operation->id][2], $sesskey, $archivepolicy);
                } else {
                    service::confirm($cmid, $operation->id, $sesskey);
                }
            } catch (\moodle_exception $error) {
                $failed[] = (int)$operation->issueid;
            }
        }
        return $failed;
    }
    /**
     * Reject a foreign, duplicated or oversized selection before any preparation.
     *
     * @param int $cmid Activity.
     * @param array $issueids Native issue IDs.
     */
    private static function validate_selection(int $cmid, array $issueids): void {
        global $DB;
        $cm = get_coursemodule_from_id('iomadcertificate', $cmid, 0, false, MUST_EXIST);
        if (!$issueids || count($issueids) > self::LIMIT || count(array_unique($issueids)) !== count($issueids)) {
            throw new \moodle_exception('bulkinvalid', 'local_iomadcertregen');
        }
        foreach ($issueids as $issueid) {
            if (
                !ctype_digit((string)$issueid) || !$DB->record_exists('iomadcertificate_issues', [
                'id' => (int)$issueid, 'iomadcertificateid' => $cm->instance,
                ])
            ) {
                throw new \moodle_exception('bulkinvalid', 'local_iomadcertregen');
            }
            $userid = $DB->get_field('iomadcertificate_issues', 'userid', ['id' => (int)$issueid], MUST_EXIST);
            if (!access::user_allowed(\context_module::instance($cmid), (int)$userid)) {
                throw new \required_capability_exception(
                    \context_module::instance($cmid),
                    'local/iomadcertregen:regenerate',
                    'nopermissions',
                    ''
                );
            }
        }
    }
}
