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

namespace local_iomadcertregen\task;

/**
 * Remove expired corrected copies and orphaned operation records.
 */
final class cleanup extends \core\task\scheduled_task {
    /**
     * Return Task name.
     *
     * @return string Task name.
     */
    public function get_name(): string {
        return get_string('cleanup', 'local_iomadcertregen');
    }

    /**
     * Remove only plugin-owned data.
     */
    public function execute(): void {
        global $DB;
        $rows = $DB->get_recordset_sql(
            "SELECT o.* FROM {local_iomadcertregen_op} o
              LEFT JOIN {context} c ON c.id = o.contextid
              LEFT JOIN {iomadcertificate_issues} i ON i.id = o.issueid
              LEFT JOIN {user} u ON u.id = o.userid
              LEFT JOIN {user} a ON a.id = o.actorid
             WHERE (o.timeexpires > 0 AND o.timeexpires <= :now) OR c.id IS NULL OR i.id IS NULL
                OR u.id IS NULL OR u.deleted = 1 OR a.id IS NULL OR a.deleted = 1",
            ['now' => time()]
        );
        try {
            foreach ($rows as $operation) {
                $lock = \core\lock\lock_config::get_lock_factory('local_iomadcertregen')
                    ->get_lock('issue:' . $operation->issueid, 0);
                if (!$lock) {
                    continue;
                }
                try {
                    // Recheck after acquiring the same lock used for confirmation.
                    $current = $DB->get_record('local_iomadcertregen_op', ['id' => $operation->id]);
                    if (
                        $current && (\local_iomadcertregen\local\retention::expired($current) ||
                            !$DB->record_exists('context', ['id' => $current->contextid]) ||
                            !$DB->record_exists('iomadcertificate_issues', ['id' => $current->issueid]) ||
                            !$DB->record_exists('user', ['id' => $current->userid, 'deleted' => 0]) ||
                            !$DB->record_exists('user', ['id' => $current->actorid, 'deleted' => 0]))
                    ) {
                        \local_iomadcertregen\local\records::delete($current);
                    }
                } finally {
                    $lock->release();
                }
            }
        } finally {
            $rows->close();
        }
        \local_iomadcertregen\local\retention::purge_archives();
    }
}
