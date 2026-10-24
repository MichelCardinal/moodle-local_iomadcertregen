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

use local_iomadcertregen\local\{publication, publication_files, service};

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/certificate_testcase.php');

/**
 * Exact native history files and different original/corrected PDF fixtures.
 */
abstract class publication_testcase extends certificate_testcase {
    /**
     * Prepare and confirm a different template, with a native history record.
     *
     * @return array Native records, confirmed operation, native files and original bytes.
     */
    protected function correction(): array {
        global $DB;
        $data = $this->fixture();
        $this->getDataGenerator()->enrol_user($data[1]->id, $data[3]->id, 'student', 'manual', 0);
        $native = $this->history_file($data, (int)$data[4]->timecreated);
        $bytes = $native->get_content();
        $DB->set_field('iomadcertificate', 'iomadcertificatetype', 'letter_non_embedded', ['id' => $data[0]->id]);
        $data[0]->iomadcertificatetype = 'letter_non_embedded';
        $prepared = service::prepare($data[2]->id, $data[4]->id, str_repeat('a8', 16), sesskey());
        $operation = service::confirm($data[2]->id, $prepared->id, sesskey());
        return [$data, $operation, publication_files::targets($data), $bytes];
    }

    /**
     * Create a real IOMAD course-history file using the qualified branch's schema.
     *
     * @param array $data Native fixture.
     * @param int $completed Exact historical completion date.
     * @return \stored_file Native history file.
     */
    protected function history_file(array $data, int $completed): \stored_file {
        global $CFG, $DB;
        $legacy = (int)$CFG->branch < 501;
        $track = (object)[
            'courseid' => $data[3]->id, 'coursename' => $data[3]->fullname,
            'userid' => $data[1]->id, 'timecompleted' => $completed, 'companyid' => 0, 'timeenrolled' => 0,
        ];
        $trackid = $DB->insert_record($legacy ? 'local_iomad_track' : 'local_iomad_tracks', $track);
        $filename = clean_filename(format_string(rtrim($data[0]->name, '.')) . '.pdf');
        $DB->insert_record('local_iomad_track_certs', (object)['trackid' => $trackid, 'filename' => $filename]);
        $originals = get_file_storage()->get_area_files($data[5]->id, 'mod_iomadcertificate', 'issue', $data[4]->id, 'id', false);
        return get_file_storage()->create_file_from_storedfile([
            'contextid' => \context_user::instance($data[1]->id)->id,
            'component' => $legacy ? 'local_iomad_track' : 'local_iomad',
            'filearea' => $legacy ? 'issue' : 'certificate_issue', 'itemid' => $trackid,
            'filepath' => '/', 'filename' => $filename, 'userid' => $data[1]->id,
        ], reset($originals));
    }

    /**
     * Publish a reviewed fixture through the public service.
     *
     * @param array $data Native records.
     * @param \stdClass $operation Confirmed operation.
     * @param array $archivepolicy Optional administrative archive policy.
     * @return \stdClass Published operation.
     */
    protected function publish(array $data, \stdClass $operation, array $archivepolicy = []): \stdClass {
        return publication::publish(
            $data[2]->id,
            $operation->id,
            publication_files::fingerprint(publication_files::targets($data)),
            sesskey(),
            $archivepolicy
        );
    }

    /**
     * Create an actual native company, root department and current member/manager assignments.
     *
     * @param array $data Certificate fixture.
     * @param int $managerid Fictional manager.
     * @param int $parentid Optional parent company.
     * @return int Native company ID.
     */
    protected function company_scope(array $data, int $managerid, int $parentid = 0): int {
        global $CFG, $DB;
        $modern = (int)$CFG->branch >= 501;
        $companyid = $DB->insert_record($modern ? 'local_iomad_companies' : 'company', (object)[
            'name' => 'Fictional scope ' . $managerid, 'shortname' => 'ICRSCOPE' . $managerid,
            'city' => 'Example', 'country' => 'CA', 'theme' => '', 'parentid' => $parentid,
        ]);
        $department = (object)[
            'name' => 'Fictional department', 'shortname' => 'ICRROOT' . $companyid,
            ($modern ? 'parentid' : 'parent') => 0,
            ($modern ? 'companyid' : 'company') => $companyid,
        ];
        $departmentid = $DB->insert_record($modern ? 'local_iomad_company_departments' : 'department', $department);
        foreach ([$data[1]->id => 0, $managerid => 1] as $userid => $type) {
            $DB->insert_record($modern ? 'local_iomad_company_users' : 'company_users', (object)[
                'companyid' => $companyid, 'userid' => $userid, 'managertype' => $type,
                'departmentid' => $departmentid, 'lastused' => time(),
            ]);
        }
        $DB->insert_record($modern ? 'local_iomad_company_courses' : 'company_course', (object)[
            'companyid' => $companyid, 'courseid' => $data[3]->id, 'departmentid' => $departmentid,
        ]);
        $DB->set_field($modern ? 'local_iomad_tracks' : 'local_iomad_track', 'companyid', $companyid, [
            'courseid' => $data[3]->id, 'userid' => $data[1]->id,
        ]);
        return $companyid;
    }

    /**
     * Grant an explicit native manager role for permission tests, never product-side elevation.
     *
     * @param int $userid Fictional manager.
     * @param array $extra Additional native capabilities.
     */
    protected function manager_role(int $userid, array $extra = ['block/iomad_company_admin:editallusers']): void {
        $role = $this->getDataGenerator()->create_role();
        $caps = ['mod/iomadcertificate:viewother', 'block/iomad_company_admin:downloadcertificates'];
        foreach (array_merge($caps, $extra) as $capability) {
            assign_capability($capability, CAP_ALLOW, $role, \context_system::instance());
        }
        role_assign($role, $userid, \context_system::instance()->id);
    }
}
