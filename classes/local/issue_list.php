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
 * Paginate only beneficiaries the current operator is permitted to correct.
 */
final class issue_list {
    /**
     * Read an authorised page and total without disclosing beneficiaries outside the authorised groups.
     *
     * @param int $cmid Activity.
     * @param int $page Zero-based page.
     * @param string $search Optional learner name search.
     * @return array Visible issue records and their total.
     */
    public static function page(int $cmid, int $page, string $search = ''): array {
        global $DB;
        $context = \context_module::instance($cmid);
        access::require_permission($context);
        $cm = get_coursemodule_from_id('iomadcertificate', $cmid, 0, false, MUST_EXIST);
        $where = 'i.iomadcertificateid = :instance';
        $params = ['instance' => $cm->instance];
        if ($search !== '') {
            $where .= ' AND (' . $DB->sql_like('u.firstname', ':firstname', false) . ' OR ' .
                $DB->sql_like('u.lastname', ':lastname', false) . ')';
            $params['firstname'] = '%' . $DB->sql_like_escape($search) . '%';
            $params['lastname'] = $params['firstname'];
        }
        $names = \core_user\fields::for_name()->get_sql('u')->selects;
        $records = $DB->get_recordset_sql('SELECT i.*' . $names . ', u.deleted, u.suspended
            FROM {iomadcertificate_issues} i JOIN {user} u ON u.id = i.userid
            WHERE ' . $where . ' ORDER BY u.lastname, u.firstname, i.id', $params);
        $rows = [];
        $total = 0;
        try {
            foreach ($records as $record) {
                if (!access::user_allowed($context, (int)$record->userid)) {
                    continue;
                }
                if ($total >= max(0, $page) * batch::LIMIT && count($rows) < batch::LIMIT) {
                    $rows[$record->id] = $record;
                }
                $total++;
            }
        } finally {
            $records->close();
        }
        return [$rows, $total];
    }
}
