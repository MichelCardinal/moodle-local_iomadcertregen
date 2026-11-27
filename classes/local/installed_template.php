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
 * Installed native presentation code and its review identity.
 *
 * @package    local_iomadcertregen
 * @copyright  2026 Michel Cardinal
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcertregen\local;

/**
 * Support site-maintained templates without treating a PHP digest as an approval list.
 *
 * Templates remain trusted server code, exactly as in the native activity. These
 * checks are not a PHP sandbox, and the plugin offers no template upload facility.
 */
final class installed_template {
    /**
     * Resolve a readable installed template without traversal, streams or external links.
     *
     * @param \stdClass $certificate Native activity.
     * @return string Canonical template filename.
     */
    public static function file(\stdClass $certificate): string {
        global $CFG;
        $type = $certificate->iomadcertificatetype ?? '';
        if (!is_string($type) || !preg_match('~^(?!\.{1,2}$)[^/\\\\:\x00-\x1f\x7f]+$~D', $type)) {
            throw new \moodle_exception('unsupportedtemplate', 'local_iomadcertregen');
        }
        $root = realpath($CFG->dirroot . '/mod/iomadcertificate/type');
        $expected = $root . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . 'certificate.php';
        $path = $root === false ? false : realpath($expected);
        if ($path === false || $path !== $expected || !is_file($path) || !is_readable($path)) {
            throw new \moodle_exception('unsupportedtemplate', 'local_iomadcertregen');
        }
        return $path;
    }

    /**
     * Bind the preview to the actual PHP file, without storing its source.
     *
     * @param \stdClass $certificate Native activity.
     * @return string SHA256 of the installed template.
     */
    public static function hash(\stdClass $certificate): string {
        $hash = hash_file('sha256', self::file($certificate));
        if ($hash === false) {
            throw new \moodle_exception('unsupportedtemplate', 'local_iomadcertregen');
        }
        return $hash;
    }

    /**
     * Keep presentation objects and accidental output outside the correction service's scope.
     *
     * @param array $records Activity, beneficiary, module, course and existing issue.
     * @param callable $renderer Native renderer for those records.
     * @return string Valid PDF bytes, without browser delivery.
     */
    public static function render(array $records, callable $renderer): string {
        global $USER;
        $actor = serialize($USER);
        $identity = self::identity($USER);
        $copies = array_map(static fn($record) => clone $record, $records);
        $level = ob_get_level();
        ob_start();
        try {
            $content = $renderer(...$copies);
            if (ob_get_level() !== $level + 1 || ob_get_contents() !== '' || self::identity($USER) !== $identity) {
                throw new \moodle_exception('generationfailed', 'local_iomadcertregen');
            }
            adapter::require_valid_pdf($content);
            return $content;
        } catch (\Throwable) {
            throw new \moodle_exception('generationfailed', 'local_iomadcertregen');
        } finally {
            if (serialize($USER) !== $actor) {
                // Presentation code must not change the correction's acting user.
                $USER = unserialize($actor);
            }
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
    }

    /**
     * Compare the acting identity without rejecting ordinary lazy-loaded preference/access caches.
     *
     * @param \stdClass $user Acting Moodle user.
     * @return array Identity fields in stable order.
     */
    private static function identity($user): array {
        $values = (array)$user;
        return array_map(
            static fn($field) => $values[$field] ?? null,
            ['id', 'username', 'firstname', 'lastname', 'email', 'auth', 'suspended', 'deleted']
        );
    }
}
