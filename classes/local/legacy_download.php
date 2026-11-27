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
 * Published downloads on the historical activity route.
 *
 * @package    local_iomadcertregen
 * @copyright  2026 Michel Cardinal
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcertregen\local;

/**
 * Divert qualified routes before native company checks or rendering.
 */
final class legacy_download {
    /** @var string Exact historical activity handler covered by this bridge. */
    public const VIEW_SHA256 = 'beabab61999b738da75954db5df00524b04cbbb7bc8f1668eb510bedc46a0170';

    /** @var string Qualified newer handler, whose company check also precedes login callbacks. */
    public const CURRENT_VIEW_SHA256 = '6f62861670f6f72704595b8f6268e6e85fe1e4b57c21c83ccb320e46a468b3c8';

    /**
     * Redirect through the normal protected learner endpoint, before native rendering.
     */
    public static function after_config(): void {
        if (CLI_SCRIPT || AJAX_SCRIPT || WS_SERVER) {
            return;
        }
        if ($destination = self::destination()) {
            redirect($destination);
        }
    }

    /**
     * Resolve only an existing publication; this method never serves a PDF or issues a certificate.
     *
     * The destination independently applies require_login, course/module restrictions,
     * ownership, company boundaries, retention and PDF integrity checks. Unrelated routes
     * and native issues without a publication retain their existing activity behaviour.
     *
     * @return \moodle_url|null Protected published-document route, or no diversion.
     */
    public static function destination(): ?\moodle_url {
        global $CFG, $SCRIPT;
        if (
            $SCRIPT !== '/mod/iomadcertificate/view.php' ||
                !in_array((int)($CFG->branch ?? 0), [405, 500, 501, 502], true) ||
                !isloggedin() || isguestuser() || !learner::native_download_enabled()
        ) {
            return null;
        }
        $handlers = [self::VIEW_SHA256, self::CURRENT_VIEW_SHA256];
        if (!in_array(hash_file('sha256', $CFG->dirroot . '/mod/iomadcertificate/view.php'), $handlers, true)) {
            return null;
        }
        $cmid = required_param('id', PARAM_INT);
        $userid = learner::native_userid();
        if (!learner::native_operation($cmid, $userid)) {
            return null;
        }
        publication_files::require_compatible();
        return new \moodle_url('/local/iomadcertregen/learner.php', ['id' => $cmid, 'userid' => $userid]);
    }
}
