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

use local_iomadcertregen\local\{delivery, mail_transport, records, service};
use local_iomadcertregen\privacy\provider;
use core_privacy\local\request\{approved_contextlist, writer};

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/delivery_testcase.php');

/**
 * Mail access, recipient eligibility and concurrency protections.
 *
 * @covers \local_iomadcertregen\local\delivery
 * @covers \local_iomadcertregen\local\mail_transport
 */
#[\PHPUnit\Framework\Attributes\CoversClass(delivery::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(mail_transport::class)]
final class delivery_access_test extends delivery_testcase {
    /**
     * Disabled site mail is not misreported as a successful SMTP submission.
     */
    public function test_site_mail_disabled_is_not_a_success(): void {
        global $CFG;
        [$data, $operation] = $this->confirmed();
        $CFG->noemailever = true;
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('emaildisabled', 'local_iomadcertregen'));
        delivery::send($operation->cmid, $operation->id, str_repeat('b8', 16), delivery::recipient_hash($data[1]), sesskey());
    }

    /**
     * The beneficiary destination must match the address explicitly reviewed on the form.
     */
    public function test_changed_destination_requires_new_review(): void {
        [, $operation] = $this->confirmed();
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('emailrecipientchanged', 'local_iomadcertregen'));
        delivery::send($operation->cmid, $operation->id, str_repeat('c8', 16), str_repeat('0', 64), sesskey());
    }

    /**
     * Core-suppressed accounts and invalid addresses must not be recorded as submitted.
     */
    public function test_ineligible_recipient_is_refused(): void {
        global $DB;
        [$data, $operation] = $this->confirmed();
        $initial = clone $data[1];
        $cases = [['auth', 'nologin'], ['emailstop', 1], ['confirmed', 0], ['email', 'learner@example.invalid']];
        foreach ($cases as [$field, $value]) {
            $user = clone $initial;
            $user->$field = $value;
            $DB->update_record('user', $user);
            try {
                delivery::send($operation->cmid, $operation->id, str_repeat('d8', 16), delivery::recipient_hash($user), sesskey());
                $this->fail('An ineligible recipient must be refused.');
            } catch (\moodle_exception $error) {
                $this->assertSame('emailrecipientinvalid', $error->errorcode);
            }
        }
        $this->assertSame('notrequested', records::operation($operation->cmid, $operation->id)->mailstatus);
    }

    /**
     * A mail action cannot be submitted using an invalid session key.
     */
    public function test_csrf_is_refused(): void {
        [$data, $operation] = $this->confirmed();
        $this->expectException(\moodle_exception::class);
        delivery::send($operation->cmid, $operation->id, str_repeat('e8', 16), delivery::recipient_hash($data[1]), 'wrong');
    }

    /**
     * Another administrator may view a confirmed copy but cannot send as its preparing actor.
     */
    public function test_other_actor_is_refused(): void {
        global $CFG;
        [$data, $operation] = $this->confirmed();
        $other = $this->getDataGenerator()->create_user();
        $CFG->siteadmins .= ',' . $other->id;
        $this->setUser($other);
        $this->expectException(\moodle_exception::class);
        delivery::send($operation->cmid, $operation->id, str_repeat('f8', 16), delivery::recipient_hash($data[1]), sesskey());
    }

    /**
     * Lost or altered attachments cannot be substituted at the time of sending.
     */
    public function test_missing_pdf_is_refused(): void {
        [$data, $operation] = $this->confirmed();
        service::file($operation)->delete();
        $this->expectException(\moodle_exception::class);
        delivery::send($operation->cmid, $operation->id, str_repeat('a9', 16), delivery::recipient_hash($data[1]), sesskey());
    }

    /**
     * A newly prepared copy cannot be emailed before the review is confirmed.
     */
    public function test_unconfirmed_copy_is_refused(): void {
        [$data] = $this->confirmed();
        $prepared = service::prepare($data[2]->id, $data[4]->id, str_repeat('c9', 16), sesskey());
        $this->expectException(\moodle_exception::class);
        delivery::send($prepared->cmid, $prepared->id, str_repeat('d9', 16), delivery::recipient_hash($data[1]), sesskey());
    }

    /**
     * A concurrent confirmation, deletion or email holds the same issue lock and blocks delivery.
     */
    public function test_busy_issue_is_not_emailed(): void {
        global $DB;
        [$data, $operation] = $this->confirmed();
        // MySQL advisory locks are reentrant on one connection: hold the competing lock independently.
        $config = $DB->export_dbconfig();
        $connection = \moodle_database::get_driver_instance($config->dbtype, $config->dblibrary);
        $connection->connect(
            $config->dbhost,
            $config->dbuser,
            $config->dbpass,
            $config->dbname,
            $config->prefix,
            $config->dboptions ?? []
        );
        $originaldb = $DB;
        try {
            $DB = $connection;
            $factory = \core\lock\lock_config::get_lock_factory('local_iomadcertregen');
        } finally {
            $DB = $originaldb;
        }
        $lock = $factory->get_lock('issue:' . $operation->issueid, 0);
        $this->assertNotFalse($lock);
        try {
            $this->expectException(\moodle_exception::class);
            $this->expectExceptionMessage(get_string('busy', 'local_iomadcertregen'));
            delivery::send($operation->cmid, $operation->id, str_repeat('e9', 16), delivery::recipient_hash($data[1]), sesskey());
        } finally {
            $lock->release();
            $connection->dispose();
        }
    }
    /**
     * A real Moodle form submission requires explicit acknowledgement before returning send data.
     */
    public function test_email_form_requires_acknowledgement(): void {
        [$data, $operation] = $this->confirmed();
        $fields = ['id' => $operation->cmid, 'operationid' => $operation->id,
            'requestkey' => str_repeat('f9', 16), 'recipienthash' => delivery::recipient_hash($data[1])];
        foreach ([0, 1] as $acknowledge) {
            \local_iomadcertregen\form\email::mock_submit($fields + ['acknowledge' => $acknowledge]);
            $form = new \local_iomadcertregen\form\email(null, $fields);
            if ($acknowledge) {
                $this->assertNotNull($form->get_data());
            } else {
                $this->assertNull($form->get_data());
            }
        }
    }
}
