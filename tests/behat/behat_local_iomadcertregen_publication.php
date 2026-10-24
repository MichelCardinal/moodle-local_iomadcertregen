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

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Real HTTP acceptance of publication, retained originals and bounded multi-learner forms.
 */
class behat_local_iomadcertregen_publication extends behat_base {
    /** @var array Native evidence before publication. */
    private array $before = [];

    /** @var string Native course-history URL in this disposable test. */
    private string $historyurl = '';

    /** @var int Native history file ID. */
    private int $historyfileid = 0;

    /**
     * Configure email delivery before preparation without sending any message.
     *
     * @Given /^the certificate "([^"]*)" uses email delivery$/
     * @param string $activity Activity name.
     */
    public function configure_email_delivery(string $activity): void {
        global $DB;
        $certificate = $DB->get_record('iomadcertificate', ['name' => $activity], '*', MUST_EXIST);
        $DB->set_field('iomadcertificate', 'delivery', 2, ['id' => $certificate->id]);
    }

    /**
     * Capture an existing issue and saved native PDF before the explicit publication POST.
     *
     * @Given /^the publication review for "([^"]*)" is recorded$/
     * @param string $activity Activity name.
     */
    public function record_publication(string $activity): void {
        global $DB;
        $certificate = $DB->get_record('iomadcertificate', ['name' => $activity], '*', MUST_EXIST);
        $issue = $DB->get_record('iomadcertificate_issues', ['iomadcertificateid' => $certificate->id], '*', MUST_EXIST);
        $this->capture($issue);
    }

    /**
     * Capture native identity and bytes without altering the runtime user's session.
     *
     * @param stdClass $issue Existing native issue.
     */
    private function capture(stdClass $issue): void {
        $cm = get_coursemodule_from_instance('iomadcertificate', $issue->iomadcertificateid);
        $files = get_file_storage()->get_area_files(
            context_module::instance($cm->id)->id,
            'mod_iomadcertificate',
            'issue',
            $issue->id,
            'id',
            false
        );
        if (count($files) !== 1) {
            throw new Exception('Acceptance requires exactly one saved native PDF per issue.');
        }
        $file = reset($files);
        $this->before[$issue->id] = [
            'issue' => $issue, 'fileid' => $file->get_id(), 'hash' => hash('sha256', $file->get_content()),
        ];
    }

    /**
     * Inspect the actual publication and HTTP-download the retained original as administrator.
     *
     * @Then /^the published certificate retains its original PDF and issue identity$/
     */
    public function verify_publication(): void {
        foreach ($this->before as $item) {
            $this->verify_publication_item($item);
        }
        $session = $this->getSession();
        $link = $session->getPage()->find('css', 'a[href*="/local_iomadcertregen/original/"]');
        if (!$link) {
            throw new Exception('The administrator original-download link is missing.');
        }
        $previous = $session->getCurrentUrl();
        try {
            $session->visit($link->getAttribute('href'));
            $hash = hash('sha256', $session->getDriver()->getContent());
            if ($session->getStatusCode() !== 200 || $hash !== reset($this->before)['hash']) {
                throw new Exception('The retained original HTTP download did not match its pre-publication bytes.');
            }
        } finally {
            $session->visit($previous);
        }
    }

