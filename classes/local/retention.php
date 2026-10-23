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
}
