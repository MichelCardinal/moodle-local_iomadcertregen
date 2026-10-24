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
 * Explicit, CSRF-protected bounded selection and reviewed batch actions.
 */
final class batch extends \moodleform {
    /**
     * Build a selection or review form using Moodle's form API.
     */
    public function definition(): void {
        $form = $this->_form;
        $mode = $this->_customdata['mode'];
        foreach (
            ['id' => PARAM_INT, 'batch' => PARAM_ALPHANUM, 'mode' => PARAM_ALPHA,
                'requestkey' => PARAM_ALPHANUM, 'reviewhash' => PARAM_ALPHANUM] as $name => $type
        ) {
            $form->addElement('hidden', $name, $this->_customdata[$name] ?? '');
            $form->setType($name, $type);
        }
        if ($mode === 'prepare') {
            $select = $form->addElement(
                'select',
                'issueids',
                get_string('bulkselection', 'local_iomadcertregen'),
                $this->_customdata['choices'],
                ['size' => 12]
            );
            $select->setMultiple(true);
            $form->setType('issueids', PARAM_INT);
            $form->addRule('issueids', get_string('required'), 'required', null, 'server');
        }
        $form->addElement('advcheckbox', 'acknowledge', get_string('acknowledge', 'local_iomadcertregen'));
        $form->addRule('acknowledge', get_string('required'), 'required', null, 'server');
        $this->add_action_buttons(true, get_string('bulk' . $mode, 'local_iomadcertregen'));
    }

    /**
     * Require explicit acknowledgement and a bounded selection without JavaScript.
     *
     * @param array $data Submitted values.
     * @param array $files Uploaded files.
     * @return array Field errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (empty($data['acknowledge'])) {
            $errors['acknowledge'] = get_string('required');
        }
        if ($this->_customdata['mode'] === 'prepare') {
            $ids = $data['issueids'] ?? [];
            if (!$ids || count($ids) > \local_iomadcertregen\local\batch::LIMIT) {
                $errors['issueids'] = get_string('bulkinvalid', 'local_iomadcertregen');
            }
        }
        return $errors;
    }
}
