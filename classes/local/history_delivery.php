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
 * Protected delivery of native IOMAD history files through supported login callbacks.
 */
final class history_delivery {
    /**
     * Deliver only recognised native history routes after Moodle has authenticated the request.
     *
     * The entry points provide the normalised relative path, including token URL normalisation.
     * Do not exclude AJAX/token entry points: they need exactly the same ownership checks.
     */
    public static function after_login(): void {
        global $CFG, $SCRIPT, $relativepath;
        if (CLI_SCRIPT || !in_array($SCRIPT, ['/pluginfile.php', '/tokenpluginfile.php', '/webservice/pluginfile.php'], true)) {
            return;
        }
        if ((int)get_config('local_iomadcertregen', 'version') < 2026102400 || !is_string($relativepath)) {
            return;
        }
        require_once($CFG->libdir . '/filelib.php');
        $file = self::file($relativepath);
        if (!$file) {
            return;
        }
        \core\session\manager::write_close();
        send_stored_file($file, 0, 0, true, ['cacheability' => 'private']);
    }

    /**
     * Resolve a native history file with explicit beneficiary, track and company checks.
     *
     * @param string $relativepath Normalised path actually passed to file_pluginfile.
     * @return \stored_file|null Protected file, or null for an unrelated file area.
     */
    public static function file(string $relativepath): ?\stored_file {
        global $DB;
        $route = self::route($relativepath);
        if (!$route) {
            return null;
        }
        [$contextid, $component, $area, $trackid, $filename, $table] = $route;
        $context = \context::instance_by_id($contextid, IGNORE_MISSING);
        if (!$context || $context->contextlevel !== CONTEXT_USER || !$DB->get_manager()->table_exists($table)) {
            throw new \moodle_exception('unavailable', 'local_iomadcertregen');
        }
        $track = $DB->get_record($table, ['id' => $trackid, 'userid' => $context->instanceid]);
        if (!$track) {
            throw new \moodle_exception('unavailable', 'local_iomadcertregen');
        }
        tenant_access::require_history_reader((int)$context->instanceid, (int)$track->companyid);
        $file = get_file_storage()->get_file($contextid, $component, $area, $trackid, '/', $filename);
        if (!$file || $file->is_directory()) {
            throw new \moodle_exception('unavailable', 'local_iomadcertregen');
        }
        return $file;
    }

    /**
     * Recognise exact native file areas, refusing malformed targeted paths instead of falling through.
     *
     * @param string $relativepath Normalised file route.
     * @return array|null Native route and table, or null for unrelated components/areas.
     */
    private static function route(string $relativepath): ?array {
        $parts = explode('/', ltrim($relativepath, '/'));
        $component = clean_param($parts[1] ?? '', PARAM_COMPONENT);
        $area = clean_param($parts[2] ?? '', PARAM_AREA);
        $table = [
            'local_iomad_track/issue' => 'local_iomad_track',
            'local_iomad/certificate_issue' => 'local_iomad_tracks',
        ][$component . '/' . $area] ?? null;
        if (!$table) {
            return null;
        }
        if (count($parts) !== 5 || !ctype_digit($parts[0]) || !ctype_digit($parts[3]) || $parts[4] === '') {
            throw new \moodle_exception('unavailable', 'local_iomadcertregen');
        }
        return [(int)$parts[0], $component, $area, (int)$parts[3], $parts[4], $table];
    }
}
