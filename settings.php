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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category(
        'local_iomadcertregen_category',
        get_string('pluginname', 'local_iomadcertregen')
    ));
    $ADMIN->add('local_iomadcertregen_category', new admin_externalpage(
        'local_iomadcertregen',
        get_string('inventory', 'local_iomadcertregen'),
        new moodle_url('/local/iomadcertregen/index.php')
    ));
    $ADMIN->add('local_iomadcertregen_category', new admin_externalpage(
        'local_iomadcertregen_permissions',
        get_string('accesspermissions', 'local_iomadcertregen'),
        new moodle_url('/local/iomadcertregen/permissions.php')
    ));
    $settings = new admin_settingpage(
        'local_iomadcertregen_retention',
        get_string('retentionsettings', 'local_iomadcertregen')
    );
    $ADMIN->add('local_iomadcertregen_category', $settings);
    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configselect(
            'local_iomadcertregen/archivepolicy',
            get_string('archivepolicysetting', 'local_iomadcertregen'),
            get_string('archivepolicysetting_desc', 'local_iomadcertregen'),
            'choice',
            [
                'choice' => get_string('archivepolicychoice', 'local_iomadcertregen'),
                'keep' => get_string('archivepolicykeep', 'local_iomadcertregen'),
                'discard' => get_string('archivepolicydiscard', 'local_iomadcertregen'),
            ]
        ));
        $settings->add(new admin_setting_configduration(
            'local_iomadcertregen/archivelifetime',
            get_string('archivelifetime', 'local_iomadcertregen'),
            get_string('archivelifetime_desc', 'local_iomadcertregen'),
            0
        ));
        $preparation = new admin_setting_configduration(
            'local_iomadcertregen/preparationlifetime',
            get_string('preparationlifetime', 'local_iomadcertregen'),
            get_string('preparationlifetime_desc', 'local_iomadcertregen'),
            DAYSECS
        );
        $preparation->set_min_duration(MINSECS);
        $settings->add($preparation);
        $settings->add(new admin_setting_configduration(
            'local_iomadcertregen/confirmedlifetime',
            get_string('confirmedlifetime', 'local_iomadcertregen'),
            get_string('confirmedlifetime_desc', 'local_iomadcertregen'),
            0
        ));
    }
}
