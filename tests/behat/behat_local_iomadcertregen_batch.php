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
 * Real HTTP acceptance of bounded batch review, publication and retained evidence.
 */
class behat_local_iomadcertregen_batch extends behat_base {
    /** @var array Native evidence captured before the selected batch. */
    private array $before = [];

    /** @var string Batch identity captured from the real preparation response. */
    private string $batchkey = '';

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
        $key = $this->selected_batch_key($status);
        $copies = $DB->get_records('local_iomadcertregen_op', ['batchkey' => $key]);
        if (count($copies) !== 2 || count($this->before) !== 2) {
            throw new Exception('The selected two-learner batch did not retain exactly two operations.');
        }
        foreach ($copies as $copy) {
            $this->verify_batch_item($copy, $status);
            if ($status === 'published') {
                $this->verify_archive($copy);
            }
        }
    }

    /**
     * Keep the prepared batch identity when the completed page has no further action form.
     *
     * @param string $status Expected state.
     * @return string Exact batch key captured before publication.
     */
    private function selected_batch_key(string $status): string {
        $field = $this->getSession()->getPage()->find('css', 'input[name="batch"]');
        if ($status === 'published') {
            $this->require_completed_batch($field);
            return $this->batchkey;
        }
        if (!$field || !preg_match('/^[a-f0-9]{64}$/D', $field->getValue())) {
            throw new Exception('The pending batch review form is missing or invalid.');
        }
        if ($this->batchkey !== '' && $this->batchkey !== $field->getValue()) {
            throw new Exception('The review response changed the selected batch.');
        }
        $this->batchkey = $field->getValue();
        return $this->batchkey;
    }

    /**
     * Check the completed HTTP result after the last publication form disappears.
     *
     * @param mixed $field Action form field, which must be absent.
     */
    private function require_completed_batch($field): void {
        $session = $this->getSession();
        $url = new moodle_url($session->getCurrentUrl());
        if (
            $this->batchkey === '' || $url->get_param('batch') !== $this->batchkey || $field ||
                !str_contains($session->getPage()->getText(), get_string('published', 'local_iomadcertregen'))
        ) {
            throw new Exception('The completed batch is missing its identity or publication result.');
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
     * Check that publication kept each selected beneficiary's exact previous PDF.
     *
     * @param stdClass $copy Published operation.
     */
    private function verify_archive(stdClass $copy): void {
        $item = $this->before[$copy->issueid];
        $archive = get_file_storage()->get_file(
            $copy->contextid,
            'local_iomadcertregen',
            'original',
            $copy->id,
            '/',
            'native-' . $item['fileid'] . '.pdf'
        );
        if (!$archive || hash('sha256', $archive->get_content()) !== $item['hash']) {
            throw new Exception('The selected beneficiary original was not archived exactly.');
        }
    }
}
