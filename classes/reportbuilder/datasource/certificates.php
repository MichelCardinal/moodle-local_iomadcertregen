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

namespace local_iomadcertregen\reportbuilder\datasource;

use core_reportbuilder\datasource;
use local_iomadcertregen\reportbuilder\local\entities\certificate;

/**
 * Administrator-only custom report source for current certificate configuration.
 */
final class certificates extends datasource {
    /**
     * Source name in Moodle custom reports.
     *
     * @return string Source name in Moodle custom reports.
     */
    public static function get_name(): string {
        return get_string('inventory', 'local_iomadcertregen');
    }

    /**
     * Only site administrators can select this source.
     *
     * @return bool Only site administrators can select this source.
     */
    public static function is_available(): bool {
        return is_siteadmin();
    }

    /**
     * Initialise a read-only source; an audience never grants access to site-wide configuration.
     */
    protected function initialise(): void {
        $entity = new certificate();
        $this->set_main_table('iomadcertificate', $entity->get_table_alias('iomadcertificate'));
        $this->add_entity($entity);
        $this->add_all_from_entities();
        // Also protect execution of existing reports, exports and scheduled runs for non-administrators.
        $this->add_base_condition_sql(is_siteadmin() ? '1 = 1' : '1 = 0');
    }

    /**
     * Initial columns.
     *
     * @return string[] Initial columns.
     */
    public function get_default_columns(): array {
        return ['certificate:category', 'certificate:course', 'certificate:shortname', 'certificate:activity',
            'certificate:template', 'certificate:coursevisible', 'certificate:activityvisible'];
    }

    /**
     * Initial filters.
     *
     * @return string[] Initial filters.
     */
    public function get_default_filters(): array {
        return $this->get_default_columns();
    }

    /**
     * Initial conditions.
     *
     * @return string[] Initial conditions.
     */
    public function get_default_conditions(): array {
        return [];
    }

    /**
     * Stable default sorting.
     *
     * @return int[] Stable default sorting.
     */
    public function get_default_column_sorting(): array {
        return ['certificate:course' => SORT_ASC, 'certificate:activity' => SORT_ASC];
    }
}
