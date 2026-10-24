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

use local_iomadcertregen\local\records;
use local_iomadcertregen\local\service;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/certificate_testcase.php');

/**
 * Integration tests for access behaviour.
 *
 * @covers \local_iomadcertregen\local\access
 * @covers \local_iomadcertregen\local\records
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\local\access::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\local\records::class)]
final class access_test extends certificate_testcase {
    /**
     * The service itself checks CSRF, independently of Moodle forms.
     */
    public function test_invalid_session_key_is_refused(): void {
        [, , $cm, , $issue] = $this->fixture();
        $this->expectException(\moodle_exception::class);
        service::prepare($cm->id, $issue->id, str_repeat('c', 32), 'invalid');
    }

    /**
     * The plugin capability does not replace native certificate-management permission.
     */
    public function test_nonadmin_with_capability_is_refused(): void {
        global $DB;
        [, $learner, $cm, , $issue, $context] = $this->fixture();
        $manager = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        role_assign($manager, $learner->id, $context->id);
        assign_capability('local/iomadcertregen:regenerate', CAP_ALLOW, $manager, $context->id);
        assign_capability('mod/iomadcertificate:manage', CAP_PROHIBIT, $manager, $context->id);
        $this->setUser($learner);
        $this->expectException(\required_capability_exception::class);
        service::prepare($cm->id, $issue->id, str_repeat('d', 32), sesskey());
    }

    /**
     * A valid issue identifier from a different activity is not accepted.
     */
    public function test_cross_activity_issue_is_refused(): void {
        [, , , $course, $issue] = $this->fixture();
        $other = $this->getDataGenerator()->create_module('iomadcertificate', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('iomadcertificate', $other->id);
        $this->expectException(\dml_missing_record_exception::class);
        service::prepare($cm->id, $issue->id, str_repeat('e', 32), sesskey());
    }

    /**
     * A file outside the requested operation context cannot be served.
     */
    public function test_file_access_and_expiry(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/iomadcertregen/lib.php');
        [, , $cm, $course, $issue, $context] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('1', 32), sesskey());
        $this->assertFalse(local_iomadcertregen_pluginfile(
            $course,
            $cm,
            $context,
            'document',
            [$operation->id, 'other.pdf'],
            false
        ));
        $this->assertFalse(local_iomadcertregen_pluginfile(
            $course,
            $cm,
            $context,
            'wrong',
            [$operation->id, 'corrected-certificate.pdf'],
            false
        ));
        $DB->set_field('local_iomadcertregen_op', 'timeexpires', time() - 1, ['id' => $operation->id]);
        $this->expectException(\moodle_exception::class);
        records::operation($cm->id, $operation->id);
    }

    /**
     * A preliminary issue cannot be corrected after its beneficiary is suspended.
     */
    public function test_suspended_beneficiary_is_refused(): void {
        global $DB;
        [, $learner, $cm, , $issue] = $this->fixture();
        $DB->set_field('user', 'suspended', 1, ['id' => $learner->id]);
        $this->expectException(\dml_missing_record_exception::class);
        service::prepare($cm->id, $issue->id, str_repeat('6', 32), sesskey());
    }

    /**
     * Cross-activity operation identifiers do not reveal another PDF.
     */
    public function test_cross_activity_operation_is_refused(): void {
        [, , $cm, $course, $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('8', 32), sesskey());
        $module = $this->getDataGenerator()->create_module('iomadcertificate', ['course' => $course->id]);
        $other = get_coursemodule_from_instance('iomadcertificate', $module->id);
        $this->expectException(\dml_missing_record_exception::class);
        records::operation($other->id, $operation->id);
    }

    /**
     * Even a second site administrator cannot access somebody else's preparation.
     */
    public function test_prepared_copy_is_private_to_its_administrator(): void {
        global $CFG;
        [, , $cm, , $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('a2', 16), sesskey());
        $administrator = $this->getDataGenerator()->create_user();
        set_config('siteadmins', $CFG->siteadmins . ',' . $administrator->id);
        $this->setUser($administrator);
        $this->assertTrue(is_siteadmin());
        $this->expectException(\moodle_exception::class);
        records::operation($cm->id, $operation->id);
    }

    /**
     * A second site administrator can retrieve a confirmed administrative copy.
     */
    public function test_confirmed_copy_is_available_to_authorised_administrators(): void {
        global $CFG;
        [, , $cm, , $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('b2', 16), sesskey());
        service::confirm($cm->id, $operation->id, sesskey());
        $administrator = $this->getDataGenerator()->create_user();
        set_config('siteadmins', $CFG->siteadmins . ',' . $administrator->id);
        $this->setUser($administrator);
        $this->assertSame('confirmed', records::operation($cm->id, $operation->id)->status);
    }

    /**
     * Noncanonical operation identifiers are refused before file lookup.
     */
    public function test_file_route_rejects_noncanonical_identifiers(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/iomadcertregen/lib.php');
        [, , $cm, $course, , $context] = $this->fixture();
        foreach (['0', '-1', '1abc', '1.0'] as $identifier) {
            $this->assertFalse(local_iomadcertregen_pluginfile(
                $course,
                $cm,
                $context,
                'document',
                [$identifier, 'corrected-certificate.pdf'],
                true
            ));
        }
    }
}
