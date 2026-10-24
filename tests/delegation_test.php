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

use local_iomadcertregen\local\{access, batch, issue_list, publication, publication_files, records, service, tenant_access};

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/publication_testcase.php');

/**
 * Correction delegation respects Moodle contexts without requiring company associations.
 *
 * @covers \local_iomadcertregen\local\access
 * @covers \local_iomadcertregen\local\tenant_access
 * @covers \local_iomadcertregen\local\issue_list
 */
#[\PHPUnit\Framework\Attributes\CoversClass(access::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(tenant_access::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(issue_list::class)]
final class delegation_test extends publication_testcase {
    /**
     * Existing manager archetypes get the default, but explicit refusals survive the migration.
     */
    public function test_manager_upgrade_preserves_existing_decisions(): void {
        global $CFG, $DB;
        $this->fixture();
        require_once($CFG->dirroot . '/local/iomadcertregen/db/upgrade.php');
        $role = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        $system = \context_system::instance();
        unassign_capability('local/iomadcertregen:regenerate', $role, $system->id);
        xmldb_local_iomadcertregen_upgrade_managers();
        $conditions = ['roleid' => $role, 'contextid' => $system->id, 'capability' => 'local/iomadcertregen:regenerate'];
        $this->assertEquals(CAP_ALLOW, $DB->get_field('role_capabilities', 'permission', $conditions));
        assign_capability('local/iomadcertregen:regenerate', CAP_PROHIBIT, $role, $system->id, true);
        xmldb_local_iomadcertregen_upgrade_managers();
        $this->assertEquals(CAP_PROHIBIT, $DB->get_field('role_capabilities', 'permission', $conditions));
    }

    /**
     * A Moodle manager can complete the real correction pipeline in their course only.
     */
    public function test_course_manager_can_publish(): void {
        global $DB;
        $data = $this->fixture();
        $manager = $this->getDataGenerator()->create_user();
        $role = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        role_assign($role, $manager->id, \context_course::instance($data[3]->id));
        $this->setUser($manager);
        $operation = service::prepare($data[2]->id, $data[4]->id, str_repeat('c1', 16), sesskey());
        $operation = service::confirm($data[2]->id, $operation->id, sesskey());
        $hash = publication_files::fingerprint(publication_files::targets(records::issue($data[2]->id, $data[4]->id)));
        $published = publication::publish($data[2]->id, $operation->id, $hash, sesskey());
        $this->assertGreaterThan(0, $published->publishedtime);
        $other = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('iomadcertificate', ['course' => $other->id]);
        $this->assertFalse(access::allowed(\context_module::instance($module->cmid)));
    }

    /**
     * Teachers need an explicit Moodle grant, and revocation takes effect immediately.
     */
    public function test_teacher_is_opt_in_and_revocable(): void {
        global $DB;
        $data = $this->fixture();
        $teacher = $this->getDataGenerator()->create_user();
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        $this->getDataGenerator()->enrol_user($teacher->id, $data[3]->id, $role);
        $this->setUser($teacher);
        $this->assertFalse(access::allowed($data[5]));
        assign_capability('local/iomadcertregen:regenerate', CAP_ALLOW, $role, $data[5]->id);
        $this->assertTrue(access::allowed($data[5]));
        $operation = service::prepare($data[2]->id, $data[4]->id, str_repeat('c2', 16), sesskey());
        assign_capability('local/iomadcertregen:regenerate', CAP_PREVENT, $role, $data[5]->id, true);
        $this->expectException(\required_capability_exception::class);
        service::confirm($data[2]->id, $operation->id, sesskey());
    }

    /**
     * A delegated teacher cannot correct an unrelated separate-group beneficiary.
     */
    public function test_separate_groups_are_respected(): void {
        global $DB;
        $data = $this->fixture();
        $teacher = $this->getDataGenerator()->create_user();
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        $this->getDataGenerator()->enrol_user($teacher->id, $data[3]->id, $role);
        assign_capability('local/iomadcertregen:regenerate', CAP_ALLOW, $role, $data[5]->id);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $role, $data[5]->id);
        $DB->set_field('course', 'groupmodeforce', 0, ['id' => $data[3]->id]);
        $DB->set_field('course_modules', 'groupmode', SEPARATEGROUPS, ['id' => $data[2]->id]);
        $group = $this->getDataGenerator()->create_group(['courseid' => $data[3]->id]);
        groups_add_member($group->id, $teacher->id);
        $this->setUser($teacher);
        $this->assertFalse(access::user_allowed($data[5], $data[1]->id));
        $this->getDataGenerator()->enrol_user($data[1]->id, $data[3]->id, 'student');
        $this->assertTrue(groups_add_member($group->id, $data[1]->id));
        $this->assertTrue(access::user_allowed($data[5], $data[1]->id));
    }
    /**
     * Separate-group identities and counts stay private, and a foreign selection writes nothing.
     */
    public function test_group_list_and_forged_batch_are_scoped(): void {
        global $DB;
        $data = $this->fixture();
        $teacher = $this->getDataGenerator()->create_user();
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        $this->getDataGenerator()->enrol_user($teacher->id, $data[3]->id, $role);
        assign_capability('local/iomadcertregen:regenerate', CAP_ALLOW, $role, $data[5]->id);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $role, $data[5]->id);
        $DB->set_field('course', 'groupmodeforce', 0, ['id' => $data[3]->id]);
        $DB->set_field('course_modules', 'groupmode', SEPARATEGROUPS, ['id' => $data[2]->id]);
        $this->setUser($teacher);
        [$rows, $total] = issue_list::page($data[2]->id, 0);
        $this->assertSame([], $rows);
        $this->assertSame(0, $total);
        $this->expectException(\required_capability_exception::class);
        try {
            batch::prepare($data[2]->id, [$data[4]->id], str_repeat('f4', 16), sesskey());
        } finally {
            $this->assertSame(0, $DB->count_records('local_iomadcertregen_op'));
        }
    }
}
