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
use core_reportbuilder\local\filters\{date, number, select, text};
use local_iomadcertregen\local\inventory_settings;
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
        return ['iomadcertificate', 'course_modules', 'course', 'course_categories', 'grade_categories', 'grade_items'];
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
        $gradecategory = $this->get_table_alias('grade_categories');
        $outcome = $this->get_table_alias('grade_items');
        $this->add_join("LEFT JOIN {grade_categories} {$gradecategory}
            ON {$gradecategory}.id = -{$ic}.printgrade AND {$gradecategory}.courseid = {$course}.id");
        $this->add_join("LEFT JOIN {grade_items} {$outcome}
            ON {$outcome}.id = {$ic}.printoutcome AND {$outcome}.courseid = {$course}.id AND {$outcome}.outcomeid IS NOT NULL");
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
        $fields = [
            'category' => ["{$category}.name", new lang_string('category'), 'text'],
            'course' => ["{$course}.fullname", new lang_string('course'), 'text'],
            'shortname' => ["{$course}.shortname", new lang_string('shortnamecourse'), 'text'],
            'activity' => ["{$ic}.name", new lang_string('activity'), 'text'],
            'template' => ["{$ic}.iomadcertificatetype", new lang_string('templateidentifier', 'local_iomadcertregen'), 'text'],
            'coursevisible' => ["{$course}.visible", new lang_string('coursevisible', 'local_iomadcertregen'), 'boolean'],
            'activityvisible' => ["{$cm}.visible", new lang_string('activityvisible', 'local_iomadcertregen'), 'boolean'],
        ];
        foreach (inventory_settings::fields() as $name => [$title, $kind]) {
            $fields[$name] = ["{$ic}.{$name}", new lang_string($title, 'local_iomadcertregen'), $kind];
        }
        foreach (['courseid' => "{$course}.id", 'activityid' => "{$cm}.id", 'certificateid' => "{$ic}.id"] as $name => $sql) {
            $fields[$name] = [$sql, new lang_string('inventory' . $name, 'local_iomadcertregen'), 'integer'];
        }
        $fields['dateenabled'] = ["CASE WHEN {$ic}.printdate > 0 THEN 1 ELSE 0 END",
            new lang_string('inventorydateenabled', 'local_iomadcertregen'), 'boolean'];
        return $fields;
    }

    /**
     * Columns for both the system report and custom reports.
     *
     * @return column[] Columns for both the system report and custom reports.
     */
    protected function get_available_columns(): array {
        $columns = [];
        foreach ($this->fields() as $name => [$field, $title, $kind]) {
            $column = (new column($name, $title, $this->get_entity_name()))
                ->add_joins($this->get_joins())
                ->set_type(inventory_settings::column_type($kind))
                ->add_field($field, $name)
                ->set_is_sortable($kind !== 'longtext');
            if (in_array($kind, ['datesource', 'gradesource', 'outcome'], true)) {
                $ic = $this->get_table_alias('iomadcertificate');
                $column->add_field("{$ic}.course", 'settingscourseid');
            }
            if ($kind === 'gradesource') {
                $category = $this->get_table_alias('grade_categories');
                $column->add_field("{$category}.fullname", 'settingscategoryname');
            } else if ($kind === 'outcome') {
                $item = $this->get_table_alias('grade_items');
                $column->add_field("{$item}.itemname", 'settingsoutcomename');
            }
            $column->add_callback(static function ($value, \stdClass $row) use ($name, $kind): string {
                return inventory_settings::format($name, $kind, $value, $row);
            });
            $columns[] = $column;
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
        foreach ($this->fields() as $name => [$field, $title, $kind]) {
            $options = inventory_settings::options($name, $kind);
            $class = $options ? select::class : text::class;
            if (in_array($kind, ['integer', 'datesource', 'gradesource', 'outcome'], true)) {
                $class = number::class;
            } else if ($kind === 'timestamp') {
                $class = date::class;
            }
            $filter = (new filter($class, $name, $title, $this->get_entity_name(), $field))
                ->add_joins($this->get_joins());
            if ($options) {
                $filter->set_options($options);
            }
            $filters[] = $filter;
        }
        return $filters;
    }
}
