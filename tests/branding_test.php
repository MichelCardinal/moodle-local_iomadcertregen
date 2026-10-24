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

namespace local_iomadcertregen;

use local_iomadcertregen\local\service;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/certificate_testcase.php');

/**
 * Company branding must belong to the beneficiary, independently of the administrator.
 *
 * @covers \local_iomadcertregen\local\adapter
 * @covers \local_iomadcertregen\local\service
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\local\adapter::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\local\service::class)]
final class branding_test extends certificate_testcase {
    /**
     * Create a real PNG with distinctive dimensions and colour.
     *
     * @param int $width Image width.
     * @param int $height Image height.
     * @param int $red Red component.
     * @param int $blue Blue component.
     * @return string PNG bytes.
     */
    private function image(int $width, int $height, int $red, int $blue): string {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, $red, 0, $blue));
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);
        return $png;
    }

    /**
     * Associate a fictitious user with a company and its logo through native storage.
     *
     * @param int $userid Beneficiary or administrator.
     * @param string $name Fictitious company identifier.
     * @param string $png PNG logo bytes.
     * @return int Company identifier.
     */
    private function company(int $userid, string $name, string $png): int {
        global $CFG, $DB;
        $modern = (int)$CFG->branch >= 501;
        $companyid = $DB->insert_record($modern ? 'local_iomad_companies' : 'company', (object)[
            'name' => $name, 'shortname' => $name, 'city' => 'Example', 'country' => 'CA', 'theme' => '',
        ]);
        $departmentid = $DB->insert_record($modern ? 'local_iomad_company_departments' : 'department', (object)[
            'name' => $name, 'shortname' => $name, 'company' => $companyid,
        ]);
        $DB->insert_record($modern ? 'local_iomad_company_users' : 'company_users', (object)[
            'companyid' => $companyid, 'userid' => $userid, 'departmentid' => $departmentid, 'lastused' => time(),
        ]);
        get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id, 'component' => 'local_iomad',
            'filearea' => 'companycertificateseal', 'itemid' => $companyid, 'filepath' => '/',
            'filename' => 'fictional-logo.png', 'mimetype' => 'image/png',
        ], $png);
        return $companyid;
    }

    /**
     * The learner's distinct image dimensions are present; the administrator's are absent.
     *
     * @dataProvider company_templates_provider
     * @param string $template Native company template.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('company_templates_provider')]
    public function test_company_branding_uses_beneficiary(string $template): void {
        global $CFG, $DB, $USER, $SESSION;
        $companyusertable = (int)$CFG->branch >= 501 ? 'local_iomad_company_users' : 'company_users';
        [$module, $learner, $cm, , $issue, $context] = $this->fixture();
        $DB->set_field('iomadcertificate', 'iomadcertificatetype', $template, ['id' => $module->id]);
        $red = $this->image(3, 3, 255, 0);
        $blue = $this->image(7, 5, 0, 255);
        $this->company($learner->id, 'Fictional learner', $red);
        $actorcompany = $this->company($USER->id, 'Fictional actor', $blue);
        $original = get_file_storage()->get_file(
            $context->id,
            'mod_iomadcertificate',
            'issue',
            $issue->id,
            '/',
            'original.pdf'
        );
        $originalhash = hash('sha256', $original->get_content());
        $issuebefore = $DB->get_record('iomadcertificate_issues', ['id' => $issue->id]);
        $before = $DB->get_records($companyusertable, [], 'id');
        $actor = serialize($USER);
        $previous = $SESSION->currenteditingcompany ?? null;
        $SESSION->currenteditingcompany = $actorcompany;
        try {
            $operation = service::prepare($cm->id, $issue->id, str_repeat('b5', 16), sesskey());
            $pdf = service::file($operation)->get_content();
            // Native company PDFs encrypt streams; PDF image dimensions remain inspectable.
            $this->assertSame(1, preg_match('/\/Subtype\s*\/Image\b[^>]*\/Width\s+3\b[^>]*\/Height\s+3\b/s', $pdf));
            $this->assertSame(0, preg_match('/\/Subtype\s*\/Image\b[^>]*\/Width\s+7\b[^>]*\/Height\s+5\b/s', $pdf));
            $this->assertSame('confirmed', service::confirm($cm->id, $operation->id, sesskey())->status);
            $this->assertSame($actor, serialize($USER));
            $this->assertEquals($before, $DB->get_records($companyusertable, [], 'id'));
            $original = get_file_storage()->get_file(
                $context->id,
                'mod_iomadcertificate',
                'issue',
                $issue->id,
                '/',
                'original.pdf'
            );
            $this->assertSame($originalhash, hash('sha256', $original->get_content()));
            $this->assertEquals($issuebefore, $DB->get_record('iomadcertificate_issues', ['id' => $issue->id]));
            $this->assertSame(1, $DB->count_records('iomadcertificate_issues'));
        } finally {
            if ($previous === null) {
                unset($SESSION->currenteditingcompany);
            } else {
                $SESSION->currenteditingcompany = $previous;
            }
        }
    }

    /**
     * Both company templates must select beneficiary assets explicitly.
     *
     * @return array Template scenarios.
     */
    public static function company_templates_provider(): array {
        return [['company'], ['companykanji']];
    }
}
