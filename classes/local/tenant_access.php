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
 * Explicit tenant and capability checks for reading another beneficiary's certificate.
 */
final class tenant_access {
    /**
     * Resolve qualified native company-policy classes without changing the selected company.
     *
     * @return array Company, IOMAD and company-context class names.
     */
    private static function classes(): array {
        global $CFG;
        publication_files::require_compatible();
        if ((int)$CFG->branch < 501) {
            require_once($CFG->dirroot . '/local/iomad/lib/company.php');
            require_once($CFG->dirroot . '/local/iomad/lib/iomad.php');
            return ['\\company', '\\iomad', '\\core\\context\\company'];
        }
        return ['\\local_iomad\\company', '\\local_iomad\\iomad', '\\local_iomad\\custom_context\\context_company'];
    }

    /**
     * Check one recorded company, retaining native department and child-company restrictions.
     *
     * @param int $userid Beneficiary.
     * @param int $companyid Recorded company.
     * @param bool $history Permit native history-download capability as well as view-other.
     * @return bool Whether the current manager may read this company's beneficiary.
     */
    public static function manager_allowed(int $userid, int $companyid, bool $history): bool {
        [$companyclass, $iomadclass, $contextclass] = self::classes();
        $current = $iomadclass::get_my_companyid(\context_system::instance(), false);
        $companies = $companyclass::get_companies_select(false, false, true);
        if ($current <= 0 || $companyid <= 0 || !isset($companies[$current])) {
            return false;
        }
        $context = $contextclass::instance($current);
        $allowed = $iomadclass::has_capability('mod/iomadcertificate:viewother', $context);
        if ($history) {
            $allowed = $allowed || $iomadclass::has_capability('block/iomad_company_admin:downloadcertificates', $context);
        }
        return $allowed && self::company_allowed($current, $companyid, $context, $companyclass, $iomadclass) &&
            $companyclass::check_can_manage($userid);
    }

    /**
     * Require an explicit selected company or an authorised descendant, never an unrelated company.
     *
     * @param int $current Selected company.
     * @param int $target Recorded company.
     * @param \context $context Selected company context.
     * @param string $companyclass Native company class.
     * @param string $iomadclass Native capability class.
     * @return bool Company scope is permitted.
     */
    private static function company_allowed(
        int $current,
        int $target,
        \context $context,
        string $companyclass,
        string $iomadclass
    ): bool {
        if ($current === $target) {
            return true;
        }
        if (!$iomadclass::has_capability('block/iomad_company_admin:canviewchildren', $context)) {
            return false;
        }
        $company = new $companyclass($current);
        return isset($company->get_child_companies_recursive()[$target]);
    }

    /**
     * Require company membership and course assignment before a manager reads an activity PDF.
     *
     * @param \context_module $context Activity context.
     * @param int $userid Beneficiary.
     */
    public static function require_activity_reader(\context_module $context, int $userid): void {
        global $CFG, $DB;
        $cm = get_coursemodule_from_id('iomadcertificate', $context->instanceid, 0, false, MUST_EXIST);
        $modern = (int)$CFG->branch >= 501;
        $courses = $modern ? 'local_iomad_company_courses' : 'company_course';
        $companies = records::company_ids($userid);
        if (count($companies) === 1) {
            $companyid = reset($companies);
            if (
                $DB->record_exists($courses, ['companyid' => $companyid, 'courseid' => $cm->course]) &&
                    self::manager_allowed($userid, $companyid, false)
            ) {
                return;
            }
        }
        throw new \required_capability_exception($context, 'mod/iomadcertificate:viewother', 'nopermissions', '');
    }

    /**
     * Protect native history even after course unenrolment; its recorded company remains authoritative.
     *
     * @param int $userid History beneficiary.
     * @param int $companyid Historical company.
     */
    public static function require_history_reader(int $userid, int $companyid): void {
        global $USER;
        if (!isloggedin() || isguestuser()) {
            throw new \required_capability_exception(\context_system::instance(), 'moodle/site:config', 'nopermissions', '');
        }
        if (is_siteadmin() || (int)$USER->id === $userid) {
            return;
        }
        if (!self::manager_allowed($userid, $companyid, true)) {
            throw new \required_capability_exception(
                \context_system::instance(),
                'block/iomad_company_admin:downloadcertificates',
                'nopermissions',
                ''
            );
        }
    }
}
