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

use local_iomadcertregen\local\{learner, publication, publication_files, service};

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/publication_testcase.php');

/**
 * Learner admission, immutable issue identity and server-side publication protection.
 *
 * @covers \local_iomadcertregen\local\learner
 */
#[\PHPUnit\Framework\Attributes\CoversClass(learner::class)]
final class publication_access_test extends publication_testcase {
    /**
     * An active enrolled beneficiary receives the exact published PDF despite a later template selection.
     */
    public function test_enrolled_beneficiary_gets_exact_published_bytes(): void {
        global $DB;
        [$data, $operation] = $this->correction();
        $this->publish($data, $operation);
        $bytes = service::file($operation)->get_content();
        $DB->set_field('iomadcertificate', 'iomadcertificatetype', 'A4_embedded', ['id' => $data[0]->id]);
        $this->request_as($data[1]);
        [$published, $file] = learner::document($data[2]->id, (int)$data[1]->id);
        $this->assertSame((int)$operation->id, (int)$published->id);
        $this->assertSame($bytes, $file->get_content());
    }

    /**
     * Another enrolled learner cannot obtain the beneficiary's correction.
     */
    public function test_other_learner_is_refused(): void {
        [$data, $operation] = $this->correction();
        $this->publish($data, $operation);
        $other = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($other->id, $data[3]->id, 'student');
        $this->request_as($other);
        $this->expectException(\required_capability_exception::class);
        learner::document($data[2]->id, (int)$data[1]->id);
    }

    /**
     * A changed native code invalidates the publication binding rather than silently issuing again.
     */
    public function test_changed_issue_code_is_refused(): void {
        global $DB;
        [$data, $operation] = $this->correction();
        $this->publish($data, $operation);
        $DB->set_field('iomadcertificate_issues', 'code', 'DIFFERENT', ['id' => $data[4]->id]);
        $this->request_as($data[1]);
        $this->expectException(\moodle_exception::class);
        learner::document($data[2]->id, (int)$data[1]->id);
    }

    /**
     * A suspended enrolment cannot use a direct published document URL.
     */
    public function test_suspended_enrolment_is_refused(): void {
        global $DB;
        [$data, $operation] = $this->correction();
        $this->publish($data, $operation);
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, ['userid' => $data[1]->id]);
        $this->request_as($data[1]);
        $this->expectException(\moodle_exception::class);
        learner::document($data[2]->id, (int)$data[1]->id);
    }

    /**
     * A hidden activity's restrictions still apply to the beneficiary.
     */
    public function test_hidden_activity_is_refused(): void {
        [$data, $operation] = $this->correction();
        $this->publish($data, $operation);
        set_coursemodule_visible($data[2]->id, 0);
        $this->request_as($data[1]);
        $this->expectException(\moodle_exception::class);
        learner::document($data[2]->id, (int)$data[1]->id);
    }

    /**
     * Publication requires server-side CSRF protection.
     */
    public function test_invalid_sesskey_is_refused(): void {
        [$data, $operation, $files] = $this->correction();
        $this->expectException(\moodle_exception::class);
        publication::publish($data[2]->id, $operation->id, publication_files::fingerprint($files), 'invalid');
    }

    /**
     * Confirmation by itself is not sufficient if the copy has an expiry.
     */
    public function test_expiring_copy_cannot_be_published(): void {
        global $DB;
        [$data, $operation] = $this->correction();
        $DB->set_field('local_iomadcertregen_op', 'timeexpires', time() + DAYSECS, ['id' => $operation->id]);
        $this->expectException(\moodle_exception::class);
        $this->publish($data, $operation);
    }

    /**
     * The native activity's minimum time requirement remains effective.
     */
    public function test_required_course_time_is_enforced(): void {
        global $DB;
        [$data, $operation] = $this->correction();
        $this->publish($data, $operation);
        $DB->set_field('iomadcertificate', 'requiredtime', 10000, ['id' => $data[0]->id]);
        $this->request_as($data[1]);
        $this->expectException(\moodle_exception::class);
        learner::document($data[2]->id, (int)$data[1]->id);
    }
    /**
     * Model a new HTTP request after the legacy renderer initialised the administrator page.
     *
     * @param \stdClass $user Authenticated test user.
     */
    private function request_as(\stdClass $user): void {
        $this->setUser($user);
        $GLOBALS['PAGE'] = new \moodle_page();
    }
}
