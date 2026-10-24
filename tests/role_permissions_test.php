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
final class role_permissions_test extends certificate_testcase {
    /**
     * Custom roles can be granted and revoked without changing assignments or native permissions.
     */
    public function test_custom_role_permission_and_scope(): void {
        global $DB;
        $data = $this->fixture();
        $role = $this->getDataGenerator()->create_role(['name' => 'Example certificate editor']);
        $user = $this->getDataGenerator()->create_user();
        $coursecontext = \context_course::instance($data[3]->id);
        role_assign($role, $user->id, $coursecontext->id);
        assign_capability('mod/iomadcertificate:manage', CAP_ALLOW, $role, $coursecontext);
        $assignments = $DB->get_records('role_assignments', ['roleid' => $role]);
        $this->assertArrayHasKey($role, role_permissions::roles());
        $sink = $this->redirectEvents();
        role_permissions::save($role, CAP_ALLOW, role_permissions::revision($role), sesskey());
        $events = $sink->get_events();
        $sink->close();
        $this->assertInstanceOf(\core\event\capability_assigned::class, end($events));
        $this->assertEquals($assignments, $DB->get_records('role_assignments', ['roleid' => $role]));
        $this->setUser($user);
        $this->assertTrue(access::allowed($data[5]));
        $this->assertFalse(has_capability('local/iomadcertregen:regenerate', \context_system::instance()));
        $this->setAdminUser();
        role_permissions::save($role, CAP_INHERIT, role_permissions::revision($role), sesskey());
        $this->setUser($user);
        $this->assertFalse(access::allowed($data[5]));
        $this->assertTrue(has_capability('mod/iomadcertificate:manage', $data[5]));
    }

    /**
     * A lower-context prohibition is preserved when the role definition is allowed.
     */
    public function test_local_prohibition_is_preserved(): void {
        $data = $this->fixture();
        $role = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        role_assign($role, $user->id, \context_course::instance($data[3]->id));
        assign_capability('mod/iomadcertificate:manage', CAP_ALLOW, $role, $data[5]);
        assign_capability('local/iomadcertregen:regenerate', CAP_PROHIBIT, $role, $data[5]);
        role_permissions::save($role, CAP_ALLOW, role_permissions::revision($role), sesskey());
        $this->setUser($user);
        $this->assertFalse(access::allowed($data[5]));
    }

    /**
     * A stale form never overwrites a more recent administrator decision.
     */
    public function test_stale_form_is_refused(): void {
        $this->fixture();
        $role = $this->getDataGenerator()->create_role();
        $revision = role_permissions::revision($role);
        role_permissions::save($role, CAP_ALLOW, $revision, sesskey());
        $this->expectException(\moodle_exception::class);
        role_permissions::save($role, CAP_PREVENT, $revision, sesskey());
    }

    /**
     * The service independently rejects a forged session token.
     */
    public function test_csrf_is_refused(): void {
        $this->fixture();
        $role = $this->getDataGenerator()->create_role();
        $this->expectException(\moodle_exception::class);
        role_permissions::save($role, CAP_ALLOW, role_permissions::revision($role), 'invalid');
    }

    /**
     * A course manager cannot grant plugin permissions to roles.
     */
    public function test_nonadmin_cannot_edit_role_permissions(): void {
        global $DB;
        $this->fixture();
        $role = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        $user = $this->getDataGenerator()->create_user();
        role_assign($role, $user->id, \context_system::instance()->id);
        $revision = role_permissions::revision($role);
        $this->setUser($user);
        $this->expectException(\required_capability_exception::class);
        role_permissions::save($role, CAP_ALLOW, $revision, sesskey());
    }
    /**
     * The simple choice changes only the grant and preserves refusals when access remains off.
     *
     * @dataProvider access_choices
     * @param int $current Native role definition.
     * @param int $allow Simple submitted choice.
     * @param int $expected Expected native definition.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('access_choices')]
    public function test_simple_access_choice(int $current, int $allow, int $expected): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $role = $this->getDataGenerator()->create_role();
        assign_capability(role_permissions::CAPABILITY, $current, $role, \context_system::instance(), true);
        role_permissions::save_access($role, $allow, role_permissions::revision($role), sesskey());
        $this->assertSame($expected, role_permissions::permission($role));
    }

    /**
     * Provide all current native states and both simple decisions.
     *
     * @return array Native role transitions.
     */
    public static function access_choices(): array {
        return [
            [CAP_INHERIT, 0, CAP_INHERIT], [CAP_INHERIT, 1, CAP_ALLOW],
            [CAP_ALLOW, 0, CAP_INHERIT], [CAP_ALLOW, 1, CAP_ALLOW],
            [CAP_PREVENT, 0, CAP_PREVENT], [CAP_PREVENT, 1, CAP_ALLOW],
            [CAP_PROHIBIT, 0, CAP_PROHIBIT], [CAP_PROHIBIT, 1, CAP_ALLOW],
        ];
    }
}
