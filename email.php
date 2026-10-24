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

require_once(__DIR__ . '/../../config.php');

use local_iomadcertregen\local\delivery;
use local_iomadcertregen\local\records;

$id = required_param('id', PARAM_INT);
$operationid = required_param('operationid', PARAM_INT);
$cm = get_coursemodule_from_id('iomadcertificate', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, false, $cm);
$operation = records::operation($id, $operationid);
if ($operation->status !== 'confirmed' || (int)$operation->actorid !== (int)$USER->id) {
    throw new moodle_exception('unavailable', 'local_iomadcertregen');
}
$user = records::issue($id, $operation->issueid)[1];
$url = new moodle_url('/local/iomadcertregen/email.php', ['id' => $id, 'operationid' => $operationid]);
$return = new moodle_url('/local/iomadcertregen/confirm.php', ['id' => $id, 'operationid' => $operationid]);
$PAGE->set_url($url);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(get_string('sendcorrectedemail', 'local_iomadcertregen'));
$PAGE->set_heading(format_string($course->fullname));
$form = new \local_iomadcertregen\form\email($url, [
    'id' => $id, 'operationid' => $operationid, 'requestkey' => bin2hex(random_bytes(16)),
    'recipienthash' => delivery::recipient_hash($user),
]);
if ($form->is_cancelled()) {
    redirect($return);
}
if ($data = $form->get_data()) {
    delivery::send($id, $operationid, $data->requestkey, $data->recipienthash, required_param('sesskey', PARAM_RAW));
    redirect($return);
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('sendcorrectedemail', 'local_iomadcertregen'));
echo html_writer::tag('p', s(fullname($user)) . ' — ' . s($user->email));
echo $OUTPUT->notification(get_string('emailnotice', 'local_iomadcertregen'), 'info');
if (!empty($CFG->noemailever)) {
    echo $OUTPUT->notification(get_string('emaildisabled', 'local_iomadcertregen'), 'warning');
} else if (in_array($operation->mailstatus, ['notrequested', 'failed'], true)) {
    $form->display();
} else {
    echo $OUTPUT->notification(get_string('mailstatus_' . $operation->mailstatus, 'local_iomadcertregen'), 'info');
}
echo $OUTPUT->continue_button($return);
echo $OUTPUT->footer();
