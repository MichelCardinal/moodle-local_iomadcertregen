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

use local_iomadcertregen\local\adapter;
use local_iomadcertregen\local\records;
use local_iomadcertregen\local\service;

$id = required_param('id', PARAM_INT);
$issueid = required_param('issueid', PARAM_INT);
$cm = get_coursemodule_from_id('iomadcertificate', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, false, $cm);
[$certificate, $user, $cm, $course, $issue, $context] = records::issue($id, $issueid);
$original = adapter::original_url($certificate, $issue, $context);
$url = new moodle_url('/local/iomadcertregen/prepare.php', ['id' => $id, 'issueid' => $issueid]);
$PAGE->set_url($url);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(get_string('prepare', 'local_iomadcertregen'));
$PAGE->set_heading(format_string($course->fullname));
$form = new \local_iomadcertregen\form\action($url, [
    'id' => $id, 'issueid' => $issueid, 'button' => 'prepare',
    'requestkey' => bin2hex(random_bytes(16)),
]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/iomadcertregen/index.php', ['id' => $id]));
}
if ($submitted = $form->get_data()) {
    $operation = service::prepare($id, $issueid, $submitted->requestkey, required_param('sesskey', PARAM_RAW));
    redirect(new moodle_url('/local/iomadcertregen/confirm.php', ['id' => $id, 'operationid' => $operation->id]));
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('prepare', 'local_iomadcertregen'));
echo html_writer::tag('p', s(fullname($user)) . ' — ' . userdate($issue->timecreated) . ' — ' . s($issue->code));
echo html_writer::tag('p', get_string('currenttemplate', 'local_iomadcertregen', s($certificate->iomadcertificatetype)));
if (adapter::original_download_available()) {
    echo html_writer::tag('p', html_writer::link(
        new moodle_url($original),
        get_string('original', 'local_iomadcertregen'),
        ['target' => '_blank', 'rel' => 'noopener']
    ));
} else {
    echo $OUTPUT->notification(get_string('originalunavailable', 'local_iomadcertregen'), 'warning');
}
echo $OUTPUT->notification(get_string('scope', 'local_iomadcertregen'), 'info');
echo html_writer::tag('p', get_string('snapshotnotice', 'local_iomadcertregen'));
$form->display();
echo $OUTPUT->footer();
