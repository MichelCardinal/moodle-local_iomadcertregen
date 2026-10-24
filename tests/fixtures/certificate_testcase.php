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
use core_privacy\local\request\writer;

/**
 * Shared native fixtures for the component's integration tests.
 */
abstract class certificate_testcase extends \advanced_testcase {
    /**
     * Create a preliminary native issue without a saved original.
     *
     * @return array Native fixture records.
     */
    protected function native_fixture(): array {
        global $CFG, $DB;
        $this->resetAfterTest();
        writer::reset();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/iomadcertificate/lib.php');
        require_once($CFG->dirroot . '/mod/iomadcertificate/locallib.php');
        set_config('fontsans', 'helvetica', 'iomadcertificate');
        set_config('fontserif', 'times', 'iomadcertificate');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $learner = $generator->create_user(['firstname' => 'Example', 'lastname' => 'Learner']);
        $module = $generator->create_module('iomadcertificate', [
            'course' => $course->id, 'iomadcertificatetype' => 'A4_non_embedded',
            'printnumber' => 1, 'printdate' => 1, 'datefmt' => 3,
        ]);
        $issue = (object)[
            'userid' => $learner->id, 'iomadcertificateid' => $module->id,
            'code' => 'LAB0000001', 'timecreated' => 1705320000,
        ];
        $issue->id = $DB->insert_record('iomadcertificate_issues', $issue);
        $cm = get_coursemodule_from_instance('iomadcertificate', $module->id);
        $context = \context_module::instance($cm->id);
        return [$module, $learner, $cm, $course, $issue, $context];
    }

    /**
     * Create a native issue with a PDF saved through the owner's API.
     *
     * @return array Native fixture records.
     */
    protected function fixture(): array {
        $data = $this->native_fixture();
        [$module, $learner, $cm, $course, $issue, $context] = $data;
        // The owner's save function is used only to establish a test fixture.
        $existed = array_key_exists('certuser', $GLOBALS);
        $previous = $GLOBALS['certuser'] ?? null;
        $GLOBALS['certuser'] = $learner;
        try {
            $pdf = adapter::render($module, $learner, $cm, $course, $issue);
            iomadcertificate_save_pdf($pdf, $issue->id, 'original.pdf', $context->id);
        } finally {
            if ($existed) {
                $GLOBALS['certuser'] = $previous;
            } else {
                unset($GLOBALS['certuser']);
            }
        }
        return $data;
    }
}
