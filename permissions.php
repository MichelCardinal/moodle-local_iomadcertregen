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
require_once($CFG->libdir . '/adminlib.php');

use local_iomadcertregen\local\{access, role_permissions};

require_login();
access::require_permission_for_site();
admin_externalpage_setup('local_iomadcertregen_permissions');
$roles = role_permissions::roles();
$url = new moodle_url('/local/iomadcertregen/permissions.php');
$options = [];
$selected = [];
$prohibited = [];
foreach ($roles as $role) {
    $options[$role->id] = format_string($role->localname);
    $permission = role_permissions::permission($role->id);
    if ($permission === CAP_ALLOW) {
        $selected[] = $role->id;
    } else if ($permission === CAP_PROHIBIT) {
        $prohibited[] = $options[$role->id];
    }
}
$form = new \local_iomadcertregen\form\role_permission($url, [
    'revision' => role_permissions::selection_revision(), 'roles' => $options,
    'selected' => $selected, 'prohibited' => implode(', ', $prohibited),
]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/category.php', ['category' => 'local_iomadcertregen_category']));
}
if ($data = $form->get_data()) {
    role_permissions::save_selection($data->roleids ?? [], $data->revision, required_param('sesskey', PARAM_RAW));
    redirect($url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('accesspermissions', 'local_iomadcertregen'));
echo html_writer::tag('p', get_string('rolepickerintro', 'local_iomadcertregen'));
echo $OUTPUT->notification(get_string('roleaccessscope', 'local_iomadcertregen'), 'info');
echo html_writer::start_tag('details', ['class' => 'mb-4']);
echo html_writer::tag('summary', get_string('roleaccessdetails', 'local_iomadcertregen'));
foreach (['rolepermissionsscope', 'rolepermissionsiomad', 'rolepermissionsnative'] as $string) {
    echo html_writer::tag('p', get_string($string, 'local_iomadcertregen'));
}
echo html_writer::end_tag('details');
$form->display();
echo html_writer::tag('p', html_writer::link(new moodle_url('/admin/roles/manage.php'), get_string('defineroles', 'role')));
echo html_writer::tag('p', html_writer::link(
    new moodle_url('/admin/settings.php', ['section' => 'local_iomadcertregen_retention']),
    get_string('retentionsettings', 'local_iomadcertregen')
));
echo $OUTPUT->footer();
