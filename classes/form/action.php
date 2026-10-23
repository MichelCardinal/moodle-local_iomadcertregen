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
 * CSRF-protected, explicit individual action with a persistent request identifier.
 */
final class action extends \moodleform {
    /**
     * Build the native Moodle form.
     */
    public function definition(): void {
        $form = $this->_form;
        foreach (['id', 'issueid', 'operationid'] as $name) {
            $form->addElement('hidden', $name, $this->_customdata[$name] ?? 0);
            $form->setType($name, PARAM_INT);
        }
        $form->addElement('hidden', 'requestkey', $this->_customdata['requestkey']);
        $form->setType('requestkey', PARAM_ALPHANUM);
        $form->addElement('advcheckbox', 'acknowledge', get_string('acknowledge', 'local_iomadcertregen'));
        $form->addRule('acknowledge', get_string('required'), 'required', null, 'server');
        $this->add_action_buttons(true, get_string($this->_customdata['button'], 'local_iomadcertregen'));
    }

    /**
     * Require explicit acknowledgement even when submitting without JavaScript.
     *
     * @param array $data Submitted fields.
     * @param array $files Files.
     * @return array Field errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (empty($data['acknowledge'])) {
            $errors['acknowledge'] = get_string('required');
        }
        return $errors;
    }
}
