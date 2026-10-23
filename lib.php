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
    if ($context->contextlevel !== CONTEXT_MODULE || !is_siteadmin()) {
        return;
    }
    $cm = get_coursemodule_from_id('iomadcertificate', $context->instanceid);
    if (
        !$cm || !has_capability('local/iomadcertregen:regenerate', $context) ||
            !has_capability('mod/iomadcertificate:manage', $context)
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
    $file = \local_iomadcertregen\local\service::file($operation);
    if (
        !$file || $file->get_mimetype() !== 'application/pdf' ||
            $file->get_filesize() > \local_iomadcertregen\local\adapter::MAX_PDF_BYTES ||
            !hash_equals($operation->pdfhash, hash('sha256', $file->get_content()))
    ) {
        return false;
    }
    \local_iomadcertregen\event\copy_downloaded::create([
        'context' => $context, 'objectid' => $operation->id, 'relateduserid' => $operation->userid,
    ])->trigger();
    \core\session\manager::write_close();
    send_stored_file($file, 0, 0, $forcedownload, ['cacheability' => 'private'] + $options);
}
