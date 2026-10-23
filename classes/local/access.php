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

namespace local_iomadcertregen\local;

/**
 * Authorisation shared by pages, services and file delivery.
 */
final class access {
    /**
     * Require a logged-in site administrator for the activity selector.
     */
    public static function require_permission_for_site(): void {
        if (!is_siteadmin()) {
            throw new \required_capability_exception(
                \context_system::instance(),
                'moodle/site:config',
                'nopermissions',
                ''
            );
        }
    }

    /**
     * Require an authorised site administrator in this activity.
     *
     * @param \context_module $context Activity context.
     */
    public static function require_permission(\context_module $context): void {
        if (!is_siteadmin()) {
            throw new \required_capability_exception($context, 'local/iomadcertregen:regenerate', 'nopermissions', '');
        }
        require_capability('local/iomadcertregen:regenerate', $context);
        require_capability('mod/iomadcertificate:manage', $context);
    }

    /**
     * Validate the exact module-context file route before resolving an operation.
     *
     * @param \stdClass|false|null $cm Course module, if present.
     * @param \context $context Requested context.
     * @param string $filearea Requested file area.
     * @param array $args Operation identifier and filename.
     * @return bool Whether the route belongs to this plugin's PDF area.
     */
    public static function valid_file_request($cm, \context $context, string $filearea, array $args): bool {
        return $context->contextlevel === CONTEXT_MODULE && $cm &&
            (int)$cm->id === (int)$context->instanceid && $filearea === 'document' && count($args) === 2 &&
            ctype_digit((string)$args[0]) && (int)$args[0] > 0 && $args[1] === 'corrected-certificate.pdf';
    }
}
