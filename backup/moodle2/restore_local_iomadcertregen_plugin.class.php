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

/**
 * Restore confirmed copies only after the native issue and users have been mapped.
 */
class restore_local_iomadcertregen_plugin extends restore_local_plugin {
    /** @var array Pending copies, scoped to this course module. */
    private array $copies = [];

    /**
     * Define the module-level copy path, excluding backups without user data.
     *
     * @return restore_path_element[] Paths.
     */
    protected function define_module_plugin_structure() {
        if (
            $this->task->get_modulename() !== 'iomadcertificate' || !$this->get_setting_value('userinfo') ||
                !is_siteadmin($this->task->get_userid())
        ) {
            return [];
        }
        return [new restore_path_element('local_iomadcertregen_copy', $this->get_pathfor('/copy'))];
    }

    /**
     * Defer copies until the native activity's separate XML has been processed.
     *
     * @param array $data Backed-up operation.
     */
    public function process_local_iomadcertregen_copy($data): void {
        $copy = (object)$data;
        if (!$this->valid_metadata($copy)) {
            $this->warn();
            return;
        }
        $this->copies[] = $copy;
    }

    /**
     * Refuse incomplete or nested metadata before resolving any foreign mapping.
     *
     * @param stdClass $copy Candidate backup metadata.
     * @return bool Whether all required scalar values and numbers are valid.
     */
    private function valid_metadata(stdClass $copy): bool {
        foreach (
            ['id', 'issueid', 'userid', 'actorid', 'sourcehash', 'pdfhash',
                'timecreated', 'timemodified', 'timeexpires', 'issuecode', 'issuedate'] as $field
        ) {
            if (!isset($copy->$field) || !is_scalar($copy->$field)) {
                return false;
            }
        }
        foreach (['id', 'issueid', 'userid', 'actorid', 'timecreated', 'timemodified', 'issuedate'] as $field) {
            if (filter_var($copy->$field, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                return false;
            }
        }
        return filter_var($copy->timeexpires, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) !== false;
    }

    /**
     * Map original relationships and restore the unchanged reviewed PDF.
     */
    public function after_restore_module(): void {
        foreach ($this->copies as $copy) {
            $operation = $this->restore_copy($copy);
            if (!$operation) {
                $this->warn();
                continue;
            }
            $this->set_mapping('local_iomadcertregen_op', $copy->id, $operation->id, true);
            $this->add_related_files('local_iomadcertregen', 'document', 'local_iomadcertregen_op', null, $copy->id);
            if (!$this->valid_file($operation)) {
                \local_iomadcertregen\local\records::delete($operation);
                $this->warn();
            }
        }
        $this->copies = [];
    }

    /**
     * Create only owned metadata after validating the restored native bindings.
     *
     * Historical timestamps and expiry are never shifted or extended.
     *
     * @param \stdClass $copy Backup values.
     * @return \stdClass|null Restored operation, or null when its evidence cannot be preserved.
     */
    private function restore_copy(\stdClass $copy): ?\stdClass {
        global $DB;
        $issueid = $this->get_mappingid('iomadcertificate_issue', $copy->issueid);
        $userid = $this->get_mappingid('user', $copy->userid);
        $actorid = $this->get_mappingid('user', $copy->actorid);
        if (!$this->mapped_users_exist((int)$userid, (int)$actorid)) {
            return null;
        }
        $issue = $DB->get_record('iomadcertificate_issues', [
            'id' => $issueid, 'userid' => $userid, 'iomadcertificateid' => $this->task->get_activityid(),
        ]);
        if (
            !$issue || $issue->code !== $copy->issuecode ||
                (int)$issue->timecreated !== (int)$copy->issuedate ||
                \local_iomadcertregen\local\retention::expired($copy)
        ) {
            return null;
        }
        if (
            !preg_match('/^[a-f0-9]{64}$/D', $copy->pdfhash) ||
                !preg_match('/^[a-f0-9]{64}$/D', $copy->sourcehash)
        ) {
            return null;
        }
        $operation = (object)[
            'contextid' => $this->task->get_contextid(), 'cmid' => $this->task->get_moduleid(),
            'issueid' => $issueid, 'userid' => $userid, 'actorid' => $actorid, 'status' => 'confirmed',
            'requestkey' => hash('sha256', $this->get_restoreid() . ':' . $copy->id),
            'sourcehash' => $copy->sourcehash, 'pdfhash' => $copy->pdfhash,
            'mailstatus' => self::mail_status($copy),
            'mailtime' => max(0, (int)($copy->mailtime ?? 0)), 'mailrequestkey' => '',
            'timecreated' => $copy->timecreated, 'timemodified' => $copy->timemodified, 'timeexpires' => $copy->timeexpires,
        ];
        $operation->id = $DB->insert_record('local_iomadcertregen_op', $operation);
        return $operation;
    }

    /**
     * Preserve bounded mail history; an interrupted attempt must never be retried on restore.
     *
     * @param stdClass $copy Backup data, including older archives without mail history.
     * @return string Safe historical state.
     */
    private static function mail_status(stdClass $copy): string {
        $status = $copy->mailstatus ?? 'notrequested';
        return in_array($status, ['notrequested', 'submitted', 'failed', 'unknown'], true) ? $status : 'unknown';
    }

    /**
     * Require a bounded PDF with the exact reviewed bytes after native file restore.
     *
     * @param \stdClass $operation Restored operation.
     * @return bool Whether the owned file is valid.
     */
    private function valid_file(\stdClass $operation): bool {
        $file = \local_iomadcertregen\local\service::file($operation);
        if (
            !$file || $file->get_mimetype() !== 'application/pdf' ||
                $file->get_filesize() > \local_iomadcertregen\local\adapter::MAX_PDF_BYTES
        ) {
            return false;
        }
        $content = $file->get_content();
        try {
            \local_iomadcertregen\local\adapter::require_valid_pdf($content);
        } catch (moodle_exception $error) {
            return false;
        }
        return hash_equals($operation->pdfhash, hash('sha256', $content));
    }

    /**
     * Require real, undeleted mapped users; never fall back to old numeric IDs.
     *
     * @param int $userid Mapped beneficiary.
     * @param int $actorid Mapped preparing administrator.
     * @return bool Whether both users remain available.
     */
    private function mapped_users_exist(int $userid, int $actorid): bool {
        global $DB;
        return $userid > 0 && $actorid > 0 &&
            $DB->record_exists('user', ['id' => $userid, 'deleted' => 0]) &&
            $DB->record_exists('user', ['id' => $actorid, 'deleted' => 0]);
    }

    /**
     * Log a bounded warning without exporting names, codes or document content.
     */
    private function warn(): void {
        $this->step->log(get_string('restorecopyomitted', 'local_iomadcertregen'), backup::LOG_WARNING);
    }
}
