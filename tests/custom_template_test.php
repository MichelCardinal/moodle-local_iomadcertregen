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
 * Installed custom template integration tests.
 *
 * @package    local_iomadcertregen
 * @copyright  2026 Michel Cardinal
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcertregen;

use local_iomadcertregen\local\adapter;
use local_iomadcertregen\local\installed_template;
use local_iomadcertregen\local\publication;
use local_iomadcertregen\local\publication_files;
use local_iomadcertregen\local\records;
use local_iomadcertregen\local\service;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/certificate_testcase.php');

/**
 * Tests use disposable template directories in an isolated native test source tree.
 *
 * @covers \local_iomadcertregen\local\adapter
 * @covers \local_iomadcertregen\local\installed_template
 * @covers \local_iomadcertregen\local\records
 */
#[\PHPUnit\Framework\Attributes\CoversClass(adapter::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(installed_template::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(records::class)]
final class custom_template_test extends certificate_testcase {
    /** @var string[] Test directories to remove after each test. */
    private $directories = [];

    /**
     * Remove only directories created by this test, even after a failed assertion.
     */
    protected function tearDown(): void {
        try {
            foreach ($this->directories as $directory) {
                $file = $directory . '/certificate.php';
                if (file_exists($file) || is_link($file)) {
                    unlink($file);
                }
                rmdir($directory);
            }
            $this->directories = [];
        } finally {
            parent::tearDown();
        }
    }

    /**
     * Install a fictitious template using exactly the native template directory contract.
     *
     * @param string $code Trusted fixture PHP, without an opening tag.
     * @return array Template type and filename.
     */
    private function install_template(string $code): array {
        global $CFG;
        $type = 'icr_test_' . bin2hex(random_bytes(8));
        $directory = $CFG->dirroot . '/mod/iomadcertificate/type/' . $type;
        $this->assertTrue(mkdir($directory));
        $this->directories[] = $directory;
        $path = $directory . '/certificate.php';
        $this->assertNotFalse(file_put_contents($path, "<?php\n" . $code));
        return [$type, $path];
    }

