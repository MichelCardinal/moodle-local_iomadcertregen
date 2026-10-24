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

/**
 * Add the activity tool without patching the module or theme.
 *
 * @param settings_navigation $navigation Settings navigation.
 * @param context $context Page context.
 */
function local_iomadcertregen_extend_settings_navigation(settings_navigation $navigation, context $context): void {
    if ($context->contextlevel !== CONTEXT_MODULE) {
        return;
    }
    $cm = get_coursemodule_from_id('iomadcertificate', $context->instanceid);
    if (
        !$cm || !\local_iomadcertregen\local\access::allowed($context)
    ) {
        return;
    }
    $parent = $navigation->find('modulesettings', navigation_node::TYPE_SETTING);
    if ($parent) {
        $parent->add(
            get_string('pluginname', 'local_iomadcertregen'),
            new moodle_url('/local/iomadcertregen/index.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'local_iomadcertregen'
        );
    }
}

/**
 * Serve only a PDF owned by this plugin after full context and ownership checks.
 *
 * @param stdClass $course Course.
 * @param stdClass $cm Module.
 * @param context $context File context.
 * @param string $filearea Area.
 * @param array $args File arguments.
 * @param bool $forcedownload Download request.
 * @param array $options File options.
 * @return bool False if the file is unavailable.
 */
function local_iomadcertregen_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if (!\local_iomadcertregen\local\access::valid_file_request($cm, $context, $filearea, $args)) {
        return false;
    }
    require_login($course, false, $cm);
    try {
        $operation = \local_iomadcertregen\local\records::operation($cm->id, (int)$args[0]);
    } catch (moodle_exception $error) {
        return false;
    }
    if ((int)$operation->contextid !== (int)$context->id) {
        return false;
    }
    $file = \local_iomadcertregen\local\documents::file($operation, $filearea, $args[1]);
    if (!$file) {
        return false;
    }
    \local_iomadcertregen\event\copy_downloaded::create([
        'context' => $context, 'objectid' => $operation->id, 'relateduserid' => $operation->userid,
    ])->trigger();
    \core\session\manager::write_close();
    send_stored_file($file, 0, 0, $filearea === 'original' || $forcedownload, ['cacheability' => 'private'] + $options);
}

/**
 * Preserve published bytes when the native activity's download button is used.
 *
 * This supported login callback runs before the native renderer and any native PDF write.
 * It protects native history file entry points and redirects the qualified activity download route.
 */
function local_iomadcertregen_after_require_login(): void {
    \local_iomadcertregen\local\history_delivery::after_login();
    global $CFG, $SCRIPT;
    if (
        $SCRIPT !== '/mod/iomadcertificate/view.php' || CLI_SCRIPT || AJAX_SCRIPT || WS_SERVER
    ) {
        return;
    }
    if (!\local_iomadcertregen\local\learner::native_download_enabled()) {
        return;
    }
    $cmid = required_param('id', PARAM_INT);
    $userid = \local_iomadcertregen\local\learner::native_userid();
    if (!\local_iomadcertregen\local\learner::native_operation($cmid, $userid)) {
        return;
    }
    if (
        hash_file('sha256', $CFG->dirroot . '/mod/iomadcertificate/view.php') !==
            '6f62861670f6f72704595b8f6268e6e85fe1e4b57c21c83ccb320e46a468b3c8'
    ) {
        throw new moodle_exception('unsupported', 'local_iomadcertregen');
    }
    redirect(new moodle_url('/local/iomadcertregen/learner.php', ['id' => $cmid, 'userid' => $userid]));
}
