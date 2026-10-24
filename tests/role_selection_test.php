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

use local_iomadcertregen\local\{access, role_permissions};

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/certificate_testcase.php');

/**
 * The administration shortcut edits only the native plugin capability.
 *
 * @covers \local_iomadcertregen\local\role_permissions
 */
#[\PHPUnit\Framework\Attributes\CoversClass(role_permissions::class)]
final class role_selection_test extends certificate_testcase {
    /**
     * One selection updates native grants while preserving refusals, assignments and other permissions.
     */
    public function test_multiple_role_selection(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $first = $this->getDataGenerator()->create_role();
        $second = $this->getDataGenerator()->create_role();
        $third = $this->getDataGenerator()->create_role();
        $system = \context_system::instance();
        assign_capability(role_permissions::CAPABILITY, CAP_ALLOW, $first, $system);
        assign_capability(role_permissions::CAPABILITY, CAP_PROHIBIT, $third, $system);
        assign_capability('moodle/course:view', CAP_ALLOW, $first, $system);
        $assignments = $DB->get_records('role_assignments', [], 'id');
        role_permissions::save_selection([$second], role_permissions::selection_revision(), sesskey());
        $this->assertSame(CAP_INHERIT, role_permissions::permission($first));
        $this->assertSame(CAP_ALLOW, role_permissions::permission($second));
        $this->assertSame(CAP_PROHIBIT, role_permissions::permission($third));
        $this->assertEquals($assignments, $DB->get_records('role_assignments', [], 'id'));
        $this->assertEquals(CAP_ALLOW, $DB->get_field('role_capabilities', 'permission', [
            'roleid' => $first, 'capability' => 'moodle/course:view', 'contextid' => $system->id,
        ]));
        role_permissions::save_selection([], role_permissions::selection_revision(), sesskey());
        $this->assertSame(CAP_INHERIT, role_permissions::permission($second));
        $this->assertSame(CAP_PROHIBIT, role_permissions::permission($third));
        $this->assertTrue(is_siteadmin());
    }

    /**
     * Unknown IDs are rejected before the selection changes any role.
     */
    public function test_invalid_role_selection_writes_nothing(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $role = $this->getDataGenerator()->create_role();
        $revision = role_permissions::selection_revision();
        $this->expectException(\moodle_exception::class);
        try {
            role_permissions::save_selection([$role, -1], $revision, sesskey());
        } finally {
            $this->assertSame($revision, role_permissions::selection_revision());
        }
    }

    /**
     * A concurrent native role change invalidates the entire reviewed selection.
     */
    public function test_stale_role_selection_is_refused(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $role = $this->getDataGenerator()->create_role();
        $revision = role_permissions::selection_revision();
        assign_capability(role_permissions::CAPABILITY, CAP_ALLOW, $role, \context_system::instance());
        $this->expectException(\moodle_exception::class);
        try {
            role_permissions::save_selection([], $revision, sesskey());
        } finally {
            $this->assertSame(CAP_ALLOW, role_permissions::permission($role));
        }
    }

    /**
     * Later UI upgrades must not restore a manager grant the administrator removed.
     */
    public function test_ui_upgrade_preserves_deselected_manager(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/local/iomadcertregen/db/upgrade.php');
        $role = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        unassign_capability(role_permissions::CAPABILITY, $role, \context_system::instance());
        set_config('version', 2026102408, 'local_iomadcertregen');
        $this->assertTrue(xmldb_local_iomadcertregen_upgrade(2026102408));
        $this->assertSame(CAP_INHERIT, role_permissions::permission($role));
    }
    /**
     * The multiple-role service requires a valid session token independently of its form.
     */
    public function test_selection_rejects_invalid_session(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $revision = role_permissions::selection_revision();
        $this->expectException(\moodle_exception::class);
        role_permissions::save_selection([], $revision, 'invalid');
    }

    /**
     * A nonadministrator cannot use the multiple-role service.
     */
    public function test_selection_rejects_nonadministrator(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $revision = role_permissions::selection_revision();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        role_permissions::save_selection([], $revision, sesskey());
    }
}
