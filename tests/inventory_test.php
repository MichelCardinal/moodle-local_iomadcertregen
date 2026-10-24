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

use core_reportbuilder\local\filters\{select, text};
use core_reportbuilder\tests\core_reportbuilder_testcase;
use local_iomadcertregen\reportbuilder\datasource\certificates;

/**
 * Live inventory coverage, filters and access independent of company and issues.
 *
 * @covers \local_iomadcertregen\reportbuilder\datasource\certificates
 * @covers \local_iomadcertregen\reportbuilder\local\entities\certificate
 * @covers \local_iomadcertregen\reportbuilder\local\systemreports\certificates
 */
#[\PHPUnit\Framework\Attributes\CoversClass(certificates::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\reportbuilder\local\entities\certificate::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\reportbuilder\local\systemreports\certificates::class)]
final class inventory_test extends core_reportbuilder_testcase {
    /**
     * Two activities in a hidden course without company links or issues, and a custom report.
     *
     * @return array Two activities in a hidden course without company links or issues, and a custom report.
     */
    private function fixture(): array {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $category = $generator->create_category(['name' => 'Fictional category']);
        $course = $generator->create_course(['fullname' => 'Fictional course', 'category' => $category->id, 'visible' => 0]);
        $first = $generator->create_module('iomadcertificate', ['course' => $course->id, 'name' => 'First certificate',
            'iomadcertificatetype' => 'A4_non_embedded']);
        $second = $generator->create_module('iomadcertificate', ['course' => $course->id, 'name' => 'Second certificate',
            'iomadcertificatetype' => 'letter_non_embedded', 'visible' => 0]);
        $generator->create_module('page', ['course' => $course->id]);
        $report = $generator->get_plugin_generator('core_reportbuilder')->create_report([
            'name' => 'Fictional inventory', 'source' => certificates::class, 'default' => 1,
        ]);
        return [$first, $second, $report];
    }

    /**
     * Hidden courses and multiple activities appear once each without requiring learner issues.
     */
    public function test_inventory_covers_activities_without_issues(): void {
        global $DB;
        [, , $report] = $this->fixture();
        $content = array_map('array_values', $this->get_custom_report_content($report->get('id')));
        $this->assertCount(2, $content);
        $this->assertSame(['Fictional category', 'Fictional course'], array_slice($content[0], 0, 2));
        $this->assertSame('A4_non_embedded', $content[0][4]);
        $this->assertSame(get_string('no'), $content[0][5]);
        $this->assertSame('letter_non_embedded', $content[1][4]);
        $this->assertSame(get_string('no'), $content[1][6]);
        $this->assertSame(0, $DB->count_records('iomadcertificate_issues'));
        $this->assertSame(0, $DB->count_records('local_iomadcertregen_op'));
    }

    /**
     * Model filtering, literal wildcard search and visibility filtering use native report semantics.
     */
    public function test_inventory_filters(): void {
        [, , $report] = $this->fixture();
        $id = $report->get('id');
        $content = $this->get_custom_report_content($id, 30, [
            'certificate:template_operator' => text::IS_EQUAL_TO, 'certificate:template_value' => 'letter_non_embedded',
        ]);
        $this->assertCount(1, $content);
        $this->assertSame('Second certificate', array_values($content[0])[3]);
        $this->assertCount(0, $this->get_custom_report_content($id, 30, [
            'certificate:course_operator' => text::CONTAINS, 'certificate:course_value' => '%',
        ]));
        $this->assertCount(1, $this->get_custom_report_content($id, 30, [
            'certificate:activityvisible_operator' => select::EQUAL_TO, 'certificate:activityvisible_value' => 0,
        ]));
    }

    /**
     * The report is live, shows unknown template identifiers and excludes deleted activities.
     */
    public function test_current_configuration_not_historical_pdf(): void {
        global $DB;
        [$first, $second, $report] = $this->fixture();
        $DB->set_field('iomadcertificate', 'iomadcertificatetype', 'missing_custom_template', ['id' => $first->id]);
        $DB->set_field('course_modules', 'deletioninprogress', 1, ['id' => $second->cmid]);
        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(1, $content);
        $this->assertSame('missing_custom_template', array_values($content[0])[4]);
        $this->assertCount(1, $this->get_custom_report_content($report->get('id'), 1));
    }

    /**
     * Identically configured activities remain distinct rows in the default inventory.
     */
    public function test_identical_activities_are_not_collapsed(): void {
        [$first, , $report] = $this->fixture();
        $this->getDataGenerator()->create_module('iomadcertificate', [
            'course' => $first->course, 'name' => $first->name,
            'iomadcertificatetype' => $first->iomadcertificatetype,
        ]);
        $this->assertCount(3, $this->get_custom_report_content($report->get('id')));
        $this->assertCount(1, $this->get_custom_report_content($report->get('id'), 1));
    }

    /**
     * All selectable columns work independently, as in Moodle's report-builder source tests.
     */
    public function test_independent_columns(): void {
        $this->fixture();
        $this->datasource_stress_test_columns(certificates::class);
    }

    /**
     * Source discovery and execution stay restricted even if a custom report is shared.
     */
    public function test_non_administrator_cannot_read_shared_source(): void {
        [, , $report] = $this->fixture();
        $this->assertTrue(certificates::is_available());
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->get_plugin_generator('core_reportbuilder')->create_audience([
            'reportid' => $report->get('id'), 'classname' => \core_reportbuilder\reportbuilder\audience\allusers::class,
            'configdata' => [],
        ]);
        $this->setUser($user);
        $this->assertFalse(certificates::is_available());
        $this->expectException(\moodle_exception::class);
        $this->get_custom_report_content($report->get('id'));
    }

    /**
     * System report and its download use the same administrator-only access check.
     */
    public function test_system_report_denies_non_administrator(): void {
        $this->fixture();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\core_reportbuilder\exception\report_access_exception::class);
        \core_reportbuilder\system_report_factory::create(
            \local_iomadcertregen\reportbuilder\local\systemreports\certificates::class,
            \context_system::instance()
        );
    }

    /**
     * System report exposes native export and actions for administrators.
     */
    public function test_system_report_has_export_and_actions(): void {
        $this->fixture();
        $report = \core_reportbuilder\system_report_factory::create(
            \local_iomadcertregen\reportbuilder\local\systemreports\certificates::class,
            \context_system::instance()
        );
        $this->assertTrue($report->can_be_downloaded());
        $this->assertTrue($report->has_actions());
    }
}
