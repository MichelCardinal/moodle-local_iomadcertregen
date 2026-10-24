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

/**
 * Register new plugin versions without changing existing correction deadlines.
 *
 * @param int $oldversion Previous installed version.
 * @return bool Success.
 */
function xmldb_local_iomadcertregen_upgrade(int $oldversion): bool {
    global $DB;
    // Earlier alpha upgrades were metadata-only savepoints, with no schema or deadline transformations.
    if ($oldversion < 2026102309) {
        upgrade_plugin_savepoint(true, 2026102309, 'local', 'iomadcertregen');
    }
    if ($oldversion < 2026102310) {
        $manager = $DB->get_manager();
        $table = new xmldb_table('local_iomadcertregen_op');
        $fields = [
            new xmldb_field('mailstatus', XMLDB_TYPE_CHAR, '12', null, XMLDB_NOTNULL, null, 'notrequested'),
            new xmldb_field('mailtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
            new xmldb_field('mailrequestkey', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, ''),
        ];
        foreach ($fields as $field) {
            if (!$manager->field_exists($table, $field)) {
                $manager->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2026102310, 'local', 'iomadcertregen');
    }
    if ($oldversion < 2026102400) {
        $manager = $DB->get_manager();
        $table = new xmldb_table('local_iomadcertregen_op');
        foreach (
            [
            new xmldb_field('publishedtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
            new xmldb_field('issuehash', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, ''),
            new xmldb_field('batchkey', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, ''),
            ] as $field
        ) {
            if (!$manager->field_exists($table, $field)) {
                $manager->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2026102400, 'local', 'iomadcertregen');
    }
    return true;
}
