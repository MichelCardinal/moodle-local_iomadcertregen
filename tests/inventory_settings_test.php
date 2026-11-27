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

namespace local_iomadcertregen;

use core_reportbuilder\local\filters\{number, select};
use core_reportbuilder\tests\core_reportbuilder_testcase;
use core_reportbuilder\local\helpers\report as report_helper;
use local_iomadcertregen\reportbuilder\datasource\certificates;
use local_iomadcertregen\local\inventory_settings;

/**
 * Detailed activity settings remain read-only, course-scoped and safely formatted.
 *
 * @covers \local_iomadcertregen\local\inventory_settings
 * @covers \local_iomadcertregen\reportbuilder\local\entities\certificate
 */
#[\PHPUnit\Framework\Attributes\CoversClass(inventory_settings::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\reportbuilder\local\entities\certificate::class)]
final class inventory_settings_test extends core_reportbuilder_testcase {
    /**
     * Two unissued activities in the same fictional course, with an existing default report.
     *
     * @return array Native activities and report.
     */
    private function fixture(): array {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $first = $generator->create_module('iomadcertificate', ['course' => $course->id, 'name' => 'First certificate']);
        $second = $generator->create_module('iomadcertificate', ['course' => $course->id, 'name' => 'Second certificate']);
        $report = $generator->get_plugin_generator('core_reportbuilder')->create_report([
            'name' => 'Fictional inventory', 'source' => certificates::class, 'default' => 1,
        ]);
        return [$first, $second, $report];
    }

