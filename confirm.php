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

use local_iomadcertregen\local\records;
use local_iomadcertregen\local\service;

$id = required_param('id', PARAM_INT);
$operationid = required_param('operationid', PARAM_INT);
$cm = get_coursemodule_from_id('iomadcertificate', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, false, $cm);
$operation = records::operation($id, $operationid);
$data = records::issue($id, $operation->issueid);
$url = new moodle_url('/local/iomadcertregen/confirm.php', ['id' => $id, 'operationid' => $operationid]);
$PAGE->set_url($url);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(get_string('confirm', 'local_iomadcertregen'));
$PAGE->set_heading(format_string($course->fullname));
$form = new \local_iomadcertregen\form\action($url, [
    'id' => $id, 'operationid' => $operationid, 'button' => 'confirm',
    'requestkey' => bin2hex(random_bytes(16)),
]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/iomadcertregen/index.php', ['id' => $id]));
}
if ($submitted = $form->get_data()) {
    service::confirm($id, $operationid, required_param('sesskey', PARAM_RAW));
    redirect($url);
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string($operation->status === 'confirmed' ? 'confirmed' : 'confirm', 'local_iomadcertregen'));
echo html_writer::tag('p', s(fullname($data[1])) . ' — ' . userdate($data[4]->timecreated) . ' — ' . s($data[4]->code));
echo $OUTPUT->notification(get_string('scope', 'local_iomadcertregen'), 'info');
echo html_writer::tag('p', get_string('preparedat', 'local_iomadcertregen', userdate($operation->timecreated)));
if ($operation->timeexpires > 0) {
    echo html_writer::tag('p', get_string('expiresat', 'local_iomadcertregen', userdate($operation->timeexpires)));
} else {
    echo html_writer::tag('p', get_string('noexpiry', 'local_iomadcertregen'));
}
$preview = moodle_url::make_pluginfile_url(
    $operation->contextid,
    'local_iomadcertregen',
    'document',
    $operation->id,
    '/',
    'corrected-certificate.pdf'
);
echo html_writer::tag('p', html_writer::link(
    $preview,
    get_string('preview', 'local_iomadcertregen'),
    ['target' => '_blank', 'rel' => 'noopener', 'class' => 'btn btn-secondary']
));
if ($operation->status === 'confirmed') {
    $download = moodle_url::make_pluginfile_url(
        $operation->contextid,
        'local_iomadcertregen',
        'document',
        $operation->id,
        '/',
        'corrected-certificate.pdf',
        true
    );
    echo html_writer::tag('p', html_writer::link(
        $download,
        get_string('download', 'local_iomadcertregen'),
        ['class' => 'btn btn-primary']
    ));
    echo html_writer::tag('p', get_string('mailstatus_' . $operation->mailstatus, 'local_iomadcertregen'));
    if ($operation->mailtime) {
        echo html_writer::tag('p', userdate($operation->mailtime));
    }
    if ((int)$data[0]->delivery === 2) {
        echo $OUTPUT->notification(get_string('emailconfigured', 'local_iomadcertregen'), 'info');
    }
    if (
        (int)$operation->actorid === (int)$USER->id &&
            in_array($operation->mailstatus, ['notrequested', 'failed'], true)
    ) {
        echo html_writer::tag('p', html_writer::link(
            new moodle_url(
                '/local/iomadcertregen/email.php',
                ['id' => $id, 'operationid' => $operationid]
            ),
            get_string('sendcorrectedemail', 'local_iomadcertregen'),
            ['class' => 'btn btn-secondary']
        ));
    }
} else {
    echo html_writer::tag('p', get_string('confirmnotice', 'local_iomadcertregen'));
    $form->display();
}
echo $OUTPUT->continue_button(new moodle_url('/local/iomadcertregen/index.php', ['id' => $id]));
echo $OUTPUT->footer();