    /**
     * Follow the unchanged native activity button as a real learner and verify exact PDF bytes.
     *
     * @Then /^the native certificate button for "([^"]*)" serves the published PDF$/
     * @param string $activity Activity name.
     */
    public function download_from_native_button(string $activity): void {
        global $DB;
        $certificate = $DB->get_record('iomadcertificate', ['name' => $activity], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('iomadcertificate', $certificate->id);
        $operation = $DB->get_record_select(
            'local_iomadcertregen_op',
            'cmid = :id AND publishedtime > 0',
            ['id' => $cm->id],
            '*',
            MUST_EXIST
        );
        $session = $this->getSession();
        $session->visit($this->locate_path('/mod/iomadcertificate/view.php?id=' . $cm->id));
        $previous = $session->getCurrentUrl();
        try {
            $session->getPage()->pressButton(get_string('getiomadcertificate', 'iomadcertificate'));
            $this->assert_published_response($operation);
            $session->visit($this->locate_path('/mod/iomadcertificate/view.php?id=' . $cm->id .
                '&action=download&userid=' . get_admin()->id));
            $this->assert_published_response($operation);
        } finally {
            $session->visit($previous);
        }
    }

    /**
     * Select two existing native issues through the real multiple-selection form.
     *
     * @When /^I select the existing certificates of "([^"]*)" and "([^"]*)"$/
     * @param string $first First username.
     * @param string $second Second username.
     */
    public function select_issues(string $first, string $second): void {
        global $DB;
        $select = $this->getSession()->getPage()->findField('issueids[]');
        if (!$select) {
            throw new Exception('The native selection form is missing.');
        }
        foreach ([$first, $second] as $username) {
            $userid = $DB->get_field('user', 'id', ['username' => $username], MUST_EXIST);
            $issue = $DB->get_record('iomadcertificate_issues', ['userid' => $userid], '*', MUST_EXIST);
            $this->capture($issue);
            $select->selectOption((string)$issue->id, true);
        }
    }

    /**
     * Verify each issue in the selected batch, including originals and absence of new issues.
     *
     * @Then /^both selected certificate copies are "(prepared|confirmed|published)"$/
     * @param string $status Expected state.
     */
    public function verify_batch(string $status): void {
        global $DB;
        $field = $this->getSession()->getPage()->find('css', 'input[name="batch"]');
        if (!$field) {
            throw new Exception('The batch review form is missing after the HTTP action.');
        }
        $key = $field->getValue();
        $copies = $DB->get_records('local_iomadcertregen_op', ['batchkey' => $key]);
        if (count($copies) !== 2 || count($this->before) !== 2) {
            throw new Exception('The selected two-learner batch did not retain exactly two operations.');
        }
        foreach ($copies as $copy) {
            $this->verify_batch_item($copy, $status);
        }
    }
    /**
     * Compare one publication to its captured native identity and original bytes.
     *
     * @param array $item Captured native evidence.
     */
    private function verify_publication_item(array $item): void {
        global $DB;
        $issue = $DB->get_record('iomadcertificate_issues', ['id' => $item['issue']->id], '*', MUST_EXIST);
        $operation = $DB->get_record_select(
            'local_iomadcertregen_op',
            'issueid = :id AND publishedtime > 0',
            ['id' => $issue->id],
            '*',
            MUST_EXIST
        );
        $native = get_file_storage()->get_file_by_id($item['fileid']);
        $archive = get_file_storage()->get_file(
            $operation->contextid,
            'local_iomadcertregen',
            'original',
            $operation->id,
            '/',
            'native-' . $item['fileid'] . '.pdf'
        );
        if (
            $issue != $item['issue'] || !$archive || !$native ||
                hash('sha256', $archive->get_content()) !== $item['hash'] ||
                hash('sha256', $native->get_content()) !== $operation->pdfhash
        ) {
            throw new Exception('Publication did not preserve native identity, original bytes and reviewed replacement.');
        }
    }

    /**
     * Check one selected beneficiary without changing the remaining batch checks.
     *
     * @param stdClass $copy Operation.
     * @param string $status Expected batch state.
     */
    private function verify_batch_item(stdClass $copy, string $status): void {
        global $DB;
        $item = $this->before[$copy->issueid];
        $native = get_file_storage()->get_file_by_id($item['fileid']);
        $hash = $status === 'published' ? $copy->pdfhash : $item['hash'];
        $issue = $DB->get_record('iomadcertificate_issues', ['id' => $copy->issueid], '*', MUST_EXIST);
        if (
            $copy->status !== ($status === 'published' ? 'confirmed' : $status) ||
                ($status === 'published' && !$copy->publishedtime) ||
                $issue != $item['issue'] || hash('sha256', $native->get_content()) !== $hash ||
                $copy->mailstatus !== 'notrequested'
        ) {
            throw new Exception('A batch item has an incorrect state, changed native identity or unexpected mail attempt.');
        }
    }

    /**
     * Establish the real native historical route, using the branch's own storage convention.
     *
     * @Given /^a native history PDF exists for "([^"]*)" in "([^"]*)"$/
     * @param string $username Fictional learner.
     * @param string $activity Activity name.
     */
    public function create_history(string $username, string $activity): void {
        global $CFG, $DB;
        $userid = $DB->get_field('user', 'id', ['username' => $username], MUST_EXIST);
        $certificate = $DB->get_record('iomadcertificate', ['name' => $activity], '*', MUST_EXIST);
        $issue = $DB->get_record(
            'iomadcertificate_issues',
            ['userid' => $userid, 'iomadcertificateid' => $certificate->id],
            '*',
            MUST_EXIST
        );
        $cm = get_coursemodule_from_instance('iomadcertificate', $certificate->id);
        $legacy = (int)$CFG->branch < 501;
        $trackid = $DB->insert_record($legacy ? 'local_iomad_track' : 'local_iomad_tracks', (object)[
            'courseid' => $certificate->course, 'userid' => $userid, 'timecompleted' => $issue->timecreated, 'companyid' => 0,
        ]);
        $filename = clean_filename(format_string(rtrim($certificate->name, '.')) . '.pdf');
        $DB->insert_record('local_iomad_track_certs', (object)['trackid' => $trackid, 'filename' => $filename]);
        $originals = get_file_storage()->get_area_files(
            context_module::instance($cm->id)->id,
            'mod_iomadcertificate',
            'issue',
            $issue->id,
            'id',
            false
        );
        $file = get_file_storage()->create_file_from_storedfile([
            'contextid' => context_user::instance($userid)->id,
            'component' => $legacy ? 'local_iomad_track' : 'local_iomad',
            'filearea' => $legacy ? 'issue' : 'certificate_issue',
            'itemid' => $trackid, 'filepath' => '/', 'filename' => $filename, 'userid' => $userid,
        ], reset($originals));
        $this->historyfileid = $file->get_id();
        $this->historyurl = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $trackid,
            '/',
            $filename,
            true
        )->out(false);
    }

