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

use local_iomadcertregen\local\records;

/**
 * Clean only owned copies when their native context or participant is removed.
 */
final class observer {
    /**
     * Remove copies belonging to a deleted certificate module.
     *
     * @param \core\event\course_module_deleted $event Native event.
     */
    public static function module_deleted(\core\event\course_module_deleted $event): void {
        self::remove('cmid = :cmid', ['cmid' => $event->objectid]);
    }

    /**
     * Remove copies only when a course reset actually removed their native issue.
     *
     * @param \core\event\course_reset_ended $event Native event.
     */
    public static function course_reset(\core\event\course_reset_ended $event): void {
        self::remove(
            "cmid IN (SELECT id FROM {course_modules} WHERE course = :courseid)
            AND NOT EXISTS (SELECT 1 FROM {iomadcertificate_issues} i WHERE i.id = issueid)",
            ['courseid' => $event->courseid]
        );
    }

    /**
     * Remove copies involving a deleted beneficiary or administrator.
     *
     * @param \core\event\user_deleted $event Native event.
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        self::remove(
            'userid = :beneficiary OR actorid = :actor',
            ['beneficiary' => $event->objectid, 'actor' => $event->objectid]
        );
    }

    /**
     * Remove owned rows, leaving a contended record for the cleanup task.
     *
     * @param string $where Parameterised selection.
     * @param array $params Parameters.
     */
    private static function remove(string $where, array $params): void {
        global $DB;
        foreach ($DB->get_records_select('local_iomadcertregen_op', $where, $params) as $operation) {
            $lock = \core\lock\lock_config::get_lock_factory('local_iomadcertregen')
                ->get_lock('issue:' . $operation->issueid, 0);
            if (!$lock) {
                continue;
            }
            try {
                records::delete($operation);
            } finally {
                $lock->release();
            }
        }
    }
}
