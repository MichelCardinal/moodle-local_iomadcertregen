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

use local_iomadcertregen\local\{learner, tenant_access};

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/publication_testcase.php');

/**
 * Recorded company boundaries and native department/child-company permissions.
 *
 * @covers \local_iomadcertregen\local\tenant_access
 */
#[\PHPUnit\Framework\Attributes\CoversClass(tenant_access::class)]
final class tenant_scope_test extends publication_testcase {
    /**
     * Native child-company access must be explicitly granted, even with broad user-management capability.
     *
     * @dataProvider child_permissions
     * @param bool $allowed Whether child-company access is granted.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('child_permissions')]
    public function test_parent_company_requires_child_permission(bool $allowed): void {
        [$data] = $this->correction();
        $manager = $this->getDataGenerator()->create_user();
        $parent = $this->company_scope($data, $manager->id);
        $child = $this->company_scope($data, $this->getDataGenerator()->create_user()->id, $parent);
        $extra = ['block/iomad_company_admin:editallusers'];
        if ($allowed) {
            $extra[] = 'block/iomad_company_admin:canviewchildren';
        }
        $this->manager_role($manager->id, $extra);
        $this->setUser($manager);
        $GLOBALS['SESSION']->currenteditingcompany = $parent;
        $this->assertSame($allowed, tenant_access::manager_allowed($data[1]->id, $child, true));
    }

    /**
     * Provide granted and absent native child-company permissions.
     *
     * @return array Native permission cases.
     */
    public static function child_permissions(): array {
        return [[true], [false]];
    }

    /**
     * A department manager cannot read a beneficiary outside their native department subtree.
     */
    public function test_department_scope_is_preserved(): void {
        global $CFG, $DB;
        [$data] = $this->correction();
        $manager = $this->getDataGenerator()->create_user();
        $companyid = $this->company_scope($data, $manager->id);
        $modern = (int)$CFG->branch >= 501;
        $usertable = $modern ? 'local_iomad_company_users' : 'company_users';
        $root = $DB->get_field($usertable, 'departmentid', ['userid' => $manager->id, 'companyid' => $companyid]);
        $departmentid = $DB->insert_record($modern ? 'local_iomad_company_departments' : 'department', (object)[
            'name' => 'Restricted department', 'shortname' => 'ICRRESTRICTED',
            ($modern ? 'companyid' : 'company') => $companyid, ($modern ? 'parentid' : 'parent') => $root,
        ]);
        $DB->set_field($usertable, 'departmentid', $departmentid, ['userid' => $manager->id]);
        $DB->set_field($usertable, 'managertype', 2, ['userid' => $manager->id]);
        $this->manager_role($manager->id, []);
        $this->setUser($manager);
        $GLOBALS['SESSION']->currenteditingcompany = $companyid;
        $this->assertFalse(tenant_access::manager_allowed($data[1]->id, $companyid, true));
    }

    /**
     * The activity endpoint must not trust a global view-other capability across unrelated companies.
     */
    public function test_activity_reader_cannot_cross_companies(): void {
        [$data, $operation] = $this->correction();
        $manager = $this->getDataGenerator()->create_user();
        $otherdata = $data;
        $otherdata[1] = $this->getDataGenerator()->create_user();
        $companyid = $this->company_scope($otherdata, $manager->id);
        $this->company_scope($data, $this->getDataGenerator()->create_user()->id);
        $this->getDataGenerator()->enrol_user($manager->id, $data[3]->id, 'student');
        $this->manager_role($manager->id);
        $operation = \local_iomadcertregen\local\service::prepare(
            $data[2]->id,
            $data[4]->id,
            str_repeat('a0', 16),
            sesskey()
        );
        $operation = \local_iomadcertregen\local\service::confirm($data[2]->id, $operation->id, sesskey());
        $this->publish($data, $operation);
        $this->setUser($manager);
        $GLOBALS['SESSION']->currenteditingcompany = $companyid;
        $GLOBALS['PAGE'] = new \moodle_page();
        $this->expectException(\required_capability_exception::class);
        learner::document($data[2]->id, $data[1]->id);
    }
    /**
     * A stale or forged selected company cannot grant access after membership or selection rights change.
     */
    public function test_unapproved_selected_company_is_refused(): void {
        [$data] = $this->correction();
        $manager = $this->getDataGenerator()->create_user();
        $this->company_scope($data, $manager->id);
        $other = $this->company_scope($data, $this->getDataGenerator()->create_user()->id);
        $this->manager_role($manager->id);
        $this->setUser($manager);
        $GLOBALS['SESSION']->currenteditingcompany = $other;
        $this->assertFalse(tenant_access::manager_allowed($data[1]->id, $other, true));
    }
}
