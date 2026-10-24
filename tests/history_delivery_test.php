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

use local_iomadcertregen\local\{history_delivery, tenant_access};

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/publication_testcase.php');

/**
 * Native historical file ownership, route validation and company/department boundaries.
 *
 * @covers \local_iomadcertregen\local\history_delivery
 * @covers \local_iomadcertregen\local\tenant_access
 */
#[\PHPUnit\Framework\Attributes\CoversClass(history_delivery::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(tenant_access::class)]
final class history_delivery_test extends publication_testcase {
    /**
     * Build the normalised route actually passed by Moodle's pluginfile entry points.
     *
     * @param \stored_file $file Native history file.
     * @return string Native relative path.
     */
    private function path(\stored_file $file): string {
        return '/' . implode('/', [$file->get_contextid(), $file->get_component(), $file->get_filearea(),
            $file->get_itemid(), $file->get_filename()]);
    }

    /**
     * A beneficiary retains historical access when their course enrolment is suspended.
     */
    public function test_owner_can_download_after_enrolment_suspension(): void {
        [$data] = $this->correction();
        $file = $this->history_file($data, $data[4]->timecreated - DAYSECS);
        global $DB;
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, ['userid' => $data[1]->id]);
        $this->setUser($data[1]);
        $this->assertSame($file->get_content(), history_delivery::file($this->path($file))->get_content());
    }

    /**
     * Being another logged-in user grants no access to a personal historical certificate.
     */
    public function test_other_learner_cannot_download(): void {
        [$data] = $this->correction();
        $file = $this->history_file($data, $data[4]->timecreated - DAYSECS);
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        history_delivery::file($this->path($file));
    }

    /**
     * Context owner and historical beneficiary must agree even for a site administrator.
     */
    public function test_mismatched_context_is_refused(): void {
        [$data] = $this->correction();
        $file = $this->history_file($data, $data[4]->timecreated - DAYSECS);
        $other = $this->getDataGenerator()->create_user();
        $parts = explode('/', ltrim($this->path($file), '/'));
        $parts[0] = \context_user::instance($other->id)->id;
        $this->expectException(\moodle_exception::class);
        history_delivery::file('/' . implode('/', $parts));
    }

    /**
     * Unrelated component areas remain under their own native handlers.
     */
    public function test_unrelated_routes_are_ignored(): void {
        $this->resetAfterTest();
        $this->assertNull(history_delivery::file('/1/local_iomad/logo/1/example.png'));
        $this->assertNull(history_delivery::file('/1/user/private/0/example.pdf'));
    }

    /**
     * Non-canonical targeted paths must not fall through to the less restrictive native handler.
     */
    public function test_malformed_targeted_route_is_refused(): void {
        $this->resetAfterTest();
        $this->expectException(\moodle_exception::class);
        history_delivery::file('/1/local_iomad/certificate_issue/2/nested/example.pdf');
    }

    /**
     * Guest access is never sufficient for a personal historical file.
     */
    public function test_guest_is_refused(): void {
        [$data] = $this->correction();
        $file = $this->history_file($data, $data[4]->timecreated - DAYSECS);
        $this->setGuestUser();
        $this->expectException(\required_capability_exception::class);
        history_delivery::file($this->path($file));
    }

    /**
     * A native company manager retains permitted access to their company's historical records.
     */
    public function test_company_manager_can_read_own_company(): void {
        [$data, , $files] = $this->correction();
        $manager = $this->getDataGenerator()->create_user();
        $companyid = $this->company_scope($data, $manager->id);
        $role = $this->getDataGenerator()->create_role();
        foreach (['mod/iomadcertificate:viewother', 'block/iomad_company_admin:editallusers'] as $capability) {
            assign_capability($capability, CAP_ALLOW, $role, \context_system::instance());
        }
        role_assign($role, $manager->id, \context_system::instance()->id);
        $this->setUser($manager);
        $GLOBALS['SESSION']->currenteditingcompany = $companyid;
        $files = array_filter($files, static fn($file) => $file->get_component() !== 'mod_iomadcertificate');
        $history = reset($files);
        $this->assertSame($history->get_content(), history_delivery::file($this->path($history))->get_content());
    }

    /**
     * A broad native capability is insufficient to read an unrelated company's recorded history.
     */
    public function test_company_manager_cannot_read_other_company(): void {
        [$data, , $files] = $this->correction();
        $manager = $this->getDataGenerator()->create_user();
        $companyid = $this->company_scope($data, $manager->id);
        $this->company_scope($data, $this->getDataGenerator()->create_user()->id);
        $role = $this->getDataGenerator()->create_role();
        foreach (['mod/iomadcertificate:viewother', 'block/iomad_company_admin:editallusers'] as $capability) {
            assign_capability($capability, CAP_ALLOW, $role, \context_system::instance());
        }
        role_assign($role, $manager->id, \context_system::instance()->id);
        $this->setUser($manager);
        $GLOBALS['SESSION']->currenteditingcompany = $companyid;
        $files = array_filter($files, static fn($file) => $file->get_component() !== 'mod_iomadcertificate');
        $this->expectException(\required_capability_exception::class);
        history_delivery::file($this->path(reset($files)));
    }
}