    /**
     * Confirm the actual native historical URL returns the published PDF for its beneficiary.
     *
     * @Then /^I download my native course-history PDF$/
     */
    public function download_own_history(): void {
        $session = $this->getSession();
        $previous = $session->getCurrentUrl();
        $file = get_file_storage()->get_file_by_id($this->historyfileid);
        try {
            $session->visit($this->historyurl);
            if (
                $session->getStatusCode() !== 200 ||
                    hash('sha256', $session->getDriver()->getContent()) !== hash('sha256', $file->get_content())
            ) {
                throw new Exception('The beneficiary history download did not return the exact stored published PDF.');
            }
        } finally {
            $session->visit($previous);
        }
    }

    /**
     * Prove that being another logged-in learner never grants access to a personal history file.
     *
     * @Then /^the other learner's native history PDF is refused$/
     */
    public function refuse_unrelated_history(): void {
        $session = $this->getSession();
        $previous = $session->getCurrentUrl();
        try {
            $session->visit($this->historyurl);
            if ($session->getStatusCode() < 400 || str_starts_with($session->getDriver()->getContent(), '%PDF-')) {
                throw new Exception('An unrelated logged-in learner received another beneficiary history PDF.');
            }
        } finally {
            $session->visit($previous);
        }
    }
    /**
     * Verify an authenticated learner response contains the exact published bytes.
     *
     * @param stdClass $operation Published operation.
     */
    private function assert_published_response(stdClass $operation): void {
        $session = $this->getSession();
        $headers = array_change_key_case($session->getResponseHeaders(), CASE_LOWER);
        if (
            $session->getStatusCode() !== 200 ||
                !str_starts_with(implode(', ', (array)($headers['content-type'] ?? [])), 'application/pdf') ||
                hash('sha256', $session->getDriver()->getContent()) !== $operation->pdfhash ||
                !str_contains($session->getCurrentUrl(), '/local/iomadcertregen/learner.php')
        ) {
            throw new Exception('The learner button did not deliver the reviewed PDF through its protected endpoint.');
        }
    }
}