    /**
     * Select every native setting together, escaping stored HTML and resolving course-local references.
     */
    public function test_detailed_settings_and_references(): void {
        global $DB;
        [$first, , ] = $this->fixture();
        $course = get_course($first->course);
        $quiz = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'name' => 'Fictional graded work']);
        $category = $this->getDataGenerator()->create_grade_category(['courseid' => $course->id, 'fullname' => 'Fictional grades']);
        $DB->update_record('iomadcertificate', (object)[
            'id' => $first->id, 'printdate' => $quiz->cmid, 'datefmt' => 3,
            'printgrade' => -$category->id, 'gradefmt' => 2, 'delivery' => 2,
            'emailothers' => 'example@example.invalid', 'requiredtime' => 45, 'orientation' => 'P',
            'savecert' => 1, 'printnumber' => 1, 'printteacher' => 1,
            'printsignature' => 'signature-example.png', 'customtext' => '<script>alert(1)</script>',
        ]);
        $report = $this->getDataGenerator()->get_plugin_generator('core_reportbuilder')->create_report([
            'name' => 'Detailed settings', 'source' => certificates::class, 'default' => 0,
        ]);
        $columns = array_keys(inventory_settings::fields());
        $columns = array_merge(['activity'], $columns, ['courseid', 'activityid', 'certificateid', 'dateenabled']);
        foreach ($columns as $name) {
            $added = report_helper::add_report_column($report->get('id'), 'certificate:' . $name);
            if ($name === 'activity') {
                report_helper::toggle_report_column_sorting($report->get('id'), $added->get('id'), true, SORT_ASC);
            }
        }
        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(2, $content);
        $row = array_combine($columns, array_values($content[0]));
        $this->assertSame('First certificate', $row['activity']);
        $this->assertStringContainsString('Fictional graded work', $row['printdate']);
        $this->assertStringContainsString('Fictional grades', $row['printgrade']);
        $this->assertSame(get_string('inventorydateformat3', 'local_iomadcertregen'), $row['datefmt']);
        $this->assertSame(get_string('gradepoints', 'iomadcertificate'), $row['gradefmt']);
        $this->assertSame(get_string('emailiomadcertificate', 'iomadcertificate'), $row['delivery']);
        $this->assertSame(get_string('portrait', 'iomadcertificate'), $row['orientation']);
        $this->assertSame('example@example.invalid', $row['emailothers']);
        $this->assertSame('45', $row['requiredtime']);
        $this->assertSame('signature-example.png', $row['printsignature']);
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $row['customtext']);
        $this->assertSame(get_string('yes'), $row['dateenabled']);
        $this->assertSame((string)$first->cmid, $row['activityid']);
        $this->assertSame((string)$first->id, $row['certificateid']);
        $this->assertSame(0, $DB->count_records('iomadcertificate_issues'));
    }

    /**
     * Native date settings, delivery and time requirements can identify incorrect configurations.
     */
    public function test_detailed_filters_and_unknown_values(): void {
        global $DB;
        [$first, , $report] = $this->fixture();
        $DB->update_record('iomadcertificate', (object)[
            'id' => $first->id, 'printdate' => 1, 'datefmt' => 3, 'requiredtime' => 60, 'delivery' => 2,
        ]);
        $id = $report->get('id');
        foreach (['datefmt', 'dateenabled', 'requiredtime', 'delivery', 'printdate'] as $filter) {
            report_helper::add_report_filter($id, 'certificate:' . $filter);
        }
        $this->assertCount(1, $this->get_custom_report_content($id, 30, [
            'certificate:datefmt_operator' => select::EQUAL_TO, 'certificate:datefmt_value' => 3,
        ]));
        $this->assertCount(1, $this->get_custom_report_content($id, 30, [
            'certificate:dateenabled_operator' => select::EQUAL_TO, 'certificate:dateenabled_value' => 1,
        ]));
        $this->assertCount(1, $this->get_custom_report_content($id, 30, [
            'certificate:requiredtime_operator' => number::GREATER_THAN,
            'certificate:requiredtime_value1' => 30,
        ]));
        $this->assertCount(1, $this->get_custom_report_content($id, 30, [
            'certificate:delivery_operator' => select::EQUAL_TO, 'certificate:delivery_value' => 2,
        ]));
        $this->assertCount(1, $this->get_custom_report_content($id, 30, [
            'certificate:printdate_operator' => number::EQUAL_TO,
            'certificate:printdate_value1' => 1,
        ]));
        $this->assertSame(get_string('no'), inventory_settings::format(
            'printdate',
            'datesource',
            0,
            (object)['settingscourseid' => $first->course]
        ));
        $this->assertSame(
            get_string('completiondate', 'iomadcertificate'),
            inventory_settings::format(
                'printdate',
                'datesource',
                2,
                (object)['settingscourseid' => $first->course]
            )
        );
        $this->assertSame(
            get_string('inventoryunknown', 'local_iomadcertregen', '99'),
            inventory_settings::format('datefmt', 'option', 99, new \stdClass())
        );
        $this->assertSame(
            get_string('inventorynotconfigured', 'local_iomadcertregen'),
            inventory_settings::format('datefmt', 'option', 0, new \stdClass())
        );
    }

    /**
     * An invalid cross-course grade/category reference cannot expose another course's labels.
     */
    public function test_references_are_scoped_to_activity_course(): void {
        global $DB;
        [$first, , ] = $this->fixture();
        $other = $this->getDataGenerator()->create_course();
        $category = $this->getDataGenerator()->create_grade_category([
            'courseid' => $other->id, 'fullname' => 'Other course label',
        ]);
        $DB->set_field('iomadcertificate', 'printgrade', -$category->id, ['id' => $first->id]);
        $report = $this->getDataGenerator()->get_plugin_generator('core_reportbuilder')->create_report([
            'name' => 'Scoped references', 'source' => certificates::class, 'default' => 0,
        ]);
        foreach (['activity', 'printgrade'] as $column) {
            $added = report_helper::add_report_column($report->get('id'), 'certificate:' . $column);
            if ($column === 'activity') {
                report_helper::toggle_report_column_sorting($report->get('id'), $added->get('id'), true, SORT_ASC);
            }
        }
        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertStringContainsString(get_string('inventorymissing', 'local_iomadcertregen'), array_values($content[0])[1]);
        $this->assertStringNotContainsString('Other course label', array_values($content[0])[1]);
    }

    /**
     * Outcome labels resolve only within the certificate course and missing activity references stay explicit.
     */
    public function test_outcome_and_missing_activity_labels(): void {
        global $DB;
        [$first, , ] = $this->fixture();
        $generator = $this->getDataGenerator();
        $scale = $generator->create_scale();
        $outcome = $generator->create_grade_outcome([
            'courseid' => $first->course, 'fullname' => 'Fictional outcome', 'shortname' => 'RC3 outcome',
            'scaleid' => $scale->id,
        ]);
        $item = $generator->create_grade_item([
            'courseid' => $first->course, 'itemname' => 'Fictional outcome item', 'outcomeid' => $outcome->id,
        ]);
        $DB->update_record('iomadcertificate', (object)[
            'id' => $first->id, 'printoutcome' => $item->id, 'printdate' => 9999999,
        ]);
        $report = $generator->get_plugin_generator('core_reportbuilder')->create_report([
            'name' => 'Outcome inventory', 'source' => certificates::class, 'default' => 0,
        ]);
        foreach (['activity', 'printoutcome', 'printdate'] as $column) {
            $added = report_helper::add_report_column($report->get('id'), 'certificate:' . $column);
            if ($column === 'activity') {
                report_helper::toggle_report_column_sorting($report->get('id'), $added->get('id'), true, SORT_ASC);
            }
        }
        $rows = $this->get_custom_report_content($report->get('id'));
        $firstrow = array_values($rows[0]);
        $this->assertStringContainsString('Fictional outcome item', $firstrow[1]);
        $this->assertStringContainsString(get_string('inventorymissing', 'local_iomadcertregen'), $firstrow[2]);
        $this->assertStringContainsString('9999999', $firstrow[2]);
        $this->assertSame(0, $DB->count_records('iomadcertificate_issues'));
    }
}
