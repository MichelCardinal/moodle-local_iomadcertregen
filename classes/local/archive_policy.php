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
 * Site policy for future administrative archives, independent of existing archive records.
 */
final class archive_policy {
    /**
     * Resolve the institutional decision; null leaves the choice to the correction operator.
     *
     * @return bool|null Forced retention decision or operator choice.
     */
    public static function forced(): ?bool {
        $value = get_config('local_iomadcertregen', 'archivepolicy');
        if ($value === false || $value === '' || $value === 'choice') {
            return null;
        }
        if ($value === 'keep' || $value === 'discard') {
            return $value === 'keep';
        }
        throw new \moodle_exception('invalidarchivepolicy', 'local_iomadcertregen');
    }
}
