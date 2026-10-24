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
 * Validate and normalise archive policy records crossing the Moodle backup boundary.
 */
final class archive_metadata {
    /**
     * Reject nested archive fields and invalid optional values before restoring older or newer backups.
     *
     * @param \stdClass $copy Candidate metadata.
     * @return bool Whether the optional archive policy is safe.
     */
    public static function valid(\stdClass $copy): bool {
        foreach (['keeporiginal', 'originalexpires', 'correctiontime', 'correctionreason'] as $field) {
            if (isset($copy->$field) && !is_scalar($copy->$field)) {
                return false;
            }
        }
        $default = isset($copy->archivepolicyversion) ? null : 1;
        return self::valid_archive_dates($copy) && in_array($copy->keeporiginal ?? $default, [0, 1, '0', '1'], true) &&
            \core_text::strlen((string)($copy->correctionreason ?? '')) <= 255;
    }

    /**
     * Require complete expiry metadata for new backups, while accepting legacy archives without policy fields.
     *
     * @param \stdClass $copy Backup metadata.
     * @return bool Whether policy version and archive timestamps are valid.
     */
    private static function valid_archive_dates(\stdClass $copy): bool {
        if (
            isset($copy->archivepolicyversion) &&
                ((string)$copy->archivepolicyversion !== '1' || !property_exists($copy, 'correctionreason'))
        ) {
            return false;
        }
        $default = isset($copy->archivepolicyversion) ? null : 0;
        foreach (['originalexpires', 'correctiontime'] as $field) {
            if (filter_var($copy->$field ?? $default, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Preserve historical archive policy, also when restoring an older backup.
     *
     * @param \stdClass $copy Backup metadata.
     * @return array Bounded archive policy fields.
     */
    public static function values(\stdClass $copy): array {
        return [
            'keeporiginal' => empty($copy->keeporiginal) && isset($copy->keeporiginal) ? 0 : 1,
            'originalexpires' => max(0, (int)($copy->originalexpires ?? 0)),
            'correctiontime' => max(0, (int)($copy->correctiontime ?? 0)),
            'correctionreason' => \core_text::substr(clean_param($copy->correctionreason ?? '', PARAM_TEXT), 0, 255),
        ];
    }
}
