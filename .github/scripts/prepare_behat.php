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

// Test infrastructure only. No runtime component is modified or uninstalled.
define('CLI_SCRIPT', true);
define('BEHAT_UTIL', true);
define('NO_OUTPUT_BUFFERING', true);
define('IGNORE_COMPONENT_CACHE', true);
define('ABORT_AFTER_CONFIG', true);
require(
    PHP_SAPI === 'cli' && !empty($argv[1]) && is_file($argv[1] . '/config.php') ?
        realpath($argv[1]) . '/config.php' :
        throw new InvalidArgumentException('Usage: php prepare_behat.php /absolute/path/to/isolated/moodle')
);
$CFG->debug = E_ALL;
$CFG->debugdisplay = 1;
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('ABORT_AFTER_CONFIG_CANCEL', true);
require($CFG->dirroot . '/lib/setup.php');
require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->libdir . '/behat/classes/util.php');
if (!defined('BEHAT_SITE_RUNNING') || $DB->get_prefix() !== $CFG->behat_prefix) {
    throw new coding_exception('This command requires the separately configured Behat test site.');
}
if ($status = behat_util::get_behat_status()) {
    throw new coding_exception('Native Behat readiness guard failed: ' . $status);
}
$utility = new behat_config_util();
$contexts = $utility->get_components_contexts();
$incompatible = [
    'auth/iomadsaml2/tests/behat/behat_auth_saml2.php' =>
        '95b8b334909e1f7289e8cc4866309c684f76d95411c9a9ef323db38bc063a876',
    'admin/tool/iomadpolicy/tests/behat/behat_tool_iomadpolicy.php' =>
        '0ea3c4a8c243462a0589d3712541c38bcc7c2416bb22e6d134d9d67dbee6c955',
];
$excluded = [];
foreach ($contexts as $name => $path) {
    $relative = substr($path, strlen($CFG->dirroot) + 1);
    if (!isset($incompatible[$relative])) {
        continue;
    }
    if (hash_file('sha256', $path) !== $incompatible[$relative]) {
        throw new coding_exception('Changed third-party acceptance context; review its compatibility before exclusion.');
    }
    $excluded[] = ['context' => $name, 'path' => $relative, 'sha256' => $incompatible[$relative]];
    unset($contexts[$name]);
}
if (!isset($contexts['behat_local_iomadcertregen'])) {
    throw new coding_exception('The plugin acceptance context is missing.');
}
$config = behat_command::get_behat_dir() . '/behat.yml';
$yaml = $utility->get_config_file_contents($utility->get_components_features(), $contexts);
if (file_put_contents($config, $yaml) === false) {
    throw new coding_exception('Cannot save the acceptance configuration.');
}
// Native enable marker, after the same readiness guard; configuration generation is scoped above.
$enabled = file_put_contents(
    behat_util::get_test_file_path(),
    '$CFG->behat_wwwroot, $CFG->behat_prefix and $CFG->behat_dataroot' .
        ' are currently used as $CFG->wwwroot, $CFG->prefix and $CFG->dataroot'
);
if ($enabled === false) {
    throw new coding_exception('Cannot enable the isolated acceptance site.');
}
echo json_encode([
    'config' => $config, 'excluded_test_contexts' => $excluded,
    'runtime_source_modified' => false, 'plugin_scenarios_excluded' => false,
    'scope' => 'Run only @local_iomadcertregen; no qualification of the excluded IOMAD SAML/policy acceptance contexts.',
], JSON_PRETTY_PRINT) . PHP_EOL;
