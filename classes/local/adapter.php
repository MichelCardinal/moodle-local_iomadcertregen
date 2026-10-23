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
 * Fail-closed adapter for the qualified historical IOMAD interfaces.
 */
final class adapter {
    /** @var int Largest corrected PDF accepted for storage or delivery. */
    public const MAX_PDF_BYTES = 20 * 1024 * 1024;

    /**
     * Check the qualified branch, libraries and selected native template.
     *
     * @param \stdClass $certificate Native activity.
     */
    public static function require_compatible(\stdClass $certificate): void {
        global $CFG;
        $profile = json_decode(file_get_contents(__DIR__ . '/qualified.json'), true, 512, JSON_THROW_ON_ERROR);
        if (!in_array((int)$CFG->branch, $profile['branches'], true) || !empty($certificate->printoutcome)) {
            throw new \moodle_exception('unsupported', 'local_iomadcertregen');
        }
        $type = $certificate->iomadcertificatetype;
        $template = 'mod/iomadcertificate/type/' . $type . '/certificate.php';
        if (!isset($profile['templates'][$template])) {
            throw new \moodle_exception('unsupportedtemplate', 'local_iomadcertregen');
        }
        foreach ($profile['libraries'] + [$template => $profile['templates'][$template]] as $path => $hash) {
            $full = $CFG->dirroot . '/' . $path;
            if (!is_readable($full) || !in_array(hash_file('sha256', $full), (array)$hash, true)) {
                throw new \moodle_exception('unsupportedtemplate', 'local_iomadcertregen');
            }
        }
        require_once($CFG->dirroot . '/local/iomad_track/db/install.php');
    }

    /**
     * Indicate whether the qualified native saved-file endpoint can be offered.
     *
     * The original link is enabled only for the separately qualified corrected native handler.
     *
     * @return bool Whether the original's native download endpoint is usable.
     */
    public static function original_download_available(): bool {
        global $CFG;
        $profile = json_decode(file_get_contents(__DIR__ . '/qualified.json'), true, 512, JSON_THROW_ON_ERROR);
        $library = $CFG->dirroot . '/mod/iomadcertificate/lib.php';
        if (!is_readable($library)) {
            return false;
        }
        return ($profile['native_saved_file_download'][hash_file('sha256', $library)] ?? false) === true;
    }

    /**
     * Obtain evidence through the owner's reporting function, never its issuer.
     *
     * Accept only the qualified function's PDF link, with exact component, context,
     * issue and local URL. Unknown output is refused. No HTML is rendered directly.
     *
     * @param \stdClass $certificate Activity.
     * @param \stdClass $issue Existing issue.
     * @param \context_module $context Module context.
     * @return string Confirmed native stored-PDF URL.
     */
    public static function original_url(\stdClass $certificate, \stdClass $issue, \context_module $context): string {
        self::require_compatible($certificate);
        $html = iomadcertificate_print_user_files($certificate, $issue->userid, $context->id);
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $images = $document->getElementsByTagName('img');
        if ($images->length !== 1 || $images->item(0)->getAttribute('alt') !== 'application/pdf') {
            throw new \moodle_exception('nooriginal', 'local_iomadcertregen');
        }
        $links = $document->getElementsByTagName('a');
        if ($links->length !== 1) {
            throw new \moodle_exception('nooriginal', 'local_iomadcertregen');
        }
        $url = $links->item(0)->getAttribute('href');
        self::validate_original_url($url, $context->id, $issue->id);
        return $url;
    }

    /**
     * Reject links outside the exact native issue's local PDF route.
     *
     * @param string $url URL returned by the qualified owner's reporting function.
     * @param int $contextid Activity context.
     * @param int $issueid Native issue identifier.
     */
    private static function validate_original_url(string $url, int $contextid, int $issueid): void {
        global $CFG;
        $base = $CFG->wwwroot . '/pluginfile.php';
        if (substr($url, 0, strlen($base)) !== $base) {
            throw new \moodle_exception('nooriginal', 'local_iomadcertregen');
        }
        $relative = substr($url, strlen($base));
        if (substr($relative, 0, 6) === '?file=') {
            $relative = rawurldecode(substr($relative, 6));
        } else {
            $relative = rawurldecode($relative);
        }
        $prefix = '/' . $contextid . '/mod_iomadcertificate/issue/' . $issueid . '/';
        $filename = substr($relative, strlen($prefix));
        if (
            substr($relative, 0, strlen($prefix)) !== $prefix || strpos($filename, '/') !== false ||
                !preg_match('/^[^?#]+\.pdf$/i', $filename) || clean_param($filename, PARAM_FILE) !== $filename
        ) {
            throw new \moodle_exception('nooriginal', 'local_iomadcertregen');
        }
    }

    /**
     * Render using an explicit beneficiary and the unmodified existing issue.
     *
     * @param \stdClass $certificate Activity.
     * @param \stdClass $user Beneficiary.
     * @param \stdClass $cm Course module.
     * @param \stdClass $course Course.
     * @param \stdClass $issue Existing issue.
     * @return string PDF contents.
     */
    public static function render($certificate, $user, $cm, $course, $issue): string {
        self::require_compatible($certificate);
        $content = xmldb_local_iomad_track_create_certificate($certificate, $user, $cm, $course, $issue);
        self::require_valid_pdf($content);
        return $content;
    }

    /**
     * Check the bounded PDF format for native generation and restored copies.
     *
     * @param mixed $content Candidate PDF bytes.
     */
    public static function require_valid_pdf($content): void {
        if (
            !is_string($content) || strlen($content) < 100 || strlen($content) > self::MAX_PDF_BYTES ||
                substr($content, 0, 5) !== '%PDF-' || strpos(substr($content, -1024), '%%EOF') === false
        ) {
            throw new \moodle_exception('generationfailed', 'local_iomadcertregen');
        }
    }
}
