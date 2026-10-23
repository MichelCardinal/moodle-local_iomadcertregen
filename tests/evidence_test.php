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

use local_iomadcertregen\local\adapter;
use local_iomadcertregen\local\records;
use local_iomadcertregen\local\service;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/certificate_testcase.php');

/**
 * Integration tests for evidence behaviour.
 *
 * @covers \local_iomadcertregen\local\adapter
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\local\adapter::class)]
final class evidence_test extends certificate_testcase {
    /**
     * Preliminary records do not permit generation without a saved native PDF.
     */
    public function test_preliminary_issue_without_pdf_is_refused(): void {
        global $DB;
        [, , $cm, , $issue] = $this->native_fixture();
        try {
            service::prepare($cm->id, $issue->id, str_repeat('b', 32), sesskey());
            $this->fail('A preliminary record must not permit correction.');
        } catch (\moodle_exception $error) {
            $this->assertSame('nooriginal', $error->errorcode);
        }
        $this->assertEquals(0, $DB->count_records('local_iomadcertregen_op'));
        $this->assertEquals(1, $DB->count_records('iomadcertificate_issues'));
    }

    /**
     * Duplicate native issues are refused instead of choosing an arbitrary row.
     */
    public function test_duplicate_native_issue_is_refused(): void {
        global $DB;
        [, , $cm, , $issue] = $this->fixture();
        $duplicate = clone $issue;
        unset($duplicate->id);
        $DB->insert_record('iomadcertificate_issues', $duplicate);
        $this->expectException(\moodle_exception::class);
        records::issue($cm->id, $issue->id);
    }

    /**
     * Unknown templates and outcomes fail before rendering PHP.
     */
    public function test_unqualified_template_and_outcome_are_refused(): void {
        [$module] = $this->native_fixture();
        $module->iomadcertificatetype = '../company';
        try {
            adapter::require_compatible($module);
            $this->fail('Unknown templates must be refused.');
        } catch (\moodle_exception $error) {
            $this->assertSame('unsupportedtemplate', $error->errorcode);
        }
        $module->iomadcertificatetype = 'A4_non_embedded';
        $module->printoutcome = 1;
        $this->expectException(\moodle_exception::class);
        adapter::require_compatible($module);
    }

    /**
     * The evidence adapter supports Moodle URLs without slash arguments.
     */
    public function test_native_evidence_without_slash_arguments(): void {
        global $CFG;
        [$module, , , , $issue, $context] = $this->fixture();
        $CFG->slasharguments = 0;
        $url = adapter::original_url($module, $issue, $context);
        $this->assertStringContainsString('?file=', $url);
    }

    /**
     * Both qualified native download revisions have an explicit, tested capability.
     */
    public function test_native_saved_download_capability_matches_revision(): void {
        [$module, $learner, $cm, $course, $issue, $context] = $this->fixture();
        if (adapter::original_download_available()) {
            $this->assertStringContainsString('original.pdf', adapter::original_url($module, $issue, $context));
            $this->assertNotFalse(get_file_storage()->get_file(
                $context->id,
                'mod_iomadcertificate',
                'issue',
                $issue->id,
                '/',
                'original.pdf'
            ));
            return;
        }
        $this->assertFalse(adapter::original_download_available());
        $existed = array_key_exists('certuser', $GLOBALS);
        $previous = $GLOBALS['certuser'] ?? null;
        $GLOBALS['certuser'] = $learner;
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('is_external_file');
        try {
            iomadcertificate_pluginfile($course, $cm, $context, 'issue', [$issue->id, 'original.pdf'], true);
        } finally {
            if ($existed) {
                $GLOBALS['certuser'] = $previous;
            } else {
                unset($GLOBALS['certuser']);
            }
        }
    }
}
