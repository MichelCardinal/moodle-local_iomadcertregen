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

namespace local_iomadcertregen\event;

/**
 * A corrected-copy operation was confirmed.
 */
final class copy_confirmed extends \core\event\base {
    /**
     * Initialise the event metadata.
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_iomadcertregen_op';
    }

    /**
     * Return Translated event name.
     *
     * @return string Translated event name.
     */
    public static function get_name(): string {
        return get_string('eventconfirmed', 'local_iomadcertregen');
    }

    /**
     * Return Description without copied identity or PDF contents.
     *
     * @return string Description without copied identity or PDF contents.
     */
    public function get_description(): string {
        return 'The user with id ' . $this->userid . ' confirmed a corrected certificate copy for user ' .
            $this->relateduserid . ' in context ' . $this->contextid . '.';
    }

    /**
     * Return Activity tool.
     *
     * @return \moodle_url Activity tool.
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/local/iomadcertregen/index.php', ['id' => $this->contextinstanceid]);
    }
}
