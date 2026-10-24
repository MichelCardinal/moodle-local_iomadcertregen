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

use core_reportbuilder\system_report_factory;
use local_iomadcertregen\local\access;
use local_iomadcertregen\reportbuilder\local\systemreports\certificates;

require_login();
access::require_permission_for_site();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/iomadcertregen/inventory.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('inventory', 'local_iomadcertregen'));
$PAGE->set_heading(get_string('inventory', 'local_iomadcertregen'));
$report = system_report_factory::create(certificates::class, $context);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('inventory', 'local_iomadcertregen'));
echo $OUTPUT->notification(get_string('inventoryscope', 'local_iomadcertregen'), 'info');
echo html_writer::tag('p', get_string('inventoryhelp', 'local_iomadcertregen'));
echo $report->output();
echo $OUTPUT->footer();
