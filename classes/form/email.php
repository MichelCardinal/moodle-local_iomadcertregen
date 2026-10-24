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

namespace local_iomadcertregen\form;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

/**
 * Explicit confirmation of the exact PDF and destination, separate from PDF confirmation.
 */
final class email extends \moodleform {
    /**
     * Define the recipient acknowledgement and protected request fields.
     */
    public function definition(): void {
        $form = $this->_form;
        foreach (
            ['id' => PARAM_INT, 'operationid' => PARAM_INT, 'requestkey' => PARAM_ALPHANUM,
                'recipienthash' => PARAM_ALPHANUM] as $name => $type
        ) {
            $form->addElement('hidden', $name, $this->_customdata[$name]);
            $form->setType($name, $type);
        }
        $form->addElement('advcheckbox', 'acknowledge', get_string('emailacknowledge', 'local_iomadcertregen'));
        $form->addRule('acknowledge', get_string('required'), 'required', null, 'server');
        $this->add_action_buttons(true, get_string('sendcorrectedemail', 'local_iomadcertregen'));
    }

    /**
     * Require acknowledgement even when JavaScript is unavailable.
     *
     * @param array $data Form fields.
     * @param array $files Uploaded files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (empty($data['acknowledge'])) {
            $errors['acknowledge'] = get_string('required');
        }
        return $errors;
    }
}
