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

use core_reportbuilder\local\report\column;

/**
 * Read-only display of the current IOMAD Certificate activity settings.
 */
final class inventory_settings {
    /**
     * Native fields, language titles and report formatting types.
     *
     * @return array Setting definitions.
     */
    public static function fields(): array {
        return [
            'intro' => ['inventoryintro', 'longtext'],
            'introformat' => ['inventoryintroformat', 'integer'],
            'emailteachers' => ['inventoryemailteachers', 'boolean'],
            'emailothers' => ['inventoryemailothers', 'longtext'],
            'savecert' => ['inventorysavecert', 'boolean'],
            'reportcert' => ['inventoryreportcert', 'boolean'],
            'delivery' => ['inventorydelivery', 'option'],
            'requiredtime' => ['inventoryrequiredtime', 'integer'],
            'orientation' => ['inventoryorientation', 'option'],
            'borderstyle' => ['inventoryborderstyle', 'image'],
            'bordercolor' => ['inventorybordercolor', 'option'],
            'printwmark' => ['inventoryprintwmark', 'image'],
            'printdate' => ['inventoryprintdate', 'datesource'],
            'datefmt' => ['inventorydatefmt', 'option'],
            'printnumber' => ['inventoryprintnumber', 'boolean'],
            'printgrade' => ['inventoryprintgrade', 'gradesource'],
            'gradefmt' => ['inventorygradefmt', 'option'],
            'printoutcome' => ['inventoryprintoutcome', 'outcome'],
            'printhours' => ['inventoryprinthours', 'text'],
            'printteacher' => ['inventoryprintteacher', 'boolean'],
            'customtext' => ['inventorycustomtext', 'longtext'],
            'printsignature' => ['inventoryprintsignature', 'image'],
            'printseal' => ['inventoryprintseal', 'image'],
            'timecreated' => ['inventorytimecreated', 'timestamp'],
            'timemodified' => ['inventorytimemodified', 'timestamp'],
        ];
    }

    /**
     * Native report column type for each settings type.
     *
     * @param string $kind Settings type.
     * @return int Report column type.
     */
    public static function column_type(string $kind): int {
        return [
            'boolean' => column::TYPE_BOOLEAN,
            'integer' => column::TYPE_INTEGER,
            'timestamp' => column::TYPE_TIMESTAMP,
            'longtext' => column::TYPE_LONGTEXT,
        ][$kind] ?? column::TYPE_TEXT;
    }

    /**
     * Static native choices; reference IDs retain a searchable numeric filter.
     *
     * @param string $name Setting name.
     * @param string $kind Settings type.
     * @return array Localised choices.
     */
    public static function options(string $name, string $kind): array {
        if ($kind === 'boolean') {
            return [0 => get_string('no'), 1 => get_string('yes')];
        }
        $native = [
            'delivery' => [0 => 'openbrowser', 1 => 'download', 2 => 'emailiomadcertificate'],
            'orientation' => ['L' => 'landscape', 'P' => 'portrait'],
            'bordercolor' => [0 => 'no', 1 => 'borderblack', 2 => 'borderbrown', 3 => 'borderblue', 4 => 'bordergreen'],
            'gradefmt' => [1 => 'gradepercent', 2 => 'gradepoints', 3 => 'gradeletter'],
        ];
        $choices = [];
        foreach ($native[$name] ?? [] as $value => $identifier) {
            $choices[$value] = get_string($identifier, $identifier === 'no' ? 'moodle' : 'iomadcertificate');
        }
        if ($name === 'datefmt') {
            foreach (range(1, 5) as $value) {
                $choices[$value] = get_string('inventorydateformat' . $value, 'local_iomadcertregen');
            }
        }
        if (in_array($name, ['datefmt', 'gradefmt'], true)) {
            $choices = [0 => get_string('inventorynotconfigured', 'local_iomadcertregen')] + $choices;
        }
        return $choices;
    }

    /**
     * Escape display values; never execute template files or activity text.
     *
     * @param string $name Setting name.
     * @param string $kind Settings type.
     * @param mixed $value Stored setting.
     * @param \stdClass $row Report row containing the reference's course.
     * @return string Safe report value.
     */
    public static function format(string $name, string $kind, $value, \stdClass $row): string {
        $options = self::options($name, $kind);
        if ($options) {
            return s($options[$value] ?? get_string('inventoryunknown', 'local_iomadcertregen', (string)$value));
        }
        if ($kind === 'timestamp') {
            return $value ? s(userdate((int)$value)) : get_string('no');
        }
        if (in_array($kind, ['datesource', 'gradesource', 'outcome'], true)) {
            return s(self::reference($kind, (int)$value, (int)$row->settingscourseid, $row));
        }
        if ($kind === 'image' && ($value === null || $value === '' || (string)$value === '0')) {
            return get_string('no');
        }
        return s((string)$value);
    }

    /**
     * Resolve an activity or grade reference within its own course only.
     *
     * @param string $kind Reference type.
     * @param int $value Stored reference.
     * @param int $courseid Owning course.
     * @param \stdClass $row Joined reference names.
     * @return string Reference label with its stored identifier.
     */
    private static function reference(string $kind, int $value, int $courseid, \stdClass $row): string {
        if ($value === 0) {
            return get_string('no');
        }
        $choices = ['datesource' => [1 => 'issueddate', 2 => 'completiondate'], 'gradesource' => [1 => 'coursegrade']];
        if (isset($choices[$kind][$value])) {
            return get_string($choices[$kind][$value], 'iomadcertificate');
        }
        if ($kind === 'gradesource' && $value < 0) {
            $label = $row->settingscategoryname ?? get_string('inventorymissing', 'local_iomadcertregen');
            return get_string('inventorycategoryreference', 'local_iomadcertregen', (object)['name' => $label, 'id' => -$value]);
        }
        if ($kind === 'outcome') {
            $label = $row->settingsoutcomename ?? get_string('inventorymissing', 'local_iomadcertregen');
        } else {
            $label = self::activity_name($courseid, $value);
        }
        return get_string('inventoryreference', 'local_iomadcertregen', (object)['name' => $label, 'id' => $value]);
    }

    /**
     * Find an activity name without following a stored reference into another course.
     *
     * @param int $courseid Owning course.
     * @param int $cmid Referenced course module.
     * @return string Activity name or an explicit missing-reference label.
     */
    private static function activity_name(int $courseid, int $cmid): string {
        $modinfo = get_fast_modinfo($courseid);
        $cm = $modinfo->cms[$cmid] ?? null;
        return $cm && !$cm->deletioninprogress ? $cm->name : get_string('inventorymissing', 'local_iomadcertregen');
    }
}
