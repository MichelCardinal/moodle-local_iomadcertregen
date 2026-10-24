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
 * Validated native records and owned operation storage.
 */
final class records {
    /**
     * Resolve a single existing issue belonging to the given activity.
     *
     * @param int $cmid Module identifier.
     * @param int $issueid Native issue identifier.
     * @return array Native objects and context.
     */
    public static function issue(int $cmid, int $issueid): array {
        global $DB;
        $cm = get_coursemodule_from_id('iomadcertificate', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $context = \context_module::instance($cmid);
        access::require_permission($context);
        $certificate = $DB->get_record('iomadcertificate', ['id' => $cm->instance, 'course' => $course->id], '*', MUST_EXIST);
        $issue = $DB->get_record('iomadcertificate_issues', [
            'id' => $issueid, 'iomadcertificateid' => $certificate->id,
        ], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $issue->userid, 'deleted' => 0, 'suspended' => 0], '*', MUST_EXIST);
        if (!access::user_allowed($context, (int)$user->id)) {
            throw new \required_capability_exception($context, 'local/iomadcertregen:regenerate', 'nopermissions', '');
        }
        if (
            $DB->count_records('iomadcertificate_issues', [
                'iomadcertificateid' => $certificate->id, 'userid' => $user->id,
            ]) !== 1 || empty($issue->code) || empty($issue->timecreated)
        ) {
            throw new \moodle_exception('invalidissue', 'local_iomadcertregen');
        }
        return [$certificate, $user, $cm, $course, $issue, $context];
    }

    /**
     * Capture the native records used by the qualified renderer.
     *
     * @param array $data Validated native records.
     * @return string Input fingerprint, without retaining identity values.
     */
    public static function fingerprint(array $data): string {
        global $DB;
        [$certificate, $user, , $course, $issue] = $data;
        $identity = [];
        foreach (
            ['id', 'firstname', 'lastname', 'firstnamephonetic', 'lastnamephonetic',
                'middlename', 'alternatename', 'lang', 'timezone', 'suspended', 'deleted'] as $field
        ) {
            $identity[$field] = $user->$field ?? null;
        }
        $grades = $DB->get_records_sql(
            "SELECT g.* FROM {grade_grades} g JOIN {grade_items} i ON i.id = g.itemid
              WHERE i.courseid = :courseid AND g.userid = :userid ORDER BY g.id",
            ['courseid' => $course->id, 'userid' => $user->id]
        );
        $values = [
            $certificate, $issue, $identity, $course->fullname, $grades,
            $DB->get_records('course_completions', ['course' => $course->id, 'userid' => $user->id], 'id'),
            $DB->get_records('config_plugins', ['plugin' => 'iomadcertificate'], 'name'),
        ];
        $companies = self::company_ids((int)$user->id);
        if ($companies) {
            $values[] = ['companies' => $companies];
        }
        return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
    }

    /**
     * Read distinct active native company memberships without choosing one on the learner's behalf.
     *
     * @param int $userid Beneficiary.
     * @return int[] Native company IDs in stable order.
     */
    public static function company_ids(int $userid): array {
        global $CFG, $DB;
        $table = (int)$CFG->branch >= 501 ? 'local_iomad_company_users' : 'company_users';
        $rows = $DB->get_records_sql("SELECT DISTINCT companyid FROM {{$table}}
            WHERE userid = :userid AND suspended = 0 ORDER BY companyid", ['userid' => $userid]);
        return array_map('intval', array_keys($rows));
    }

    /**
     * Check operation ownership and current native relationships.
     *
     * @param int $cmid Module identifier.
     * @param int $id Operation identifier.
     * @return \stdClass Owned operation.
     */
    public static function operation(int $cmid, int $id): \stdClass {
        global $DB, $USER;
        $operation = $DB->get_record('local_iomadcertregen_op', ['id' => $id, 'cmid' => $cmid], '*', MUST_EXIST);
        if (!in_array($operation->status, ['prepared', 'confirmed'], true)) {
            throw new \moodle_exception('unavailable', 'local_iomadcertregen');
        }
        $data = self::issue($cmid, $operation->issueid);
        if (
            (int)$operation->userid !== (int)$data[1]->id || (int)$operation->contextid !== (int)$data[5]->id ||
                retention::expired($operation) ||
                ($operation->status !== 'confirmed' &&
                (int)$operation->actorid !== (int)$USER->id)
        ) {
            throw new \moodle_exception('unavailable', 'local_iomadcertregen');
        }
        return $operation;
    }

    /**
     * Delete owned files and metadata; never touches a native certificate.
     *
     * @param \stdClass $operation Owned operation.
     */
    public static function delete(\stdClass $operation): void {
        global $DB;
        foreach (['document', 'original'] as $area) {
            get_file_storage()->delete_area_files($operation->contextid, 'local_iomadcertregen', $area, $operation->id);
        }
        $DB->delete_records('local_iomadcertregen_op', ['id' => $operation->id]);
    }
}
