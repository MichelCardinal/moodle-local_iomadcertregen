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

require_once(__DIR__ . '/../../config.php');

require_login();
$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', $USER->id, PARAM_INT);
[$operation, $file, $certificate] = \local_iomadcertregen\local\learner::document($id, $userid);
\local_iomadcertregen\event\copy_downloaded::create([
    'context' => context_module::instance($id), 'objectid' => $operation->id, 'relateduserid' => $userid,
])->trigger();
\core\session\manager::write_close();
send_stored_file($file, 0, 0, (int)$certificate->delivery === 1, ['cacheability' => 'private']);
