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
 * Native acceptance fixtures and navigation for administrative certificate copies.
 */
class behat_local_iomadcertregen extends behat_base {
    /** @var array Native evidence captured before the web correction. */
    private array $evidence = [];

    /**
     * Save a real original for an already issued fictional certificate.
     *
     * @Given /^an IOMAD certificate has been issued to "([^"]*)" in "([^"]*)"$/
     * @param string $username Existing fictional learner.
     * @param string $activity Activity name.
     */
    public function an_iomad_certificate_has_been_issued(string $username, string $activity): void {
        global $DB, $USER;
        $learner = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $certificate = $DB->get_record('iomadcertificate', ['name' => $activity], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('iomadcertificate', $certificate->id, 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        $context = context_module::instance($cm->id);
        $issue = (object)[
            'userid' => $learner->id, 'iomadcertificateid' => $certificate->id,
            'code' => 'EXAMPLE' . str_pad((string)$learner->id, 5, '0', STR_PAD_LEFT), 'timecreated' => 1705320000,
        ];
        $issue->id = $DB->insert_record('iomadcertificate_issues', $issue);
        set_config('fontsans', 'helvetica', 'iomadcertificate');
        set_config('fontserif', 'times', 'iomadcertificate');
        $actor = $USER;
        $hadcertuser = array_key_exists('certuser', $GLOBALS);
        $certuser = $GLOBALS['certuser'] ?? null;
        try {
            \core\session\manager::set_user(get_admin());
            $pdf = \local_iomadcertregen\local\adapter::render($certificate, $learner, $cm, $course, $issue);
            $GLOBALS['certuser'] = $learner;
            iomadcertificate_save_pdf($pdf, $issue->id, 'original.pdf', $context->id);
        } finally {
            \core\session\manager::set_user($actor);
            if ($hadcertuser) {
                $GLOBALS['certuser'] = $certuser;
            } else {
                unset($GLOBALS['certuser']);
            }
        }
        $this->evidence = [
            'issue' => $DB->get_record('iomadcertificate_issues', ['id' => $issue->id]),
            'contextid' => $context->id, 'hash' => hash('sha256', $pdf),
        ];
        // The administrator is correcting the template text after the original issue.
        $DB->set_field('iomadcertificate', 'customtext', 'Corrected fictional template', ['id' => $certificate->id]);
    }

    /**
     * Create enough real saved originals to cross the list's page boundary.
     *
     * @Given /^(\d+) issued IOMAD certificates exist in "([^"]*)"$/
     * @param int $count Total certificates, including the background issue.
     * @param string $activity Activity name.
     */
    public function issued_certificates_exist(int $count, string $activity): void {
        global $DB;
        if ($count < 2 || $count > 1001) {
            throw new InvalidArgumentException('Acceptance volume must be between 2 and 1001 issues.');
        }
        $generator = behat_util::get_data_generator();
        for ($number = 1; $number < $count; $number++) {
            $username = 'certificatevolume' . $number;
            $generator->create_user([
                'username' => $username, 'firstname' => 'Example',
                'lastname' => 'Volume' . str_pad((string)$number, 3, '0', STR_PAD_LEFT),
            ]);
            $this->an_iomad_certificate_has_been_issued($username, $activity);
        }
        $certificate = $DB->get_record('iomadcertificate', ['name' => $activity], '*', MUST_EXIST);
        $issues = $DB->count_records('iomadcertificate_issues', ['iomadcertificateid' => $certificate->id]);
        $originals = $DB->count_records('files', [
            'contextid' => $this->evidence['contextid'], 'component' => 'mod_iomadcertificate',
            'filearea' => 'issue', 'filename' => 'original.pdf',
        ]);
        if ($issues !== $count || $originals !== $count) {
            throw new Exception('The volume fixture did not create every native issue and saved original.');
        }
        $this->evidence['volume'] = $count;
    }

    /**
     * Open the real module-context entry point through the normal HTTP session.
     *
     * @When /^I open the certificate regenerator for "([^"]*)"$/
     * @param string $activity Activity name.
     */
    public function i_open_the_certificate_regenerator(string $activity): void {
        global $DB;
        $certificate = $DB->get_record('iomadcertificate', ['name' => $activity], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('iomadcertificate', $certificate->id, 0, false, MUST_EXIST);
        $started = microtime(true);
        $this->execute('behat_general::i_visit', ['/local/iomadcertregen/index.php?id=' . $cm->id]);
        if (isset($this->evidence['volume'])) {
            printf("[Certificate list: %d saved issues; %.3f seconds]\n", $this->evidence['volume'], microtime(true) - $started);
        }
    }

    /**
     * Check an expected HTTP permission error, then restore the normal test page.
     *
     * @Then /^the certificate regenerator refuses my access to "([^"]*)"$/
     * @param string $activity Activity name.
     */
    public function the_certificate_regenerator_refuses_access(string $activity): void {
        global $DB;
        $certificate = $DB->get_record('iomadcertificate', ['name' => $activity], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('iomadcertificate', $certificate->id, 0, false, MUST_EXIST);
        $session = $this->getSession();
        $previousurl = $session->getCurrentUrl();
        try {
            // This expected error is asserted here; standard hooks still inspect the restored page.
            $session->visit($this->locate_path('/local/iomadcertregen/index.php?id=' . $cm->id));
            $text = $session->getPage()->getText();
            if (
                $session->getStatusCode() < 400 || !str_contains($text, 'Error code: nopermissions') ||
                    !str_contains($text, 'Prepare and confirm corrected certificate copies') ||
                    $DB->record_exists('local_iomadcertregen_op', ['cmid' => $cm->id])
            ) {
                throw new Exception('The authenticated beneficiary did not receive the expected permission denial.');
            }
        } finally {
            $session->visit($previousurl);
        }
    }

    /**
     * Capture the real preparation before the confirmation POST.
     *
     * @Then /^the prepared certificate bytes are recorded for review$/
     */
    public function the_prepared_certificate_bytes_are_recorded(): void {
        global $DB;
        $operation = $DB->get_record('local_iomadcertregen_op', ['issueid' => $this->evidence['issue']->id], '*', MUST_EXIST);
        $copy = \local_iomadcertregen\local\service::file($operation);
        if ($operation->status !== 'prepared' || !$copy || hash('sha256', $copy->get_content()) !== $operation->pdfhash) {
            throw new Exception('The preparation does not contain consistent reviewed bytes.');
        }
        $this->evidence['preparedhash'] = $operation->pdfhash;
    }

    /**
     * Receive the protected PDF and verify the three real Moodle audit records.
     *
     * @Then /^I download the confirmed certificate with its audit trail$/
     */
    public function i_download_the_confirmed_certificate_with_its_audit_trail(): void {
        global $DB;
        $session = $this->getSession();
        $link = $session->getPage()->findLink('Download confirmed PDF');
        if (!$link) {
            throw new Exception('The confirmed download link is missing.');
        }
        $previousurl = $session->getCurrentUrl();
        try {
            $session->visit($link->getAttribute('href'));
            $headers = array_change_key_case($session->getResponseHeaders(), CASE_LOWER);
            $type = implode(', ', (array)($headers['content-type'] ?? []));
            $disposition = implode(', ', (array)($headers['content-disposition'] ?? []));
            $bytes = $session->getDriver()->getContent();
            if (
                $session->getStatusCode() !== 200 || !str_starts_with($type, 'application/pdf') ||
                    !str_contains($disposition, 'attachment') ||
                    hash('sha256', $bytes) !== $this->evidence['preparedhash']
            ) {
                throw new Exception('The HTTP download did not preserve the reviewed PDF and expected headers.');
            }
        } finally {
            $session->visit($previousurl);
        }
        $operation = $DB->get_record('local_iomadcertregen_op', ['issueid' => $this->evidence['issue']->id], '*', MUST_EXIST);
        $logs = $DB->get_records('logstore_standard_log', ['component' => 'local_iomadcertregen'], 'id');
        $classes = [
            '\\local_iomadcertregen\\event\\copy_prepared',
            '\\local_iomadcertregen\\event\\copy_confirmed',
            '\\local_iomadcertregen\\event\\copy_downloaded',
        ];
        if (array_values(array_column($logs, 'eventname')) !== $classes) {
            throw new Exception('The web workflow did not record the expected preparation, confirmation and download events.');
        }
        foreach ($logs as $log) {
            if (
                (int)$log->objectid !== (int)$operation->id || (int)$log->contextid !== (int)$operation->contextid ||
                    (int)$log->userid !== (int)$operation->actorid || (int)$log->relateduserid !== (int)$operation->userid
            ) {
                throw new Exception('The audit trail attributes the operation to the wrong activity or participant.');
            }
        }
    }

    /**
     * Check native evidence and exact prepared bytes after the real form submissions.
     *
     * @Then /^the corrected certificate retains its native evidence$/
     */
    public function the_corrected_certificate_retains_its_native_evidence(): void {
        global $DB;
        $expected = $this->evidence['issue'];
        $issue = $DB->get_record('iomadcertificate_issues', ['id' => $expected->id], '*', MUST_EXIST);
        $file = get_file_storage()->get_file(
            $this->evidence['contextid'],
            'mod_iomadcertificate',
            'issue',
            $issue->id,
            '/',
            'original.pdf'
        );
        if ($issue != $expected || !$file || hash('sha256', $file->get_content()) !== $this->evidence['hash']) {
            throw new Exception('Native issue or original PDF changed during the web workflow.');
        }
        $operation = $DB->get_record('local_iomadcertregen_op', ['issueid' => $issue->id], '*', MUST_EXIST);
        $copy = \local_iomadcertregen\local\service::file($operation);
        if (
            $operation->status !== 'confirmed' || !$copy ||
                hash('sha256', $copy->get_content()) !== $operation->pdfhash ||
                $operation->pdfhash === $this->evidence['hash'] ||
                $operation->pdfhash !== $this->evidence['preparedhash']
        ) {
            throw new Exception('The corrected PDF is missing, unchanged or inconsistent with confirmation.');
        }
    }
}
