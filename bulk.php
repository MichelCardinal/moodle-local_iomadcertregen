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

use local_iomadcertregen\local\{access, batch, publication_files, records};

$id = required_param('id', PARAM_INT);
$key = optional_param('batch', '', PARAM_ALPHANUM);
$mode = optional_param('mode', $key ? 'confirm' : 'prepare', PARAM_ALPHA);
$page = max(0, optional_param('page', 0, PARAM_INT));
if (!in_array($mode, ['prepare', 'confirm', 'publish'], true) || (!$key && $mode !== 'prepare')) {
    throw new moodle_exception('bulkinvalid', 'local_iomadcertregen');
}
$cm = get_coursemodule_from_id('iomadcertificate', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, false, $cm);
access::require_permission(context_module::instance($id));
$url = new moodle_url('/local/iomadcertregen/bulk.php', ['id' => $id, 'batch' => $key, 'mode' => $mode, 'page' => $page]);
$back = new moodle_url('/local/iomadcertregen/index.php', ['id' => $id]);
$PAGE->set_url($url);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(get_string('bulk', 'local_iomadcertregen'));
$PAGE->set_heading(format_string($course->fullname));
$custom = ['id' => $id, 'batch' => $key, 'mode' => $mode, 'requestkey' => bin2hex(random_bytes(16))];
$total = 0;
if ($mode === 'prepare') {
    [$issues, $total] = \local_iomadcertregen\local\issue_list::page($id, $page);
    $custom['choices'] = [];
    foreach ($issues as $issue) {
        $custom['choices'][$issue->id] = fullname($issue) . ' — ' . $issue->code . ' — ' . userdate($issue->timecreated);
    }
} else {
    $operations = batch::operations($id, $key);
    $custom['reviewhash'] = batch::fingerprint($operations, $mode === 'publish');
}
$form = new \local_iomadcertregen\form\batch($url, $custom);
if ($form->is_cancelled()) {
    redirect($back);
}
$failed = [];
if ($submitted = $form->get_data()) {
    if ($mode === 'prepare') {
        [$key, $failed] = batch::prepare($id, $submitted->issueids, $submitted->requestkey, required_param('sesskey', PARAM_RAW));
    } else {
        $failed = batch::apply(
            $id,
            $key,
            $submitted->reviewhash,
            $mode === 'publish',
            required_param('sesskey', PARAM_RAW),
            ['keep' => (bool)($submitted->keeporiginal ?? true), 'reason' => $submitted->correctionreason ?? '']
        );
    }
    if (!$failed) {
        redirect(new moodle_url('/local/iomadcertregen/bulk.php', ['id' => $id, 'batch' => $key]));
    }
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('bulk', 'local_iomadcertregen'));
echo $OUTPUT->notification(get_string('bulknotice', 'local_iomadcertregen'), 'info');
foreach ($failed as $issueid) {
    $issue = $DB->get_record('iomadcertificate_issues', ['id' => $issueid, 'iomadcertificateid' => $cm->instance]);
    $label = (string)$issueid;
    if ($issue) {
        $user = core_user::get_user($issue->userid);
        $label = $user ? fullname($user) . ' — ' . $issue->code : $issue->code;
    }
    $details = html_writer::link(new moodle_url('/local/iomadcertregen/prepare.php', [
        'id' => $id, 'issueid' => $issueid,
    ]), get_string('prepare', 'local_iomadcertregen'));
    echo $OUTPUT->notification(s($label) . ': ' . get_string('bulkerror', 'local_iomadcertregen') . ' ' . $details, 'warning');
}
if ($key && $DB->record_exists('local_iomadcertregen_op', ['batchkey' => $key, 'actorid' => $USER->id, 'cmid' => $id])) {
    $operations = batch::operations($id, $key);
    echo html_writer::tag('p', get_string('bulkresult', 'local_iomadcertregen'));
    $allconfirmed = true;
    $pending = array_filter($operations, static fn($operation) => !$operation->publishedtime);
    foreach ($operations as $operation) {
        $data = records::issue($id, $operation->issueid);
        echo html_writer::tag('p', s(fullname($data[1])) . ' — ' . s($data[4]->code) . ' — ' .
            html_writer::link(new moodle_url('/local/iomadcertregen/confirm.php', [
                'id' => $id, 'operationid' => $operation->id,
            ]), get_string($operation->publishedtime ? 'published' : $operation->status, 'local_iomadcertregen')));
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
            get_string('preview', 'local_iomadcertregen') . ' — ' . s(fullname($data[1])),
            ['target' => '_blank', 'rel' => 'noopener']
        ));
        $allconfirmed = $allconfirmed && $operation->status === 'confirmed';
        if ($mode === 'publish') {
            foreach (publication_files::targets($data) as $file) {
                echo html_writer::tag('p', s($file->get_component() . ' / ' . $file->get_filename()));
            }
        }
    }
    if (!$pending) {
        echo $OUTPUT->notification(get_string('published', 'local_iomadcertregen'), 'success');
    } else if ($submitted && $mode === 'prepare') {
        echo $OUTPUT->continue_button(new moodle_url('/local/iomadcertregen/bulk.php', ['id' => $id, 'batch' => $key]));
    } else {
        if ($mode === 'publish') {
            echo $OUTPUT->notification(get_string('publishnotice', 'local_iomadcertregen'), 'info');
        }
        $form->display();
    }
    if ($allconfirmed && $pending && $mode !== 'publish') {
        echo html_writer::tag('p', html_writer::link(new moodle_url('/local/iomadcertregen/bulk.php', [
            'id' => $id, 'batch' => $key, 'mode' => 'publish',
        ]), get_string('bulkpublish', 'local_iomadcertregen'), ['class' => 'btn btn-primary']));
    }
} else {
    $form->display();
    echo $OUTPUT->paging_bar($total, $page, batch::LIMIT, $url);
}
echo $OUTPUT->continue_button($back);
echo $OUTPUT->footer();
