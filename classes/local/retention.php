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
 * Capture the institution's retention settings when an operation changes state.
 */
final class retention {
    /**
     * Compute the expiry for a newly prepared or confirmed copy.
     *
     * Existing operations retain their stored deadline when settings change.
     *
     * @param string $status New operation state.
     * @param int $time State transition timestamp.
     * @return int Expiry timestamp, or zero for an unlimited confirmed copy.
     */
    public static function expiry(string $status, int $time): int {
        if ($status === 'prepared') {
            $duration = get_config('local_iomadcertregen', 'preparationlifetime');
            $seconds = $duration === false ? DAYSECS : max(MINSECS, (int)$duration);
            return $time + $seconds;
        }
        if ($status === 'confirmed') {
            $seconds = max(0, (int)get_config('local_iomadcertregen', 'confirmedlifetime'));
            return $seconds ? $time + $seconds : 0;
        }
        throw new \coding_exception('Unknown certificate copy state.');
    }

    /**
     * Check the deadline captured for either supported operation state.
     *
     * @param \stdClass $operation Stored operation.
     * @return bool Whether its deadline has passed.
     */
    public static function expired(\stdClass $operation): bool {
        return $operation->timeexpires > 0 && $operation->timeexpires <= time();
    }

    /**
     * Check the archive-specific deadline without expiring the corrected certificate or audit trail.
     *
     * @param \stdClass $operation Stored operation.
     * @return bool Whether the administrative originals may be accessed.
     */
    public static function archive_available(\stdClass $operation): bool {
        return (bool)($operation->keeporiginal ?? 1) &&
            (empty($operation->originalexpires) || $operation->originalexpires > time());
    }

    /**
     * Remove expired administrative archives only, under the normal issue lock.
     */
    public static function purge_archives(): void {
        global $DB;
        $rows = $DB->get_recordset_sql(
            "SELECT DISTINCT o.* FROM {local_iomadcertregen_op} o
               JOIN {files} f ON f.contextid = o.contextid AND f.itemid = o.id
              WHERE o.originalexpires > 0 AND o.originalexpires <= :now
                AND f.component = :component AND f.filearea = :area",
            ['now' => time(), 'component' => 'local_iomadcertregen', 'area' => 'original']
        );
        try {
            foreach ($rows as $operation) {
                $lock = \core\lock\lock_config::get_lock_factory('local_iomadcertregen')
                    ->get_lock('issue:' . $operation->issueid, 0);
                if (!$lock) {
                    continue;
                }
                try {
                    $current = $DB->get_record('local_iomadcertregen_op', ['id' => $operation->id]);
                    if ($current && !self::archive_available($current)) {
                        get_file_storage()->delete_area_files(
                            $current->contextid,
                            'local_iomadcertregen',
                            'original',
                            $current->id
                        );
                    }
                } finally {
                    $lock->release();
                }
            }
        } finally {
            $rows->close();
        }
    }
}
