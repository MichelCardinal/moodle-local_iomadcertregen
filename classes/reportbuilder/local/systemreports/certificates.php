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

namespace local_iomadcertregen\reportbuilder\local\systemreports;

use core_reportbuilder\local\report\action;
use core_reportbuilder\system_report;
use lang_string;
use local_iomadcertregen\reportbuilder\local\entities\certificate;
use moodle_url;
use pix_icon;

/**
 * Read-only administrative inventory with native filters, pagination and downloads.
 */
final class certificates extends system_report {
    /**
     * Return the inventory title for table captions and exports.
     *
     * @return string Report name.
     */
    public static function get_name(): string {
        return get_string('inventory', 'local_iomadcertregen');
    }

    /**
     * Initialise the live inventory; no certificate or learner record is written.
     */
    protected function initialise(): void {
        $entity = new certificate();
        $this->set_main_table('iomadcertificate', $entity->get_table_alias('iomadcertificate'));
        $this->add_entity($entity);
        $this->add_columns_from_entity('certificate');
        $this->add_filters_from_entity('certificate');
        $cm = $entity->get_table_alias('course_modules');
        $course = $entity->get_table_alias('course');
        $this->add_base_fields("{$cm}.id, {$course}.id AS courseid");
        foreach ($entity->get_joins() as $join) {
            $this->add_join($join);
        }
        $this->set_initial_sort_column('certificate:course', SORT_ASC);
        $this->set_downloadable(true);
        // Native downloads cache this report before adding the format to its report parameters.
        if (optional_param('download', '', PARAM_ALPHA) !== '') {
            return;
        }
        $this->add_action(new action(
            new moodle_url('/course/view.php', ['id' => ':courseid']),
            new pix_icon('i/course', ''),
            [],
            false,
            new lang_string('course')
        ));
        $this->add_action(new action(
            new moodle_url('/course/modedit.php', ['update' => ':id']),
            new pix_icon('t/edit', ''),
            [],
            false,
            new lang_string('editsettings')
        ));
        $this->add_action(new action(
            new moodle_url('/local/iomadcertregen/index.php', ['id' => ':id']),
            new pix_icon('i/search', ''),
            [],
            false,
            new lang_string('issuedcertificates', 'local_iomadcertregen')
        ));
    }

    /**
     * Only site administrators can view or export this inventory.
     *
     * @return bool Only site administrators can view or export this inventory.
     */
    protected function can_view(): bool {
        return $this->get_context()->contextlevel === CONTEXT_SYSTEM && is_siteadmin();
    }
}
