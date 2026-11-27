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
 * Prepare native inventory table preferences before an export closes the session.
 *
 * @package    local_iomadcertregen
 * @copyright  2026 Michel Cardinal
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcertregen\local;

use core_reportbuilder\local\models\report;
use core_reportbuilder\local\report\base;
use core_reportbuilder\system_report_factory;
use core_reportbuilder\table\system_report_table;
use local_iomadcertregen\reportbuilder\local\systemreports\certificates;

/**
 * Initialise export preferences through the native table API before its writer closes the session.
 */
final class inventory_download {
    /**
     * Prepare only this plugin's administrator inventory on the normal native export route.
     */
    public static function after_login(): void {
        global $SCRIPT;
        if (
            $SCRIPT !== '/reportbuilder/download.php' || CLI_SCRIPT || AJAX_SCRIPT || WS_SERVER || !is_siteadmin()
        ) {
            return;
        }
        $reportid = required_param('id', PARAM_INT);
        $parameters = (array)json_decode(optional_param('parameters', '', PARAM_RAW));
        self::prepare($reportid, $parameters);
    }

    /**
     * Save default, selected or reset table preferences while the native session is still writable.
     *
     * No table output or report data query is performed. The native controller retains its
     * permission checks, filtering, sorting, download formats and response delivery.
     *
     * @param int $reportid Native report ID.
     * @param array $parameters Native report parameters, without activating a download writer.
     * @return bool Whether the matching authorised inventory was prepared.
     */
    public static function prepare(int $reportid, array $parameters): bool {
        if (!is_siteadmin()) {
            return false;
        }
        $context = \context_system::instance();
        $persistent = report::get_record([
            'id' => $reportid, 'type' => base::TYPE_SYSTEM_REPORT,
            'source' => certificates::class, 'contextid' => $context->id,
        ]);
        if (!$persistent) {
            return false;
        }
        unset($parameters['download']);
        system_report_factory::create(
            certificates::class,
            $context,
            $persistent->get('component'),
            $persistent->get('area'),
            $persistent->get('itemid'),
            $parameters
        );
        $table = system_report_table::create($reportid, $parameters);
        $table->guess_base_url();
        $table->setup();
        return true;
    }
}
