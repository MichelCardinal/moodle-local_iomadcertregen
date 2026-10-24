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

namespace local_iomadcertregen\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_iomadcertregen\local\records;

/**
 * Privacy for both beneficiaries and administrators, including owned PDF files.
 */
final class provider implements core_userlist_provider, metadata_provider, plugin_provider {
    /**
     * Describe stored personal information and file storage.
     *
     * @param collection $collection Metadata collection.
     * @return collection Metadata.
     */
    public static function get_metadata(collection $collection): collection {
        $fields = [];
        foreach (
            ['contextid', 'cmid', 'issueid', 'userid', 'actorid', 'status', 'requestkey',
                'sourcehash', 'pdfhash', 'timecreated', 'timemodified', 'timeexpires',
                'mailstatus', 'mailtime', 'mailrequestkey', 'publishedtime', 'issuehash', 'batchkey'] as $field
        ) {
            $fields[$field] = 'privacy:metadata:operation:' . $field;
        }
        $collection->add_database_table('local_iomadcertregen_op', $fields, 'privacy:metadata:operation');
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:files');
        $collection->add_external_location_link('email', [
            'email' => 'privacy:metadata:email:address',
            'pdf' => 'privacy:metadata:email:pdf',
        ], 'privacy:metadata:email');
        return $collection;
    }

    /**
     * Find contexts containing either role of the specified user.
     *
     * @param int $userid User identifier.
     * @return contextlist Contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $list = new contextlist();
        $list->add_from_sql(
            "SELECT DISTINCT contextid FROM {local_iomadcertregen_op} WHERE userid = :beneficiary OR actorid = :actor",
            ['beneficiary' => $userid, 'actor' => $userid]
        );
        return $list;
    }

    /**
     * Find beneficiaries and actors in a context.
     *
     * @param userlist $userlist User collection.
     */
    public static function get_users_in_context(userlist $userlist): void {
        if ($userlist->get_context()->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        foreach (['userid', 'actorid'] as $field) {
            $userlist->add_from_sql(
                $field,
                "SELECT $field FROM {local_iomadcertregen_op} WHERE contextid = :contextid",
                ['contextid' => $userlist->get_context()->id]
            );
        }
    }

    /**
     * Export approved operation records and only the requesting beneficiary's PDFs.
     *
     * @param approved_contextlist $contextlist Approved contexts and user.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $operations = $DB->get_records_select(
                'local_iomadcertregen_op',
                'contextid = :contextid AND (userid = :beneficiary OR actorid = :actor)',
                ['contextid' => $context->id, 'beneficiary' => $userid, 'actor' => $userid],
                'id'
            );
            foreach ($operations as $operation) {
                $path = [get_string('pluginname', 'local_iomadcertregen'), (string)$operation->id];
                $data = clone $operation;
                unset($data->requestkey, $data->mailrequestkey, $data->batchkey);
                writer::with_context($context)->export_data($path, $data);
                // A preparing administrator's request must not disclose another learner's PDF.
                if ((int)$operation->userid === (int)$userid) {
                    foreach (['document', 'original'] as $area) {
                        writer::with_context($context)->export_area_files(
                            $path,
                            'local_iomadcertregen',
                            $area,
                            $operation->id
                        );
                    }
                }
            }
        }
    }

    /**
     * Delete every owned copy in an approved context.
     *
     * @param \context $context Approved context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context->contextlevel === CONTEXT_MODULE) {
            self::remove('contextid = :contextid', ['contextid' => $context->id]);
        }
    }

    /**
     * Delete copies involving the approved user only in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved request.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_MODULE) {
                self::remove(
                    'contextid = :contextid AND (userid = :beneficiary OR actorid = :actor)',
                    ['contextid' => $context->id, 'beneficiary' => $userid, 'actor' => $userid]
                );
            }
        }
    }

    /**
     * Delete copies involving approved users in one approved context.
     *
     * @param approved_userlist $userlist Approved request.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        if ($userlist->get_context()->contextlevel !== CONTEXT_MODULE || !$userlist->get_userids()) {
            return;
        }
        [$beneficiarysql, $beneficiaryparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED, 'b');
        [$actorsql, $actorparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED, 'a');
        self::remove(
            "contextid = :contextid AND (userid $beneficiarysql OR actorid $actorsql)",
            ['contextid' => $userlist->get_context()->id] + $beneficiaryparams + $actorparams
        );
    }

    /**
     * Remove owned metadata and content while serialising with confirmation.
     *
     * @param string $where Parameterised selection.
     * @param array $params Parameters.
     */
    private static function remove(string $where, array $params): void {
        global $DB;
        foreach ($DB->get_records_select('local_iomadcertregen_op', $where, $params) as $operation) {
            $lock = \core\lock\lock_config::get_lock_factory('local_iomadcertregen')
                ->get_lock('issue:' . $operation->issueid, 10);
            if (!$lock) {
                throw new \moodle_exception('busy', 'local_iomadcertregen');
            }
            try {
                records::delete($operation);
            } finally {
                $lock->release();
            }
        }
    }
}
