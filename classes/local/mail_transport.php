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
 * Submit the exact confirmed PDF through Moodle's configured mail transport.
 */
class mail_transport {
    /**
     * Send one attachment using the recipient's language and the site sender.
     *
     * @param \stdClass $user Beneficiary from the current profile.
     * @param \stdClass $course Native course.
     * @param \stored_file $file Validated confirmed PDF.
     * @return bool Whether Moodle's mail transport accepted the message.
     */
    public function send(\stdClass $user, \stdClass $course, \stored_file $file): bool {
        $directory = make_request_directory();
        $attachment = $directory . '/corrected-certificate.pdf';
        try {
            if (!$file->copy_content_to($attachment)) {
                return false;
            }
            $title = html_to_text(format_string(
                $course->fullname,
                true,
                ['context' => \context_course::instance($course->id)]
            ), 0, false);
            $manager = get_string_manager();
            $subject = $manager->get_string('emailsubject', 'local_iomadcertregen', $title, $user->lang);
            $body = $manager->get_string('emailbody', 'local_iomadcertregen', $title, $user->lang);
            return email_to_user(
                $user,
                \core_user::get_noreply_user(),
                $subject,
                $body,
                '',
                $attachment,
                'corrected-certificate.pdf'
            );
        } finally {
            if (is_file($attachment)) {
                unlink($attachment);
            }
            rmdir($directory);
        }
    }
}
