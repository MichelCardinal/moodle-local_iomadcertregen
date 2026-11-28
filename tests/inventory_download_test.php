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

use core_reportbuilder\manager;
use core_reportbuilder\system_report_factory;
use local_iomadcertregen\local\inventory_download;
use local_iomadcertregen\reportbuilder\local\systemreports\certificates;

/**
 * Native table preferences must be ready before export, without changing report access.
 *
 * @covers \local_iomadcertregen\local\inventory_download
 */
#[\PHPUnit\Framework\Attributes\CoversClass(inventory_download::class)]
final class inventory_download_test extends \advanced_testcase {
    /**
     * Create this administrator-only native system inventory.
     *
     * @return int Native report ID.
     */
    private function fixture(): int {
        $this->resetAfterTest();
        $this->setAdminUser();
        $report = system_report_factory::create(certificates::class, \context_system::instance());
        return $report->get_report_persistent()->get('id');
    }

    /**
     * A direct first export prepares native defaults without opening an output writer.
     */
    public function test_default_export_preparation_is_silent_and_idempotent(): void {
        global $SESSION;
        $id = $this->fixture();
        $key = 'system-report-table-' . $id;
        $this->assertArrayNotHasKey($key, $SESSION->flextable ?? []);
        $this->expectOutputString('');
        $this->assertTrue(inventory_download::prepare($id, ['download' => 'csv']));
        $preferences = $SESSION->flextable[$key];
        $this->assertNotEmpty($preferences['sortby']);
        $this->assertTrue(inventory_download::prepare($id, []));
        $this->assertSame($preferences, $SESSION->flextable[$key]);
    }

    /**
     * Existing native sort choices are preserved instead of being discarded for a download.
     */
    public function test_selected_sort_survives_export_preparation(): void {
        global $SESSION;
        $id = $this->fixture();
        $this->assertTrue(inventory_download::prepare($id, []));
        $column = manager::get_report_from_id($id)->get_column('certificate:activity')->get_column_alias();
        $get = $_GET;
        try {
            $_GET['tsort'] = $column;
            $_GET['tdir'] = SORT_DESC;
            $this->assertTrue(inventory_download::prepare($id, []));
            $key = 'system-report-table-' . $id;
            $preferences = $SESSION->flextable[$key];
            $this->assertSame(SORT_DESC, $preferences['sortby'][$column]);
            $_GET = $get;
            $this->assertTrue(inventory_download::prepare($id, []));
            $this->assertSame($preferences, $SESSION->flextable[$key]);
        } finally {
            $_GET = $get;
        }
    }

    /**
     * This callback cannot grant an authenticated ordinary user inventory access.
     */
    public function test_ordinary_users_cannot_prepare_inventory_exports(): void {
        global $SESSION;
        $id = $this->fixture();
        $this->setUser($this->getDataGenerator()->create_user());
        $before = serialize($SESSION);
        $this->assertFalse(inventory_download::prepare($id, []));
        $this->assertSame($before, serialize($SESSION));
    }

    /**
     * Custom reports, missing IDs and other native sources keep their own download path.
     */
    public function test_custom_and_unrelated_reports_are_untouched(): void {
        global $SESSION;
        $this->fixture();
        $custom = $this->getDataGenerator()->get_plugin_generator('core_reportbuilder')->create_report([
            'name' => 'Fictional custom inventory',
            'source' => \local_iomadcertregen\reportbuilder\datasource\certificates::class,
            'default' => 1,
        ]);
        $before = serialize($SESSION);
        $this->assertFalse(inventory_download::prepare($custom->get('id'), []));
        $this->assertFalse(inventory_download::prepare(0, []));
        $this->assertSame($before, serialize($SESSION));
    }
}
