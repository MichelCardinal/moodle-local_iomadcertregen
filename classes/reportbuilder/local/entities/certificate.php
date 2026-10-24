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

namespace local_iomadcertregen\reportbuilder\local\entities;

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\{select, text};
use core_reportbuilder\local\report\{column, filter};
use lang_string;

/**
 * Current activity configuration, independent of issued PDFs and company membership.
 */
final class certificate extends base {
    /**
     * Tables used by this entity.
     *
     * @return string[] Tables used by this entity.
     */
    protected function get_default_tables(): array {
        return ['iomadcertificate', 'course_modules', 'course', 'course_categories'];
    }

    /**
     * Entity title.
     *
     * @return lang_string Entity title.
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('inventory', 'local_iomadcertregen');
    }

    /**
     * Initialise the live configuration joins and report elements.
     *
     * @return self Initialise the live configuration joins and report elements.
     */
    public function initialise(): self {
        $ic = $this->get_table_alias('iomadcertificate');
        $cm = $this->get_table_alias('course_modules');
        $course = $this->get_table_alias('course');
        $category = $this->get_table_alias('course_categories');
        $this->add_join("JOIN {course_modules} {$cm} ON {$cm}.instance = {$ic}.id
            AND {$cm}.deletioninprogress = 0
            AND {$cm}.module = (SELECT id FROM {modules} WHERE name = 'iomadcertificate')");
        $this->add_join("JOIN {course} {$course} ON {$course}.id = {$cm}.course");
        $this->add_join("JOIN {course_categories} {$category} ON {$category}.id = {$course}.category");
        foreach ($this->get_available_columns() as $column) {
            $this->add_column($column);
        }
        foreach ($this->get_available_filters() as $filter) {
            $this->add_filter($filter);
            $this->add_condition(clone $filter);
        }
        return $this;
    }

    /**
     * Column identifiers, SQL fields and language titles.
     *
     * @return array Column identifiers, SQL fields and language titles.
     */
    private function fields(): array {
        $ic = $this->get_table_alias('iomadcertificate');
        $cm = $this->get_table_alias('course_modules');
        $course = $this->get_table_alias('course');
        $category = $this->get_table_alias('course_categories');
        return [
            'category' => ["{$category}.name", new lang_string('category')],
            'course' => ["{$course}.fullname", new lang_string('course')],
            'shortname' => ["{$course}.shortname", new lang_string('shortnamecourse')],
            'activity' => ["{$ic}.name", new lang_string('activity')],
            'template' => ["{$ic}.iomadcertificatetype", new lang_string('templateidentifier', 'local_iomadcertregen')],
            'coursevisible' => ["{$course}.visible", new lang_string('coursevisible', 'local_iomadcertregen')],
            'activityvisible' => ["{$cm}.visible", new lang_string('activityvisible', 'local_iomadcertregen')],
        ];
    }

    /**
     * Columns for both the system report and custom reports.
     *
     * @return column[] Columns for both the system report and custom reports.
     */
    protected function get_available_columns(): array {
        $columns = [];
        foreach ($this->fields() as $name => [$field, $title]) {
            $visible = in_array($name, ['coursevisible', 'activityvisible'], true);
            $columns[] = (new column($name, $title, $this->get_entity_name()))
                ->add_joins($this->get_joins())
                ->set_type($visible ? column::TYPE_BOOLEAN : column::TYPE_TEXT)
                ->add_field($field)
                ->set_is_sortable(true)
                ->add_callback(static function ($value) use ($visible): string {
                    return $visible ? get_string($value ? 'yes' : 'no') : s((string)$value);
                });
        }
        return $columns;
    }

    /**
     * Searchable current configuration, without learner data.
     *
     * @return filter[] Searchable current configuration, without learner data.
     */
    protected function get_available_filters(): array {
        $filters = [];
        foreach ($this->fields() as $name => [$field, $title]) {
            $visible = in_array($name, ['coursevisible', 'activityvisible'], true);
            $filter = (new filter(
                $visible ? select::class : text::class,
                $name,
                $title,
                $this->get_entity_name(),
                $field
            ))->add_joins($this->get_joins());
            if ($visible) {
                $filter->set_options([0 => get_string('no'), 1 => get_string('yes')]);
            }
            $filters[] = $filter;
        }
        return $filters;
    }
}
