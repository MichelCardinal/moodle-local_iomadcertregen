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
    // Earlier alpha upgrades were metadata-only savepoints, with no schema or deadline transformations.
    if ($oldversion < 2026102309) {
        upgrade_plugin_savepoint(true, 2026102309, 'local', 'iomadcertregen');
    }
    if ($oldversion < 2026102310) {
        xmldb_local_iomadcertregen_upgrade_mail();
        upgrade_plugin_savepoint(true, 2026102310, 'local', 'iomadcertregen');
    }
    if ($oldversion < 2026102400) {
        xmldb_local_iomadcertregen_upgrade_publication();
        upgrade_plugin_savepoint(true, 2026102400, 'local', 'iomadcertregen');
    }
    if ($oldversion < 2026102404) {
        xmldb_local_iomadcertregen_upgrade_archives();
        upgrade_plugin_savepoint(true, 2026102404, 'local', 'iomadcertregen');
    }
    if ($oldversion < 2026102405) {
        xmldb_local_iomadcertregen_upgrade_managers();
        upgrade_plugin_savepoint(true, 2026102405, 'local', 'iomadcertregen');
    }
    if ($oldversion < 2026102409) {
        upgrade_plugin_savepoint(true, 2026102409, 'local', 'iomadcertregen');
    }
    return true;
}

/**
 * Add archive policy metadata without changing previously retained files or deadlines.
 */
function xmldb_local_iomadcertregen_upgrade_archives(): void {
    global $DB;
    $manager = $DB->get_manager();
    $table = new xmldb_table('local_iomadcertregen_op');
    foreach (
        [
        new xmldb_field('keeporiginal', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1'),
        new xmldb_field('originalexpires', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
        new xmldb_field('correctiontime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
        new xmldb_field('correctionreason', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, ''),
        ] as $field
    ) {
        if (!$manager->field_exists($table, $field)) {
            $manager->add_field($table, $field);
        }
    }
    $DB->execute('UPDATE {local_iomadcertregen_op} SET correctiontime = publishedtime WHERE publishedtime > 0');
}

/**
 * Give existing Moodle manager archetypes the new default without replacing explicit decisions.
 */
function xmldb_local_iomadcertregen_upgrade_managers(): void {
    global $DB;
    $system = context_system::instance();
    foreach ($DB->get_records('role', ['archetype' => 'manager']) as $role) {
        if (
            !$DB->record_exists('role_capabilities', [
            'roleid' => $role->id, 'contextid' => $system->id, 'capability' => 'local/iomadcertregen:regenerate',
            ])
        ) {
            assign_capability('local/iomadcertregen:regenerate', CAP_ALLOW, $role->id, $system->id);
        }
    }
}

/**
 * Add the original corrected-mail metadata without rewriting stored records.
 */
function xmldb_local_iomadcertregen_upgrade_mail(): void {
    global $DB;
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
}

/**
 * Add the original native-publication metadata without rewriting stored records.
 */
function xmldb_local_iomadcertregen_upgrade_publication(): void {
    global $DB;
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
}
