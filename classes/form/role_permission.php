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
 * Native searchable multiple-role selection.
 */
final class role_permission extends \moodleform {
    /**
     * Define the native role picker and acknowledgement of its scope.
     */
    protected function definition(): void {
        $form = $this->_form;
        $data = $this->_customdata;
        $form->addElement('hidden', 'revision', $data['revision']);
        $form->setType('revision', PARAM_ALPHANUM);
        $form->addElement('autocomplete', 'roleids', get_string('rolepickerlabel', 'local_iomadcertregen'), $data['roles'], [
            'multiple' => true,
            'noselectionstring' => get_string('rolepickernone', 'local_iomadcertregen'),
        ]);
        $form->setType('roleids', PARAM_INT);
        $form->setDefault('roleids', $data['selected']);
        $form->addHelpButton('roleids', 'rolepickerlabel', 'local_iomadcertregen');
        if ($data['prohibited']) {
            $form->addElement('static', 'prohibitedroles', '', get_string(
                'rolepickerprohibited',
                'local_iomadcertregen',
                $data['prohibited']
            ));
        }
        $form->addElement('advcheckbox', 'acknowledge', get_string('rolepickerack', 'local_iomadcertregen'));
        $form->addRule('acknowledge', get_string('required'), 'required', null, 'server');
        $this->add_action_buttons(true, get_string('rolepickersave', 'local_iomadcertregen'));
    }
}