    /**
     * A different installed template and new date settings correct an existing course issue.
     */
    public function test_current_custom_template_and_date_settings_preserve_issued_identity(): void {
        global $DB, $USER;
        [$module, $learner, $cm, , $issue, $context] = $this->fixture(['printdate' => 0]);
        $native = clone $issue;
        $actor = serialize($USER);
        [$type] = $this->install_template(<<<'PHP'
$pdf = new \PDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetCompression(false);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);
$pdf->Text(10, 180, 'CUSTOM-CURRENT-TEMPLATE ' . $certuser->id . ' ' . $certrecord->code);
$pdf->Text(10, 185, 'DATE-SETTING ' . $iomadcertificate->printdate . ' FORMAT ' . $iomadcertificate->datefmt);
$pdf->Text(10, 190, 'ORIGINAL-TIME ' . $certrecord->timecreated);
$pdf->Text(10, 195, 'PRINTED-DATE ' . iomadcertificate_get_date($iomadcertificate, $certrecord, $course, $certuser->id));
PHP
        );
        // The activity was previously issued; correct its model and printed date afterwards.
        $DB->set_field('iomadcertificate', 'iomadcertificatetype', $type, ['id' => $module->id]);
        $DB->set_field('iomadcertificate', 'printdate', 1, ['id' => $module->id]);
        $DB->set_field('iomadcertificate', 'datefmt', 3, ['id' => $module->id]);
        $operation = service::prepare($cm->id, $issue->id, str_repeat('c', 32), sesskey());
        $content = service::file($operation)->get_content();
        $this->assertTrue(
            str_contains($content, 'CUSTOM-CURRENT-TEMPLATE ' . $learner->id . ' ' . $issue->code),
            'The custom PDF must contain the explicit beneficiary and existing issue code.'
        );
        $this->assertTrue(str_contains($content, 'DATE-SETTING 1 FORMAT 3'), 'The current date settings must be used.');
        $this->assertTrue(str_contains($content, 'ORIGINAL-TIME ' . $issue->timecreated), 'The original issue time must be used.');
        $this->assertTrue(str_contains($content, 'PRINTED-DATE 15 January 2024'), 'The original issue date must be printed.');
        $operation = service::confirm($cm->id, $operation->id, sesskey());
        $data = records::issue($cm->id, $issue->id);
        $review = publication_files::fingerprint(publication_files::targets($data));
        publication::publish($cm->id, $operation->id, $review, sesskey());
        $saved = get_file_storage()->get_file($context->id, 'mod_iomadcertificate', 'issue', $issue->id, '/', 'original.pdf');
        $this->assertSame($content, $saved->get_content());
        $this->assertEquals($native, $DB->get_record('iomadcertificate_issues', ['id' => $issue->id]));
        $this->assertSame(1, $DB->count_records('iomadcertificate_issues'));
        $this->assertSame($actor, serialize($USER));
    }

    /**
     * Activity-style presentation aliases identify the beneficiary without impersonating them.
     */
    public function test_local_user_and_site_aliases_preserve_authenticated_actor(): void {
        global $USER;
        [$module, $learner, $cm, $course, $issue] = $this->native_fixture();
        $actor = serialize($USER);
        $actorid = $USER->id;
        [$type] = $this->install_template(<<<'PHP'
$pdf = new \PDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetCompression(false);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);
$pdf->Text(10, 30, 'BENEFICIARY ' . $USER->id . ' ' . fullname($USER));
$pdf->Text(10, 40, 'AUTHENTICATED ' . $GLOBALS['USER']->id);
$pdf->Text(10, 50, 'SITE ' . $SITE->id);
$USER->firstname = 'Presentation only';
PHP
        );
        $module->iomadcertificatetype = $type;
        $content = adapter::render($module, $learner, $cm, $course, $issue);
        $this->assertStringContainsString('BENEFICIARY ' . $learner->id . ' ' . fullname($learner), $content);
        $this->assertStringContainsString('AUTHENTICATED ' . $actorid, $content);
        $this->assertStringContainsString('SITE ' . SITEID, $content);
        $this->assertSame($actor, serialize($USER));
    }

    /**
     * Editing the PHP file after preparation requires another preview.
     */
    public function test_changed_template_bytes_invalidate_prepared_copy(): void {
        global $DB;
        [$module, , $cm, , $issue] = $this->fixture();
        [$type, $path] = $this->install_template("require(dirname(__DIR__) . '/A4_non_embedded/certificate.php');\n");
        $DB->set_field('iomadcertificate', 'iomadcertificatetype', $type, ['id' => $module->id]);
        $operation = service::prepare($cm->id, $issue->id, str_repeat('d', 32), sesskey());
        file_put_contents($path, "\n// Changed layout.\n", FILE_APPEND);
        try {
            service::confirm($cm->id, $operation->id, sesskey());
            $this->fail('Changed PHP must require another preparation.');
        } catch (\moodle_exception $error) {
            $this->assertSame('sourcechanged', $error->errorcode);
        }
        $this->assertSame('prepared', $DB->get_field('local_iomadcertregen_op', 'status', ['id' => $operation->id]));
    }

    /**
     * A changed layout after confirmation cannot replace any saved certificate.
     */
    public function test_changed_template_after_confirmation_prevents_publication(): void {
        global $DB;
        [$module, , $cm, , $issue, $context] = $this->fixture();
        [$type, $path] = $this->install_template("require(dirname(__DIR__) . '/A4_non_embedded/certificate.php');\n");
        $DB->set_field('iomadcertificate', 'iomadcertificatetype', $type, ['id' => $module->id]);
        $operation = service::prepare($cm->id, $issue->id, str_repeat('f', 32), sesskey());
        service::confirm($cm->id, $operation->id, sesskey());
        $review = publication_files::fingerprint(publication_files::targets(records::issue($cm->id, $issue->id)));
        $original = get_file_storage()->get_file($context->id, 'mod_iomadcertificate', 'issue', $issue->id, '/', 'original.pdf');
        $before = $original->get_contenthash();
        file_put_contents($path, "\n// New layout after confirmation.\n", FILE_APPEND);
        try {
            publication::publish($cm->id, $operation->id, $review, sesskey());
            $this->fail('A changed template must not publish an obsolete correction.');
        } catch (\moodle_exception $error) {
            $this->assertSame('sourcechanged', $error->errorcode);
        }
        $this->assertEquals(0, $DB->get_field('local_iomadcertregen_op', 'publishedtime', ['id' => $operation->id]));
        $this->assertSame($before, $original->get_contenthash());
    }

    /**
     * Enabling a date alone also prepares from current settings and the original issue.
     */
    public function test_date_only_correction_keeps_native_issue(): void {
        global $DB;
        [$module, , $cm, , $issue] = $this->fixture(['printdate' => 0]);
        $before = clone $issue;
        $DB->set_field('iomadcertificate', 'printdate', 1, ['id' => $module->id]);
        $DB->set_field('iomadcertificate', 'datefmt', 3, ['id' => $module->id]);
        $operation = service::prepare($cm->id, $issue->id, str_repeat('1', 32), sesskey());
        $this->assertSame('prepared', $operation->status);
        $this->assertSame(records::fingerprint(records::issue($cm->id, $issue->id)), $operation->sourcehash);
        $this->assertEquals($before, $DB->get_record('iomadcertificate_issues', ['id' => $issue->id]));
        $this->assertSame(1, $DB->count_records('iomadcertificate_issues'));
    }

    /**
     * Ordinary native preference lookup is allowed while the actor's state is restored.
     */
    public function test_template_can_read_native_preferences(): void {
        global $USER;
        [$module, $learner, $cm, $course, $issue] = $this->native_fixture();
        [$type] = $this->install_template(<<<'PHP'
get_user_preferences('local_iomadcertregen_presentation', 'default');
require(dirname(__DIR__) . '/A4_non_embedded/certificate.php');
PHP
        );
        $module->iomadcertificatetype = $type;
        unset($USER->preference);
        $actor = serialize($USER);
        $pdf = adapter::render($module, $learner, $cm, $course, $issue);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame($actor, serialize($USER));
    }

    /**
     * A broken template cannot create a correction or replace the original PDF.
     *
     * @dataProvider broken_templates_provider
     * @param string $code Invalid or noisy template body.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('broken_templates_provider')]
    public function test_broken_template_preserves_original_and_output_buffer(string $code): void {
        global $DB, $USER;
        [$module, , $cm, , $issue, $context] = $this->fixture();
        $original = get_file_storage()->get_file($context->id, 'mod_iomadcertificate', 'issue', $issue->id, '/', 'original.pdf');
        $before = $original->get_contenthash();
        [$type] = $this->install_template($code);
        $DB->set_field('iomadcertificate', 'iomadcertificatetype', $type, ['id' => $module->id]);
        $level = ob_get_level();
        $actor = serialize($USER);
        try {
            service::prepare($cm->id, $issue->id, str_repeat('e', 32), sesskey());
            $this->fail('Broken presentation code must not create a correction.');
        } catch (\moodle_exception $error) {
            $this->assertSame('generationfailed', $error->errorcode);
        }
        $this->assertSame($level, ob_get_level());
        $this->assertSame($actor, serialize($USER));
        $this->assertSame(0, $DB->count_records('local_iomadcertregen_op'));
        $this->assertSame($before, $original->get_contenthash());
        $this->assertSame(1, $DB->count_records('iomadcertificate_issues'));
    }

    /**
     * Common template mistakes, without running arbitrary untrusted code.
     *
     * @return array Invalid PHP, exception and accidental browser output cases.
     */
    public static function broken_templates_provider(): array {
        return [
            'syntax' => ['this is not valid PHP;'],
            'exception' => ['throw new \\RuntimeException("Fictitious template failure");'],
            'output' => ["echo 'Unexpected browser output'; require(dirname(__DIR__) . '/A4_non_embedded/certificate.php');"],
            'actor' => ["global \$USER; \$USER->firstname = 'Changed actor'; " .
                "require(dirname(__DIR__) . '/A4_non_embedded/certificate.php');"],
        ];
    }

    /**
     * A database setting cannot resolve outside an installed template directory.
     */
    public function test_template_path_rejects_traversal_streams_and_external_symlinks(): void {
        [$module] = $this->native_fixture();
        foreach (['../company', '/tmp', 'php://input', 'a\\b', '.', '..', "a\0b"] as $type) {
            $module->iomadcertificatetype = $type;
            try {
                adapter::template_file($module);
                $this->fail('Unsafe template path was accepted.');
            } catch (\moodle_exception $error) {
                $this->assertSame('unsupportedtemplate', $error->errorcode);
            }
        }
        [$type, $path] = $this->install_template('');
        unlink($path);
        $target = make_temp_directory('local_iomadcertregen/custom-template') . '/outside.php';
        file_put_contents($target, '<?php');
        $this->assertTrue(symlink($target, $path));
        $module->iomadcertificatetype = $type;
        $this->expectException(\moodle_exception::class);
        adapter::template_file($module);
    }
}
