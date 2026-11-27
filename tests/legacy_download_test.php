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
 * Historical native download routing integration.
 *
 * @package    local_iomadcertregen
 * @copyright  2026 Michel Cardinal
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcertregen;

use local_iomadcertregen\local\legacy_download;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/publication_testcase.php');

/**
 * The positive legacy route is exercised when its actual historical native variant is installed.
 *
 * @covers \local_iomadcertregen\local\legacy_download
 */
#[\PHPUnit\Framework\Attributes\CoversClass(legacy_download::class)]
final class legacy_download_test extends publication_testcase {
    /**
     * Evaluate a request without leaving global request state changed for another test.
     *
     * @param array $parameters Native activity request parameters.
     * @param string $script Actual script being requested.
     * @return \moodle_url|null Chosen destination.
     */
    private function destination(array $parameters, string $script = '/mod/iomadcertificate/view.php'): ?\moodle_url {
        global $SCRIPT;
        $previous = [$SCRIPT ?? null, $_GET, $_POST];
        try {
            $SCRIPT = $script;
            $_GET = $parameters;
            $_POST = [];
            return legacy_download::destination();
        } finally {
            [$SCRIPT, $_GET, $_POST] = $previous;
        }
    }

    /**
     * Recognise the exact native fixture rather than pretending a modern source is historical.
     *
     * @return bool This environment contains the supported old route.
     */
    private function historical(): bool {
        global $CFG;
        return (int)$CFG->branch === 405 &&
            hash_file('sha256', $CFG->dirroot . '/mod/iomadcertificate/view.php') === legacy_download::VIEW_SHA256;
    }

    /**
     * The early hook cannot affect unrelated pages, guest requests or ordinary activity viewing.
     */
    public function test_unrelated_and_guest_requests_are_not_diverted(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        // The hook manager passes its hook object; this listener needs no payload and ignores CLI requests.
        legacy_download::after_config(new \core\hook\after_config());
        $this->assertNull($this->destination(['action' => 'get', 'id' => 1], '/my/index.php'));
        $this->assertNull($this->destination(['id' => 1]));
        $this->setGuestUser();
        $this->assertNull($this->destination(['action' => 'get', 'id' => 1]));
    }

    /**
     * Initial native delivery stays with the native activity until a correction is published.
     */
    public function test_native_issue_without_publication_is_not_diverted(): void {
        $data = $this->fixture();
        $this->assertNull($this->destination(['action' => 'get', 'id' => $data[2]->id]));
    }

    /**
     * Published corrections route the effective beneficiary without trusting a learner's userid input.
     */
    public function test_publication_uses_the_effective_beneficiary(): void {
        [$data, $operation] = $this->correction();
        $this->publish($data, $operation);
        $parameters = ['action' => 'get', 'id' => $data[2]->id, 'userid' => $data[1]->id];
        $expected = $this->historical() ? new \moodle_url('/local/iomadcertregen/learner.php', [
            'id' => $data[2]->id, 'userid' => $data[1]->id,
        ]) : null;
        $this->assertEquals($expected, $this->destination($parameters));
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($other);
        $this->assertNull($this->destination($parameters));
        $this->setUser($data[1]);
        $parameters['userid'] = $other->id;
        $this->assertEquals($expected, $this->destination($parameters));
    }

    /**
     * A replaced native issue cannot be redirected to an earlier publication.
     */
    public function test_changed_native_issue_is_not_diverted(): void {
        global $DB;
        [$data, $operation] = $this->correction();
        $this->publish($data, $operation);
        $DB->set_field('iomadcertificate_issues', 'code', 'REISSUED', ['id' => $data[4]->id]);
        $this->assertNull($this->destination([
            'action' => 'get', 'id' => $data[2]->id, 'userid' => $data[1]->id,
        ]));
    }
}
