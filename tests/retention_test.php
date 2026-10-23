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

use local_iomadcertregen\local\records;
use local_iomadcertregen\local\service;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/certificate_testcase.php');

/**
 * Integration tests for retention behaviour.
 *
 * @covers \local_iomadcertregen\local\retention
 * @covers \local_iomadcertregen\task\cleanup
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\local\retention::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\task\cleanup::class)]
final class retention_test extends certificate_testcase {
    /**
     * Retention settings are captured at preparation and confirmation separately.
     */
    public function test_retention_deadlines_are_captured_per_transition(): void {
        [, , $cm, , $issue] = $this->fixture();
        set_config('preparationlifetime', 2 * HOURSECS, 'local_iomadcertregen');
        $operation = service::prepare($cm->id, $issue->id, str_repeat('d1', 16), sesskey());
        $this->assertSame((int)$operation->timecreated + 2 * HOURSECS, (int)$operation->timeexpires);
        set_config('preparationlifetime', HOURSECS, 'local_iomadcertregen');
        $stored = records::operation($cm->id, $operation->id);
        $this->assertSame((int)$operation->timeexpires, (int)$stored->timeexpires);
        set_config('confirmedlifetime', 30 * DAYSECS, 'local_iomadcertregen');
        $confirmed = service::confirm($cm->id, $operation->id, sesskey());
        $this->assertSame((int)$confirmed->timemodified + 30 * DAYSECS, (int)$confirmed->timeexpires);
        set_config('confirmedlifetime', DAYSECS, 'local_iomadcertregen');
        $stored = records::operation($cm->id, $operation->id);
        $this->assertSame((int)$confirmed->timeexpires, (int)$stored->timeexpires);
    }

    /**
     * Enabling retention does not retroactively erase an unlimited confirmed copy.
     */
    public function test_existing_unlimited_copy_survives_new_retention_policy(): void {
        global $DB;
        [, , $cm, , $issue] = $this->fixture();
        $operation = service::prepare($cm->id, $issue->id, str_repeat('e1', 16), sesskey());
        $confirmed = service::confirm($cm->id, $operation->id, sesskey());
        $this->assertSame(0, (int)$confirmed->timeexpires);
        set_config('confirmedlifetime', DAYSECS, 'local_iomadcertregen');
        $DB->set_field('local_iomadcertregen_op', 'timemodified', time() - 60 * DAYSECS, ['id' => $operation->id]);
        (new \local_iomadcertregen\task\cleanup())->execute();
        $this->assertNotFalse(service::file(records::operation($cm->id, $operation->id)));
        $this->assertTrue($DB->record_exists('iomadcertificate_issues', ['id' => $issue->id]));
    }

    /**
     * A confirmed copy expires before cleanup, which preserves the native PDF.
     */
    public function test_expired_confirmed_copy_is_inaccessible_and_cleaned(): void {
        global $DB;
        [, , $cm, , $issue, $context] = $this->fixture();
        set_config('confirmedlifetime', DAYSECS, 'local_iomadcertregen');
        $operation = service::prepare($cm->id, $issue->id, str_repeat('f1', 16), sesskey());
        service::confirm($cm->id, $operation->id, sesskey());
        $DB->set_field('local_iomadcertregen_op', 'timeexpires', time() - 1, ['id' => $operation->id]);
        try {
            records::operation($cm->id, $operation->id);
            $this->fail('An expired confirmed copy must be inaccessible.');
        } catch (\moodle_exception $error) {
            $this->assertSame('unavailable', $error->errorcode);
        }
        (new \local_iomadcertregen\task\cleanup())->execute();
        $this->assertFalse($DB->record_exists('local_iomadcertregen_op', ['id' => $operation->id]));
        $this->assertFalse(service::file($operation));
        $this->assertTrue($DB->record_exists('iomadcertificate_issues', ['id' => $issue->id]));
        $this->assertNotFalse(get_file_storage()->get_file(
            $context->id,
            'mod_iomadcertificate',
            'issue',
            $issue->id,
            '/',
            'original.pdf'
        ));
    }
}
