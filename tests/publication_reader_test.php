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
 * Preserve native manager permissions and permit a later enrolment's native issue workflow.
 *
 * @covers \local_iomadcertregen\local\learner
 * @covers \local_iomadcertregen\local\access
 */
#[\PHPUnit\Framework\Attributes\CoversClass(learner::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\local\access::class)]
final class publication_reader_test extends publication_testcase {
    /**
     * A later enrolment must not be diverted to an obsolete certificate publication.
     */
    public function test_later_enrolment_uses_native_workflow(): void {
        global $DB;
        [$data, $operation] = $this->correction();
        $this->publish($data, $operation);
        $DB->set_field('user_enrolments', 'timestart', $data[4]->timecreated + DAYSECS, ['userid' => $data[1]->id]);
        $this->setUser($data[1]);
        $this->assertNull(learner::native_operation($data[2]->id, $data[1]->id));
        $this->assertTrue($DB->record_exists('iomadcertificate_issues', ['id' => $data[4]->id]));
    }

    /**
     * Native view-other capability plus IOMAD's own management check permits a reader, not a publisher.
     */
    public function test_native_manager_can_read_without_publishing_rights(): void {
        [$data, $operation] = $this->correction();
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($manager->id, $data[3]->id, 'student');
        $context = \context_system::instance();
        $role = $this->getDataGenerator()->create_role();
        foreach (['mod/iomadcertificate:viewother', 'block/iomad_company_admin:editallusers'] as $capability) {
            assign_capability($capability, CAP_ALLOW, $role, $context);
        }
        role_assign($role, $manager->id, $context->id);
        $companyid = $this->company_scope($data, $manager->id);
        $operation = service::prepare($data[2]->id, $data[4]->id, str_repeat('f9', 16), sesskey());
        $operation = service::confirm($data[2]->id, $operation->id, sesskey());
        $this->publish($data, $operation);
        $this->setUser($manager);
        $GLOBALS['SESSION']->currenteditingcompany = $companyid;
        $GLOBALS['PAGE'] = new \moodle_page();
        [, $file] = learner::document($data[2]->id, $data[1]->id);
        $this->assertSame(service::file($operation)->get_content(), $file->get_content());
        $this->expectException(\required_capability_exception::class);
        publication::publish($data[2]->id, $operation->id, 'unused', sesskey());
    }

    /**
     * Another administrator cannot publish an operation prepared by a different actor.
     */
    public function test_other_admin_cannot_publish_copy(): void {
        [$data, $operation, $files] = $this->correction();
        $admin = $this->getDataGenerator()->create_user();
        set_config('siteadmins', get_admin()->id . ',' . $admin->id);
        $this->setUser($admin);
        $this->expectException(\moodle_exception::class);
        publication::publish($data[2]->id, $operation->id, publication_files::fingerprint($files), sesskey());
    }

    /**
     * A prepared copy must be confirmed before publication is possible.
     */
    public function test_unconfirmed_copy_cannot_be_published(): void {
        [$data] = $this->correction();
        $prepared = service::prepare($data[2]->id, $data[4]->id, str_repeat('c9', 16), sesskey());
        $this->expectException(\moodle_exception::class);
        $this->publish($data, $prepared);
    }

    /**
     * Duplicate selection is rejected even when submitted directly to the service.
     */
    public function test_duplicate_batch_selection_is_refused(): void {
        [$data] = $this->correction();
        $this->expectException(\moodle_exception::class);
        \local_iomadcertregen\local\batch::prepare(
            $data[2]->id,
            [$data[4]->id, $data[4]->id],
            str_repeat('d9', 16),
            sesskey()
        );
    }
    /**
     * Malformed archive provenance is refused without a PHP type error or document disclosure.
     */
    public function test_retained_original_integrity_is_enforced(): void {
        [$data, $operation] = $this->correction();
        $this->publish($data, $operation);
        $files = get_file_storage()->get_area_files(
            $data[5]->id,
            'local_iomadcertregen',
            'original',
            $operation->id,
            'id',
            false
        );
        $file = reset($files);
        $this->assertNotFalse(\local_iomadcertregen\local\documents::file($operation, 'original', $file->get_filename()));
        $file->set_source('{"sha256":[]}');
        $this->assertFalse(\local_iomadcertregen\local\documents::file($operation, 'original', $file->get_filename()));
    }
    /**
     * Publishing a later native issue supersedes the orphaned mapping from a previous enrolment period.
     */
    public function test_new_issue_publication_supersedes_old_period(): void {
        global $DB;
        [$data, $first] = $this->correction();
        $this->publish($data, $first);
        $DB->delete_records('iomadcertificate_issues', ['id' => $data[4]->id]);
        $this->assertNull(learner::operation($data[2]->id, $data[1]->id));
        $issue = clone $data[4];
        unset($issue->id);
        $issue->code = 'NEWPERIOD';
        $issue->timecreated += DAYSECS;
        $issue->id = $DB->insert_record('iomadcertificate_issues', $issue);
        get_file_storage()->create_file_from_storedfile([
            'contextid' => $data[5]->id, 'component' => 'mod_iomadcertificate',
            'filearea' => 'issue', 'itemid' => $issue->id, 'filepath' => '/', 'filename' => 'original.pdf',
        ], service::file($first));
        $data[4] = $issue;
        $next = service::prepare($data[2]->id, $issue->id, str_repeat('e9', 16), sesskey());
        $next = service::confirm($data[2]->id, $next->id, sesskey());
        $this->publish($data, $next);
        $this->assertSame((int)$next->id, (int)learner::operation($data[2]->id, $data[1]->id)->id);
        $this->assertSame(0, (int)$DB->get_field('local_iomadcertregen_op', 'publishedtime', ['id' => $first->id]));
    }
    /**
     * Download aliases and an ignored foreign-user parameter cannot bypass a learner publication.
     */
    public function test_native_request_resolves_actual_beneficiary_and_download_branch(): void {
        [$data] = $this->correction();
        $other = $this->getDataGenerator()->create_user();
        $get = $_GET;
        $post = $_POST;
        try {
            $_GET['userid'] = $_POST['userid'] = $other->id;
            $_GET['action'] = $_POST['action'] = 'download';
            $this->setUser($data[1]);
            $this->assertSame((int)$data[1]->id, learner::native_userid());
            $this->assertTrue(learner::native_download_enabled());
            $this->setAdminUser();
            $this->assertSame((int)$other->id, learner::native_userid());
        } finally {
            $_GET = $get;
            $_POST = $post;
        }
    }
}
