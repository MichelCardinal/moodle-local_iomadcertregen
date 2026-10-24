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
require_once(__DIR__ . '/certificate_testcase.php');

/**
 * Confirmed certificate fixtures for mail transport and access tests.
 */
abstract class delivery_testcase extends certificate_testcase {
    /**
     * Establish a confirmed PDF and an eligible fictional recipient without contacting SMTP.
     *
     * @return array Fixture and confirmed operation.
     */
    protected function confirmed(): array {
        global $CFG, $DB;
        $fixture = $this->fixture();
        $CFG->noemailever = false;
        $CFG->noreplyaddress = 'noreply@example.com';
        $user = $fixture[1];
        $DB->update_record('user', (object)[
            'id' => $user->id, 'email' => 'learner@example.com', 'auth' => 'manual', 'confirmed' => 1, 'emailstop' => 0,
        ]);
        $fixture[1] = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
        $operation = service::prepare($fixture[2]->id, $fixture[4]->id, str_repeat('a7', 16), sesskey());
        return [$fixture, service::confirm($fixture[2]->id, $operation->id, sesskey())];
    }
}
