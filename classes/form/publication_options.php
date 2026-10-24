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

/**
 * Explicit archive choice and a bounded administrative correction reason.
 */
final class publication_options {
    /**
     * Add the same publication controls to individual and collective forms.
     *
     * @param \MoodleQuickForm $form Native form.
     */
    public static function add(\MoodleQuickForm $form): void {
        $component = 'local_iomadcertregen';
        $forced = \local_iomadcertregen\local\archive_policy::forced();
        $choices = [1 => get_string('archiveyes', $component), 0 => get_string('archiveno', $component)];
        if ($forced !== null) {
            $choices = [(int)$forced => $choices[(int)$forced]];
            $form->addElement('static', 'archivepolicy', '', get_string('archivepolicyforced', $component));
        }
        $form->addElement('select', 'keeporiginal', get_string('keeporiginal', $component), $choices);
        $form->setType('keeporiginal', PARAM_INT);
        $form->setDefault('keeporiginal', (int)($forced ?? true));
        $form->addElement('text', 'correctionreason', get_string('correctionreason', $component), ['size' => 70]);
        $form->setType('correctionreason', PARAM_TEXT);
        $form->setDefault('correctionreason', get_string('reasondefault', $component));
        $form->addRule('correctionreason', get_string('required'), 'required', null, 'server');
        $form->addRule('correctionreason', get_string('invalidreason', $component), 'maxlength', 255, 'server');
        $form->addElement('static', 'archivenotice', '', get_string('archivehelp', $component));
    }

    /**
     * Require an explicit valid archive choice even when the form is submitted without JavaScript.
     *
     * @param array $data Submitted publication fields.
     * @return array Field errors.
     */
    public static function validation(array $data): array {
        if (!array_key_exists('keeporiginal', $data) || !in_array((string)$data['keeporiginal'], ['0', '1'], true)) {
            return ['keeporiginal' => get_string('invalidarchivepolicy', 'local_iomadcertregen')];
        }
        $forced = \local_iomadcertregen\local\archive_policy::forced();
        if ($forced !== null && (bool)$data['keeporiginal'] !== $forced) {
            return ['keeporiginal' => get_string('archivepolicychanged', 'local_iomadcertregen')];
        }
        return [];
    }
}
