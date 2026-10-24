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
 * Resolve native files without guessing a historical activity or issue.
 */
final class publication_files {
    /**
     * Collect the native module PDF and strictly identified history PDFs.
     *
     * @param array $data Validated certificate records.
     * @return array Files to replace, keyed by native file ID.
     */
    public static function targets(array $data): array {
        self::require_compatible();
        [$certificate, , , , $issue, $context] = $data;
        adapter::original_url($certificate, $issue, $context);
        $files = get_file_storage()->get_area_files($context->id, 'mod_iomadcertificate', 'issue', $issue->id, 'id', false);
        $original = reset($files);
        $result = [$original->get_id() => $original];
        foreach (self::history($data) as $file) {
            $result[$file->get_id()] = $file;
        }
        foreach ($result as $file) {
            if (
                $file->is_external_file() || $file->get_mimetype() !== 'application/pdf' ||
                    $file->get_filesize() > adapter::MAX_PDF_BYTES
            ) {
                throw new \moodle_exception('publicationunavailable', 'local_iomadcertregen');
            }
            adapter::require_valid_pdf($file->get_content());
        }
        return $result;
    }

    /**
     * Require the exact native writer and history schema used by this integration.
     */
    public static function require_compatible(): void {
        global $CFG;
        $profiles = json_decode(file_get_contents(__DIR__ . '/publication_profiles.json'), true, 512, JSON_THROW_ON_ERROR);
        $profile = $profiles[(string)$CFG->branch] ?? [];
        if (!$profile) {
            throw new \moodle_exception('unsupported', 'local_iomadcertregen');
        }
        foreach ($profile as $path => $hash) {
            if (
                !is_readable($CFG->dirroot . '/' . $path) ||
                    !in_array(hash_file('sha256', $CFG->dirroot . '/' . $path), (array)$hash, true)
            ) {
                throw new \moodle_exception('unsupported', 'local_iomadcertregen');
            }
        }
    }

    /**
     * Match only the same beneficiary, course, exact issue date and unique native filename.
     *
     * @param array $data Validated certificate records.
     * @return array Unambiguously identified existing history PDFs.
     */
    private static function history(array $data): array {
        global $DB, $CFG;
        [$certificate, $user, , $course, $issue] = $data;
        $filename = clean_filename(format_string(rtrim($certificate->name, '.')) . '.pdf');
        self::require_unique_name($data, $filename);
        $legacy = (int)$CFG->branch < 501;
        $tracks = $DB->get_records($legacy ? 'local_iomad_track' : 'local_iomad_tracks', [
            'courseid' => $course->id, 'userid' => $user->id, 'timecompleted' => $issue->timecreated,
        ]);
        $result = [];
        $companies = records::company_ids((int)$user->id);
        $companies = count($companies) === 1 ? array_merge([0], $companies) : [0];
        foreach ($tracks as $track) {
            if (!in_array((int)$track->companyid, $companies, true)) {
                continue;
            }
            $names = array_column($DB->get_records('local_iomad_track_certs', ['trackid' => $track->id]), 'filename');
            if (!in_array($filename, $names, true)) {
                continue;
            }
            $file = get_file_storage()->get_file(
                \context_user::instance($user->id)->id,
                $legacy ? 'local_iomad_track' : 'local_iomad',
                $legacy ? 'issue' : 'certificate_issue',
                $track->id,
                '/',
                $filename
            );
            if (!$file) {
                throw new \moodle_exception('publicationunavailable', 'local_iomadcertregen');
            }
            $result[] = $file;
        }
        return $result;
    }

    /**
     * Hash the exact target files shown at the publication review.
     *
     * @param array $files Native files.
     * @return string Review fingerprint.
     */
    public static function fingerprint(array $files): string {
        $hashes = [];
        foreach ($files as $file) {
            $hashes[$file->get_id()] = [$file->get_pathnamehash(), hash('sha256', $file->get_content())];
        }
        ksort($hashes);
        return hash('sha256', json_encode($hashes, JSON_THROW_ON_ERROR));
    }
    /**
     * Refuse collisions because native history does not identify its source activity.
     *
     * @param array $data Native records.
     * @param string $filename Native filename.
     */
    private static function require_unique_name(array $data, string $filename): void {
        global $DB;
        $course = $data[3];
        // IOMAD history has no activity ID: equal filenames cannot safely identify a certificate.
        $names = [];
        foreach ($DB->get_records('iomadcertificate', ['course' => $course->id]) as $activity) {
            $names[] = clean_filename(format_string(rtrim($activity->name, '.')) . '.pdf');
        }
        if (count(array_keys($names, $filename, true)) !== 1) {
            throw new \moodle_exception('ambiguoushistory', 'local_iomadcertregen');
        }
        // Other certificate engines may write to the same filename; do not infer their identity.
        foreach (get_fast_modinfo($course)->get_cms() as $cm) {
            if (in_array($cm->modname, ['certificate', 'simplecertificate', 'customcert', 'coursecertificate'], true)) {
                throw new \moodle_exception('mixedhistory', 'local_iomadcertregen');
            }
        }
    }
}
