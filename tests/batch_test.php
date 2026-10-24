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

use local_iomadcertregen\local\{batch, learner, records, service};

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/publication_testcase.php');

/**
 * Bounded multi-learner processing with explicit review, partial failures and replay protection.
 *
 * @covers \local_iomadcertregen\local\batch
 */
#[\PHPUnit\Framework\Attributes\CoversClass(batch::class)]
final class batch_test extends publication_testcase {
    /**
     * Two issued beneficiaries are prepared, confirmed and published without another issue or email.
     */
    public function test_prepare_confirm_publish_and_repeat(): void {
        global $DB;
        [$data] = $this->correction();
        $seconduser = $this->getDataGenerator()->create_user();
        $secondissue = clone $data[4];
        unset($secondissue->id);
        $secondissue->userid = $seconduser->id;
        $secondissue->code = 'LABSECOND';
        $secondissue->id = $DB->insert_record('iomadcertificate_issues', $secondissue);
        $firstfile = get_file_storage()->get_area_files(
            $data[5]->id,
            'mod_iomadcertificate',
            'issue',
            $data[4]->id,
            'id',
            false
        );
        get_file_storage()->create_file_from_storedfile(
            ['itemid' => $secondissue->id, 'userid' => $seconduser->id],
            reset($firstfile)
        );
        $sink = $this->redirectEmails();
        $ids = [$data[4]->id, $secondissue->id];
        [$key, $failed] = batch::prepare($data[2]->id, $ids, str_repeat('c8', 16), sesskey());
        $this->assertSame([], $failed);
        $copies = batch::operations($data[2]->id, $key);
        $this->assertCount(2, $copies);
        batch::prepare($data[2]->id, $ids, str_repeat('c8', 16), sesskey());
        $this->assertCount(2, batch::operations($data[2]->id, $key));
        $this->assertNull(learner::operation($data[2]->id, $seconduser->id));
        $this->assertSame([], batch::apply($data[2]->id, $key, batch::fingerprint($copies, false), false, sesskey()));
        $copies = batch::operations($data[2]->id, $key);
        $this->assertSame([], batch::apply($data[2]->id, $key, batch::fingerprint($copies, true), true, sesskey()));
        $this->assertNotNull(learner::operation($data[2]->id, $seconduser->id));
        $this->assertSame(0, $sink->count());
        $this->assertSame(2, $DB->count_records('iomadcertificate_issues', ['iomadcertificateid' => $data[0]->id]));
    }

    /**
     * A beneficiary without native evidence fails independently without disguising the successful issue.
     */
    public function test_partial_preparation_is_visible(): void {
        global $DB;
        [$data] = $this->correction();
        $issue = clone $data[4];
        unset($issue->id);
        $issue->userid = $this->getDataGenerator()->create_user()->id;
        $issue->code = 'NOORIGINAL';
        $issue->id = $DB->insert_record('iomadcertificate_issues', $issue);
        [$key, $failed] = batch::prepare($data[2]->id, [$data[4]->id, $issue->id], str_repeat('d8', 16), sesskey());
        $this->assertSame([(int)$issue->id], $failed);
        $this->assertCount(1, batch::operations($data[2]->id, $key));
    }

    /**
     * An issue from a different activity invalidates the entire selection before preparation.
     */
    public function test_foreign_activity_issue_is_refused(): void {
        [$data] = $this->correction();
        $other = $this->getDataGenerator()->create_module('iomadcertificate', ['course' => $data[3]->id]);
        $cm = get_coursemodule_from_instance('iomadcertificate', $other->id);
        $this->expectException(\moodle_exception::class);
        batch::prepare($cm->id, [$data[4]->id], str_repeat('e8', 16), sesskey());
    }

    /**
     * The service rejects oversized input independently of the form.
     */
    public function test_oversized_selection_is_refused(): void {
        [$data] = $this->correction();
        $this->expectException(\moodle_exception::class);
        batch::prepare($data[2]->id, range(1, 21), str_repeat('f8', 16), sesskey());
    }

    /**
     * Another administrator cannot reuse a batch belonging to its preparer.
     */
    public function test_other_actor_cannot_read_or_apply_batch(): void {
        [$data] = $this->correction();
        [$key] = batch::prepare($data[2]->id, [$data[4]->id], str_repeat('a9', 16), sesskey());
        $other = $this->getDataGenerator()->create_user();
        set_config('siteadmins', get_admin()->id . ',' . $other->id);
        $this->setUser($other);
        $this->expectException(\moodle_exception::class);
        batch::operations($data[2]->id, $key);
    }

    /**
     * Confirmation never accepts an altered batch review fingerprint.
     */
    public function test_stale_review_is_refused(): void {
        [$data] = $this->correction();
        [$key] = batch::prepare($data[2]->id, [$data[4]->id], str_repeat('b9', 16), sesskey());
        $this->expectException(\moodle_exception::class);
        batch::apply($data[2]->id, $key, str_repeat('0', 64), false, sesskey());
    }

    /**
     * The review form rejects a missing acknowledgement on the server.
     */
    public function test_form_requires_acknowledgement(): void {
        $this->resetAfterTest();
        $form = new \local_iomadcertregen\form\batch(null, ['id' => 1, 'mode' => 'prepare', 'choices' => [1 => 'Example']]);
        $this->assertArrayHasKey('acknowledge', $form->validation(['issueids' => [1], 'acknowledge' => 0], []));
        $this->assertArrayNotHasKey('acknowledge', $form->validation(['issueids' => [1], 'acknowledge' => 1], []));
    }
}
