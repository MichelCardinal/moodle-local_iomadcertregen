<?php
// This file is part of Moodle - http://moodle.org/
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
 * Configure fresh GitHub runner databases using the pinned CI configuration builder.
 *
 * The native PHPUnit/Behat CLI tools install their own schemas. No IOMAD source
 * or third-party library declaration is changed. This script is excluded from ZIPs.
 *
 * @copyright 2026 Michel Cardinal
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (PHP_SAPI !== 'cli' || getenv('GITHUB_ACTIONS') !== 'true') {
    throw new RuntimeException('This setup is restricted to disposable GitHub runner jobs.');
}
$workspace = realpath((string)getenv('GITHUB_WORKSPACE'));
if ($workspace === false || !is_dir($workspace . '/moodle') || is_file($workspace . '/moodle/config.php')) {
    throw new RuntimeException('A fresh native checkout is required.');
}
require($workspace . '/ci/vendor/autoload.php');

$type = (string)getenv('DB_TYPE');
$host = (string)getenv('DB_HOST');
$user = (string)getenv('DB_USER');
$pass = (string)getenv('DB_PASS');
$port = (string)getenv('DB_PORT');
if (
        !in_array($type, ['pgsql', 'mysqli'], true) || $host !== '127.0.0.1'
        || !in_array($user, ['root', 'postgres'], true) || !in_array($pass, ['', 'postgres'], true)
        || !ctype_digit($port) || (int)$port < 1 || (int)$port > 65535
        || !preg_match('~^[a-zA-Z0-9_./-]+$~D', $workspace)
) {
    throw new RuntimeException('Unexpected isolated-runner configuration.');
}
$resolver = new \MoodlePluginCI\Installer\Database\DatabaseResolver();
foreach (['iomadcertregen', 'iomadcertregen_phpunit', 'iomadcertregen_behat'] as $name) {
    $database = $resolver->resolveDatabase($type, $name, $user, $pass, $host, $port);
    (new \Symfony\Component\Process\Process(array_values($database->getCreateDatabaseCommand())))->mustRun();
}
$database = $resolver->resolveDatabase($type, 'iomadcertregen', $user, $pass, $host, $port);
$data = $workspace . '/moodledata';
foreach ([$data, $data . '/phpu_moodledata', $data . '/behat_moodledata', $data . '/behat_dump'] as $directory) {
    if (!mkdir($directory, 0700, true)) {
        throw new RuntimeException('Unable to create a fresh test data directory.');
    }
}
$builder = new \MoodlePluginCI\Bridge\MoodleConfig();
$config = $builder->createContents($database, $data);
$config = $builder->injectLine($config, "\$CFG->phpunit_dbname = 'iomadcertregen_phpunit';");
$config = $builder->injectLine($config, "\$CFG->behat_dbname = 'iomadcertregen_behat';");
$builder->dump($workspace . '/moodle/config.php', $config);
echo "Configured three isolated native databases; external mail disabled.\n";
