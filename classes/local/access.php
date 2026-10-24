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
            (int)$cm->id === (int)$context->instanceid && count($args) === 2 &&
            ctype_digit((string)$args[0]) && (int)$args[0] > 0 &&
            self::valid_pdf_name($filearea, $args[1]);
    }

    /**
     * Validate a file area and its bounded PDF filename.
     *
     * @param string $area File area.
     * @param string $filename File name.
     * @return bool Whether this is a supported route.
     */
    private static function valid_pdf_name(string $area, string $filename): bool {
        return ($area === 'document' && $filename === 'corrected-certificate.pdf') ||
            ($area === 'original' && preg_match('/^native-[1-9][0-9]*\.pdf$/D', $filename));
    }

    /**
     * Require a valid administrator form intent without accepting arbitrary identifiers.
     *
     * @param string $sesskey Session protection.
     * @param string $requestkey Form nonce.
     * @param string $batchkey Optional batch provenance.
     */
    public static function require_intent(string $sesskey, string $requestkey, string $batchkey = ''): void {
        if (!confirm_sesskey($sesskey) || !preg_match('/^[a-f0-9]{32}$/D', $requestkey)) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }
        if ($batchkey !== '' && !preg_match('/^[a-f0-9]{64}$/D', $batchkey)) {
            throw new \moodle_exception('bulkinvalid', 'local_iomadcertregen');
        }
    }

    /**
     * Preserve native beneficiary and company-manager visibility for published documents.
     *
     * @param \context_module $context Activity context.
     * @param int $userid Beneficiary.
     */
    public static function require_reader(\context_module $context, int $userid): void {
        global $DB, $USER;
        if (!$DB->record_exists('user', ['id' => $userid, 'deleted' => 0, 'suspended' => 0])) {
            throw new \moodle_exception('publicationunavailable', 'local_iomadcertregen');
        }
        if (is_siteadmin()) {
            self::require_permission($context);
            return;
        }
        if ((int)$USER->id === $userid) {
            require_capability('mod/iomadcertificate:view', $context);
            if (isguestuser() || !is_enrolled($context, $USER, '', true)) {
                throw new \moodle_exception('publicationunavailable', 'local_iomadcertregen');
            }
            return;
        }
        tenant_access::require_activity_reader($context, $userid);
    }
}
