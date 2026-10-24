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
 * Back up confirmed administrative copies only for authorised site administrators.
 */
class backup_local_iomadcertregen_plugin extends backup_local_plugin {
    /**
     * Attach confirmed copies to their native certificate course module.
     *
     * @return backup_plugin_element|null Backup structure, if authorised.
     */
    protected function define_module_plugin_structure() {
        global $DB;
        $ownerid = $DB->get_field('backup_controllers', 'userid', ['backupid' => $this->task->get_backupid()], MUST_EXIST);
        if (
            $this->task->get_modulename() !== 'iomadcertificate' ||
                !$this->get_setting_value('userinfo') || !is_siteadmin($ownerid)
        ) {
            return null;
        }
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $copy = new backup_nested_element('copy', ['id'], [
            'archivepolicyversion', 'issueid', 'userid', 'actorid', 'sourcehash', 'pdfhash',
            'timecreated', 'timemodified', 'timeexpires', 'issuecode', 'issuedate', 'mailstatus', 'mailtime',
            'keeporiginal', 'originalexpires', 'correctiontime', 'correctionreason',
        ]);
        $plugin->add_child($wrapper);
        $wrapper->add_child($copy);
        $copy->set_source_sql(
            "SELECT o.id, o.issueid, o.userid, o.actorid, o.sourcehash, o.pdfhash,
                    o.timecreated, o.timemodified, o.timeexpires, o.mailstatus, o.mailtime,
                    o.keeporiginal, o.originalexpires, o.correctiontime, o.correctionreason, 1 AS archivepolicyversion,
                    i.code AS issuecode, i.timecreated AS issuedate
               FROM {local_iomadcertregen_op} o
               JOIN {course_modules} cm ON cm.id = o.cmid
               JOIN {iomadcertificate_issues} i ON i.id = o.issueid AND i.iomadcertificateid = cm.instance
              WHERE o.cmid = ? AND o.contextid = ? AND o.status = ? AND o.userid = i.userid
                AND (o.timeexpires = 0 OR o.timeexpires > ?)
              ORDER BY o.id",
            [backup::VAR_MODID, backup::VAR_CONTEXTID, ['sqlparam' => 'confirmed'], ['sqlparam' => time()]]
        );
        $copy->annotate_ids('user', 'userid');
        $copy->annotate_ids('user', 'actorid');
        $copy->annotate_files('local_iomadcertregen', 'document', 'id');
        $copy->annotate_files('local_iomadcertregen', 'original', 'id');
        return $plugin;
    }
}
