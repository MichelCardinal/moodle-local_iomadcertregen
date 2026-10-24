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

use local_iomadcertregen\local\{publication, publication_files, records};

$id = required_param('id', PARAM_INT);
$operationid = required_param('operationid', PARAM_INT);
$cm = get_coursemodule_from_id('iomadcertificate', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, false, $cm);
$operation = records::operation($id, $operationid);
$data = records::issue($id, $operation->issueid);
$url = new moodle_url('/local/iomadcertregen/publish.php', ['id' => $id, 'operationid' => $operationid]);
$back = new moodle_url('/local/iomadcertregen/confirm.php', ['id' => $id, 'operationid' => $operationid]);
$PAGE->set_url($url);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(get_string('publish', 'local_iomadcertregen'));
$PAGE->set_heading(format_string($course->fullname));
$files = publication_files::targets($data);
$form = new \local_iomadcertregen\form\action($url, [
    'id' => $id, 'operationid' => $operationid, 'button' => 'publish',
    'requestkey' => bin2hex(random_bytes(16)), 'reviewhash' => publication_files::fingerprint($files),
]);
if ($form->is_cancelled()) {
    redirect($back);
}
if ($submitted = $form->get_data()) {
    publication::publish($id, $operationid, $submitted->reviewhash, required_param('sesskey', PARAM_RAW));
    redirect($back);
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('publish', 'local_iomadcertregen'));
echo html_writer::tag('p', s(fullname($data[1])) . ' — ' . s($data[4]->code) . ' — ' . userdate($data[4]->timecreated));
echo $OUTPUT->notification(get_string('publishnotice', 'local_iomadcertregen'), 'info');
echo html_writer::tag('p', get_string('publishtargets', 'local_iomadcertregen', count($files)));
foreach ($files as $file) {
    echo html_writer::tag('p', s($file->get_component() . ' / ' . $file->get_filename()));
}
if (!$operation->publishedtime) {
    $form->display();
} else {
    echo $OUTPUT->notification(get_string('published', 'local_iomadcertregen'), 'success');
}
echo $OUTPUT->continue_button($back);
echo $OUTPUT->footer();
