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
 * Deliver the published bytes through the native activity's existing button.
 */
final class learner {
    /**
     * Find a publication without creating a native issue or changing session identity.
     *
     * @param int $cmid Activity.
     * @param int $userid Beneficiary.
     * @return \stdClass|null Published operation, if any.
     */
    public static function operation(int $cmid, int $userid): ?\stdClass {
        global $DB;
        $rows = $DB->get_records_sql(
            "SELECT o.* FROM {local_iomadcertregen_op} o
               JOIN {iomadcertificate_issues} i ON i.id = o.issueid AND i.userid = o.userid
              WHERE o.cmid = :cmid AND o.userid = :userid AND o.publishedtime > 0
              ORDER BY o.id DESC",
            ['cmid' => $cmid, 'userid' => $userid],
            0,
            2
        );
        if (!$rows) {
            return null;
        }
        if (count($rows) !== 1) {
            throw new \moodle_exception('publicationunavailable', 'local_iomadcertregen');
        }
        return reset($rows);
    }

    /**
     * Apply native course restrictions and ownership before retrieving published bytes.
     *
     * @param int $cmid Activity.
     * @param int $userid Beneficiary.
     * @return array Verified operation, PDF and native activity.
     */
    public static function document(int $cmid, int $userid): array {
        global $DB, $USER;
        $cm = get_coursemodule_from_id('iomadcertificate', $cmid, 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        require_login($course, false, $cm);
        $context = \context_module::instance($cmid);
        access::require_reader($context, $userid);
        $certificate = $DB->get_record('iomadcertificate', ['id' => $cm->instance, 'course' => $course->id], '*', MUST_EXIST);
        $operation = self::operation($cmid, $userid);
        if (
            !$operation || (int)$operation->contextid !== (int)$context->id ||
                $operation->status !== 'confirmed' || retention::expired($operation)
        ) {
            throw new \moodle_exception('unavailable', 'local_iomadcertregen');
        }
        $issue = self::issue($operation, $certificate, $userid);
        self::require_fresh_scope($operation, [$certificate, \core_user::get_user($userid), $cm, $course, $issue, $context]);
        if (
            (int)$USER->id === $userid && !is_siteadmin() &&
                !self::current_enrolment($course->id, $userid, (int)$issue->timecreated)
        ) {
            throw new \moodle_exception('invalidissue', 'local_iomadcertregen');
        }
        self::require_time($certificate, $course, $context);
        return [$operation, publication::pdf($operation), $certificate];
    }

    /**
     * Do not attach a historical issue to a later enrolment period.
     *
     * @param int $courseid Course.
     * @param int $userid Beneficiary.
     * @param int $issuedate Historical issue date.
     * @return bool Whether an active enrolment predates this issue.
     */
    private static function current_enrolment(int $courseid, int $userid, int $issuedate): bool {
        global $DB;
        $enrolments = $DB->get_records_sql(
            "SELECT ue.id, ue.timestart FROM {user_enrolments} ue JOIN {enrol} e ON e.id = ue.enrolid
              WHERE e.courseid = :courseid AND e.status = 0 AND ue.status = 0 AND ue.userid = :userid
                AND (ue.timeend = 0 OR ue.timeend > :now)",
            ['courseid' => $courseid, 'userid' => $userid, 'now' => time()]
        );
        foreach ($enrolments as $enrolment) {
            if (!$enrolment->timestart || $issuedate > $enrolment->timestart) {
                return true;
            }
        }
        return false;
    }
    /**
     * Resolve a unique unchanged native issue without creating a new one.
     *
     * @param \stdClass $operation Publication.
     * @param \stdClass $certificate Native activity.
     * @param int $userid Beneficiary.
     * @return \stdClass Native issue.
     */
    private static function issue(\stdClass $operation, \stdClass $certificate, int $userid): \stdClass {
        global $DB;
        $issue = $DB->get_record('iomadcertificate_issues', [
            'id' => $operation->issueid, 'iomadcertificateid' => $certificate->id, 'userid' => $userid,
        ], '*', MUST_EXIST);
        if (
            !hash_equals($operation->issuehash, publication::issue_hash($issue)) ||
                $DB->count_records('iomadcertificate_issues', ['iomadcertificateid' => $certificate->id, 'userid' => $userid]) !== 1
        ) {
            throw new \moodle_exception('invalidissue', 'local_iomadcertregen');
        }
        return $issue;
    }

    /**
     * Preserve the native certificate's minimum course-time rule.
     *
     * @param \stdClass $certificate Native activity.
     * @param \stdClass $course Course.
     * @param \context_module $context Activity context.
     */
    private static function require_time(\stdClass $certificate, \stdClass $course, \context_module $context): void {
        global $CFG;
        // The saved publication does not depend on the activity's subsequently selected template.
        require_once($CFG->dirroot . '/mod/iomadcertificate/locallib.php');
        if (!$certificate->requiredtime || has_capability('mod/iomadcertificate:manage', $context)) {
            return;
        }
        $stores = explode(',', (string)get_config('tool_log', 'enabled_stores'));
        $readers = get_log_manager()->get_readers();
        if (
            array_diff($stores, array_keys($readers)) ||
                iomadcertificate_get_course_time($course->id) < $certificate->requiredtime * 60
        ) {
            throw new \moodle_exception('requiredtimenotmet', 'iomadcertificate');
        }
    }
    /**
     * Guard database access during upgrades and restrict diversion to a PDF download action.
     *
     * @return bool Whether the native download integration is active.
     */
    public static function native_download_enabled(): bool {
        return optional_param('action', '', PARAM_ALPHA) !== '' &&
            (int)get_config('local_iomadcertregen', 'version') >= 2026102400;
    }

    /**
     * Resolve the effective beneficiary exactly as the qualified native activity does.
     *
     * @return int Requested beneficiary only for native view-other users; otherwise the current user.
     */
    public static function native_userid(): int {
        global $USER;
        $requested = optional_param('userid', 0, PARAM_INT);
        if ($requested && has_capability('mod/iomadcertificate:viewother', \context_system::instance())) {
            return $requested;
        }
        return (int)$USER->id;
    }

    /**
     * Keep a retained personal snapshot private when current inputs no longer justify delegated access.
     *
     * @param \stdClass $operation Published copy.
     * @param array $data Current native source.
     */
    private static function require_fresh_scope(\stdClass $operation, array $data): void {
        global $USER;
        if (is_siteadmin() || (int)$USER->id === (int)$operation->userid) {
            return;
        }
        if (!hash_equals($operation->sourcehash, records::fingerprint($data))) {
            throw new \moodle_exception('publicationreviewrequired', 'local_iomadcertregen');
        }
    }

    /**
     * Do not divert a new native enrolment period to a previous historical publication.
     *
     * @param int $cmid Activity.
     * @param int $userid Beneficiary.
     * @return \stdClass|null Publication valid for the current native issue.
     */
    public static function native_operation(int $cmid, int $userid): ?\stdClass {
        global $DB, $USER;
        $operation = self::operation($cmid, $userid);
        if (!$operation) {
            return null;
        }
        $issue = $DB->get_record('iomadcertificate_issues', ['id' => $operation->issueid, 'userid' => $userid]);
        if (!$issue || !hash_equals($operation->issuehash, publication::issue_hash($issue))) {
            return null;
        }
        $cm = get_coursemodule_from_id('iomadcertificate', $cmid, 0, false, MUST_EXIST);
        if ((int)$issue->iomadcertificateid !== (int)$cm->instance) {
            return null;
        }
        if (
            (int)$USER->id === $userid && !is_siteadmin() &&
                !self::current_enrolment($cm->course, $userid, (int)$issue->timecreated)
        ) {
            return null;
        }
        return $operation;
    }
}
