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
 * Resolve protected corrected and retained-original files after administrative access checks.
 */
final class documents {
    /**
     * Retrieve only a bounded PDF with its stored integrity fingerprint.
     *
     * @param \stdClass $operation Authorised operation.
     * @param string $filearea File area.
     * @param string $filename Validated filename.
     * @return \stored_file|false Valid file, or false.
     */
    public static function file(\stdClass $operation, string $filearea, string $filename) {
        if ($filearea === 'original' && !retention::archive_available($operation)) {
            return false;
        }
        $file = $filearea === 'original' ? get_file_storage()->get_file(
            $operation->contextid,
            'local_iomadcertregen',
            'original',
            $operation->id,
            '/',
            $filename
        ) : \local_iomadcertregen\local\service::file($operation);
        $expectedhash = self::fingerprint($operation, $filearea, $file);
        if (
            !$file || $file->get_mimetype() !== 'application/pdf' ||
                $file->get_filesize() > \local_iomadcertregen\local\adapter::MAX_PDF_BYTES ||
                !hash_equals($expectedhash, hash('sha256', $file->get_content()))
        ) {
            return false;
        }
        return $file;
    }
    /**
     * Treat malformed retained-file provenance as an unavailable document.
     *
     * @param \stdClass $operation Operation.
     * @param string $filearea Requested area.
     * @param \stored_file|false $file Stored file, if present.
     * @return string Expected SHA-256, or an empty value which cannot match.
     */
    private static function fingerprint(\stdClass $operation, string $filearea, $file): string {
        if ($filearea !== 'original') {
            return $operation->pdfhash;
        }
        if (!$file) {
            return '';
        }
        $source = json_decode((string)$file->get_source(), true);
        $hash = $source['sha256'] ?? '';
        return is_string($hash) ? $hash : '';
    }
}
