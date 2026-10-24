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

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Actual native inventory CSV downloads and audience-independent access checks.
 */
class behat_local_iomadcertregen_inventory extends behat_base {
    /** @var array Native system and custom report IDs. */
    private array $reports = [];

    /**
     * Create an inventory with no issued certificates or company associations.
     *
     * @Given /^a fictional certificate inventory is available for CSV review$/
     */
    public function inventory_fixture(): void {
        global $USER;
        $actor = $USER;
        try {
            \core\session\manager::set_user(get_admin());
            $generator = behat_util::get_data_generator();
            $category = $generator->create_category(['name' => 'Fictional CSV category']);
            $course = $generator->create_course([
                'fullname' => 'Fictional course, "CSV"', 'shortname' => 'ICRCSV',
                'category' => $category->id, 'visible' => 0,
            ]);
            foreach (['A4_non_embedded', 'letter_non_embedded'] as $template) {
                $generator->create_module('iomadcertificate', [
                    'course' => $course->id, 'name' => 'Fictional ' . $template, 'iomadcertificatetype' => $template,
                ]);
            }
            $reports = $generator->get_plugin_generator('core_reportbuilder');
            $custom = $reports->create_report([
                'name' => 'Fictional CSV inventory',
                'source' => \local_iomadcertregen\reportbuilder\datasource\certificates::class, 'default' => 1,
            ]);
            $reports->create_audience([
                'reportid' => $custom->get('id'),
                'classname' => \core_reportbuilder\reportbuilder\audience\allusers::class, 'configdata' => [],
            ]);
            $system = \core_reportbuilder\system_report_factory::create(
                \local_iomadcertregen\reportbuilder\local\systemreports\certificates::class,
                \context_system::instance()
            );
            $this->reports = ['system' => $system->get_report_persistent()->get('id'), 'custom' => $custom->get('id')];
        } finally {
            \core\session\manager::set_user($actor);
        }
    }

    /**
     * Verify the native HTTP export, including quoted course names and both templates.
     *
     * @Then /^the "(system|custom)" certificate inventory downloads both fictional templates as CSV$/
     * @param string $source Native report type.
     */
    public function download_inventory(string $source): void {
        $this->request_inventory($source, true);
    }

    /**
     * An authenticated ordinary user cannot export even an all-users custom-report audience.
     *
     * @Then /^the "(system|custom)" certificate inventory CSV download is refused$/
     * @param string $source Native report type.
     */
    public function refuse_inventory(string $source): void {
        $this->request_inventory($source, false);
    }

    /**
     * Request the actual native endpoint, then restore an ordinary page for standard hooks.
     *
     * @param string $source Report type.
     * @param bool $allowed Expected authority.
     */
    private function request_inventory(string $source, bool $allowed): void {
        $session = $this->getSession();
        $previous = $session->getCurrentUrl();
        if (str_contains($previous, '/login/')) {
            throw new Exception('The CSV test requires a successfully authenticated session.');
        }
        $url = new moodle_url('/reportbuilder/download.php', [
            'id' => $this->reports[$source], 'download' => 'csv', 'parameters' => json_encode([]),
        ]);
        try {
            if ($allowed) {
                $this->open_inventory($source);
            }
            $session->visit($url->out(false));
            $body = $session->getDriver()->getContent();
            if (!$allowed) {
                $this->verify_refusal($source, $session);
                return;
            }
            $headers = array_change_key_case($session->getResponseHeaders(), CASE_LOWER);
            $type = implode(';', $headers['content-type'] ?? []);
            $disposition = implode(';', $headers['content-disposition'] ?? []);
            if (
                $session->getStatusCode() !== 200 || !str_starts_with($type, 'text/csv') ||
                    !str_contains($disposition, 'attachment')
            ) {
                throw new Exception('The native inventory endpoint did not return an attached CSV.');
            }
            $this->verify_csv($body);
        } finally {
            $session->visit($previous);
        }
    }

    /**
     * Follow the native user workflow before exporting, initialising table and navigation session state.
     *
     * @param string $source Report type.
     */
    private function open_inventory(string $source): void {
        $path = $source === 'system' ? '/local/iomadcertregen/inventory.php' : '/reportbuilder/view.php';
        $url = new moodle_url($path, $source === 'custom' ? ['id' => $this->reports[$source]] : []);
        $session = $this->getSession();
        $session->visit($url->out(false));
        if (
            $session->getStatusCode() !== 200 || str_contains($session->getCurrentUrl(), '/login/') ||
                str_contains($session->getDriver()->getContent(), 'debuggingmessage')
        ) {
            throw new Exception('The authorised inventory page did not load cleanly before its native export.');
        }
    }

    /**
     * Require an authenticated native permission error rather than a login redirect or leaked rows.
     *
     * @param string $source Report type.
     * @param \Behat\Mink\Session $session Actual HTTP session.
     */
    private function verify_refusal(string $source, \Behat\Mink\Session $session): void {
        $code = $source === 'system' ? 'errorreportview' : 'errorsourceunavailable';
        if (
            $session->getStatusCode() < 400 || str_contains($session->getDriver()->getContent(), 'Fictional course') ||
                !str_contains($session->getPage()->getText(), 'Error code: ' . $code)
        ) {
            throw new Exception('The authenticated user did not receive the expected native export refusal.');
        }
    }

    /**
     * Parse real CSV syntax rather than accepting matching text inside an HTML response.
     *
     * @param string $body Native download bytes.
     */
    private function verify_csv(string $body): void {
        $stream = fopen('php://temp', 'r+');
        try {
            fwrite($stream, $body);
            rewind($stream);
            $rows = [];
            while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                $rows[] = $row;
            }
            if (count($rows) !== 3) {
                throw new Exception('Expected one header and two CSV activities; received: ' . json_encode($rows));
            }
            foreach (['A4_non_embedded', 'letter_non_embedded'] as $offset => $template) {
                $row = $rows[$offset + 1];
                foreach (['Fictional CSV category', 'Fictional course, "CSV"', 'Fictional ' . $template, $template] as $value) {
                    if (!in_array($value, $row, true)) {
                        throw new Exception('The CSV changed a quoted course name or certificate template.');
                    }
                }
            }
        } finally {
            fclose($stream);
        }
    }
}
