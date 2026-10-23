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

use local_iomadcertregen\local\service;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/certificate_testcase.php');

/**
 * Integration tests for service behaviour.
 *
 * @covers \local_iomadcertregen\local\service
 * @covers \local_iomadcertregen\local\records
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\local\service::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\local\records::class)]
final class service_test extends certificate_testcase {
    /**
     * The entire operation preserves the original native rows and PDF.
     */
    public function test_prepare_confirm_and_repeat_preserve_original(): void {
        global $DB;
        $data = $this->fixture();
        [, $learner, $cm, , $issue, $context] = $data;
        $original = get_file_storage()->get_file(
            $context->id,
            'mod_iomadcertificate',
            'issue',
            $issue->id,
            '/',
            'original.pdf'
        );
        $before = $original->get_contenthash();
        $native = $DB->get_record('iomadcertificate_issues', ['id' => $issue->id]);
        $events = $this->redirectEvents();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('a', 32), sesskey());
        $this->assertSame('prepared', $operation->status);
        $this->assertSame((int)$learner->id, (int)$operation->userid);
        $again = service::prepare($cm->id, $issue->id, str_repeat('a', 32), sesskey());
        $this->assertEquals($operation->id, $again->id);
        $bytes = service::file($operation)->get_content();
        $confirmed = service::confirm($cm->id, $operation->id, sesskey());
        $this->assertSame('confirmed', $confirmed->status);
        $this->assertSame($bytes, service::file($confirmed)->get_content());
        $this->assertEquals($confirmed->id, service::confirm($cm->id, $operation->id, sesskey())->id);
        $this->assertEquals(1, $DB->count_records('local_iomadcertregen_op'));
        $this->assertEquals($native, $DB->get_record('iomadcertificate_issues', ['id' => $issue->id]));
        $original = get_file_storage()->get_file(
            $context->id,
            'mod_iomadcertificate',
            'issue',
            $issue->id,
            '/',
            'original.pdf'
        );
        $this->assertSame($before, $original->get_contenthash());
        $this->assertCount(2, $events->get_events());
        $events->close();
    }

    /**
     * A changed native activity invalidates a prepared copy at confirmation.
     */
    public function test_changed_source_is_refused(): void {
        global $DB;
        [$module, , $cm, , $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('f', 32), sesskey());
        $DB->set_field('iomadcertificate', 'customtext', 'Changed after preparation', ['id' => $module->id]);
        try {
            service::confirm($cm->id, $operation->id, sesskey());
            $this->fail('Changed inputs must be reviewed again.');
        } catch (\moodle_exception $error) {
            $this->assertSame('sourcechanged', $error->errorcode);
        }
        $this->assertSame('prepared', $DB->get_field('local_iomadcertregen_op', 'status', ['id' => $operation->id]));
    }

    /**
     * A contended issue cannot start another preparation.
     */
    public function test_lock_contention_is_refused(): void {
        global $DB;
        [, , $cm, , $issue] = $this->fixture();
        // MySQL advisory locks are connection-scoped: use an independent connection.
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
        $lock = $factory->get_lock('issue:' . $issue->id, 0);
        $this->assertNotFalse($lock);
        try {
            service::prepare($cm->id, $issue->id, str_repeat('7', 32), sesskey());
            $this->fail('A concurrent operation must be refused.');
        } catch (\moodle_exception $error) {
            $this->assertSame('busy', $error->errorcode);
        } finally {
            $lock->release();
            $connection->dispose();
        }
    }

    /**
     * File tampering is detected before confirmation.
     */
    public function test_file_integrity_is_checked_at_confirmation(): void {
        global $DB;
        [, , $cm, , $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('9', 32), sesskey());
        $DB->set_field('local_iomadcertregen_op', 'pdfhash', str_repeat('0', 64), ['id' => $operation->id]);
        $this->expectException(\moodle_exception::class);
        service::confirm($cm->id, $operation->id, sesskey());
    }
}
