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

use local_iomadcertregen\local\access;
use local_iomadcertregen\local\adapter;

$id = optional_param('id', 0, PARAM_INT);
$page = max(0, optional_param('page', 0, PARAM_INT));
$search = optional_param('search', '', PARAM_TEXT);
$url = new moodle_url('/local/iomadcertregen/index.php', ['id' => $id]);
if (!$id) {
    require_login();
    access::require_permission_for_site();
    redirect(new moodle_url('/local/iomadcertregen/inventory.php'));
}
$cm = get_coursemodule_from_id('iomadcertificate', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, false, $cm);
$context = context_module::instance($id);
access::require_permission($context);
$certificate = $DB->get_record('iomadcertificate', ['id' => $cm->instance], '*', MUST_EXIST);
$PAGE->set_url($url);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(get_string('pluginname', 'local_iomadcertregen'));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_iomadcertregen'));
if (is_siteadmin()) {
    echo html_writer::tag('p', html_writer::link(
        new moodle_url('/local/iomadcertregen/inventory.php'),
        get_string('inventory', 'local_iomadcertregen')
    ));
}
echo $OUTPUT->notification(get_string('scope', 'local_iomadcertregen'), 'info');
echo html_writer::tag('p', html_writer::link(
    new moodle_url('/local/iomadcertregen/bulk.php', ['id' => $id]),
    get_string('bulk', 'local_iomadcertregen'),
    ['class' => 'btn btn-secondary']
));
$version = core_plugin_manager::instance()->get_plugin_info('mod_iomadcertificate')->versiondisk;
echo html_writer::tag('p', get_string('moduleversion', 'local_iomadcertregen', s($version)));
try {
    adapter::require_compatible($certificate);
    echo html_writer::tag('p', get_string('qualified', 'local_iomadcertregen'));
} catch (moodle_exception $error) {
    echo $OUTPUT->notification($error->getMessage(), 'warning');
    echo $OUTPUT->footer();
    exit;
}
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $url->out(false), 'class' => 'mb-3']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::tag('label', get_string('searchlearner', 'local_iomadcertregen'), ['for' => 'icr-search']);
echo html_writer::empty_tag('input', [
    'id' => 'icr-search', 'name' => 'search', 'value' => $search, 'class' => 'form-control',
]);
echo html_writer::tag('button', get_string('search'), ['type' => 'submit', 'class' => 'btn btn-secondary mt-2']);
echo html_writer::end_tag('form');
[$rows, $total] = \local_iomadcertregen\local\issue_list::page($id, $page, $search);
$table = new html_table();
$table->head = [get_string('fullname'), get_string('issuedate', 'local_iomadcertregen'),
    get_string('status'), get_string('actions')];
$table->attributes['class'] = 'generaltable';
foreach ($rows as $row) {
    $state = 'eligible';
    $action = '';
    try {
        $data = \local_iomadcertregen\local\records::issue($id, $row->id);
        adapter::original_url($data[0], $data[4], $context);
        $action = html_writer::link(
            new moodle_url(
                '/local/iomadcertregen/prepare.php',
                ['id' => $id, 'issueid' => $row->id]
            ),
            get_string('prepare', 'local_iomadcertregen'),
            ['class' => 'btn btn-secondary']
        );
    } catch (moodle_exception $error) {
        $state = 'ineligible';
    }
    $copies = $DB->get_records_select(
        'local_iomadcertregen_op',
        'cmid = :cmid AND issueid = :issueid AND status = :status AND (timeexpires = 0 OR timeexpires > :now)',
        ['cmid' => $id, 'issueid' => $row->id, 'status' => 'confirmed', 'now' => time()],
        'timecreated DESC, id DESC',
        '*',
        0,
        1
    );
    if ($copies) {
        $copy = reset($copies);
        $action .= ' ' . html_writer::link(new moodle_url(
            '/local/iomadcertregen/confirm.php',
            ['id' => $id, 'operationid' => $copy->id]
        ), get_string('lastcopy', 'local_iomadcertregen'));
    }
    $table->data[] = [s(fullname($row)), userdate($row->timecreated),
        get_string($state, 'local_iomadcertregen'), $action];
}
if (!$rows) {
    echo $OUTPUT->notification(get_string('noissues', 'local_iomadcertregen'), 'info');
} else {
    echo html_writer::div(html_writer::table($table), 'table-responsive');
}
$pagingurl = new moodle_url($url, ['search' => $search]);
echo $OUTPUT->paging_bar($total, $page, 30, $pagingurl);
echo $OUTPUT->footer();
