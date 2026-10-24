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

use local_iomadcertregen\local\{learner, publication_files, service};

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/fixtures/publication_testcase.php');

/**
 * A personal native issue must not carry a former or ambiguous company's document into another tenant.
 *
 * @covers \local_iomadcertregen\local\records
 * @covers \local_iomadcertregen\local\publication_files
 * @covers \local_iomadcertregen\local\learner
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_iomadcertregen\local\records::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(publication_files::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(learner::class)]
final class publication_tenant_test extends publication_testcase {
    /**
     * A company transfer between preparation and publication invalidates the reviewed source.
     */
    public function test_changed_company_before_publication_is_refused(): void {
        [$data, $operation] = $this->correction();
        $this->company_scope($data, $this->getDataGenerator()->create_user()->id);
        $this->expectException(\moodle_exception::class);
        $this->publish($data, $operation);
    }

    /**
     * A correction for the current company must not overwrite the old company's historical PDF.
     */
    public function test_former_company_history_is_excluded(): void {
        [$data, , $files] = $this->correction();
        $files = array_filter($files, static fn($file) => $file->get_component() !== 'mod_iomadcertificate');
        $history = reset($files);
        $original = $history->get_content();
        $this->transfer($data);
        $operation = $this->fresh_copy($data, 'b0');
        $this->assertCount(1, publication_files::targets($data));
        $this->publish($data, $operation);
        $this->assertSame($original, get_file_storage()->get_file_by_id($history->get_id())->get_content());
    }

    /**
     * A learner retains their personal snapshot after transfer; a new company's manager must not receive it.
     */
    public function test_transfer_keeps_owner_copy_private_from_new_manager(): void {
        global $CFG, $DB;
        [$data] = $this->correction();
        $oldcompany = $this->company_scope($data, $this->getDataGenerator()->create_user()->id);
        $operation = $this->fresh_copy($data, 'c0');
        $this->publish($data, $operation);
        $manager = $this->getDataGenerator()->create_user();
        $companyid = $this->company_scope($data, $manager->id);
        $table = (int)$CFG->branch >= 501 ? 'local_iomad_company_users' : 'company_users';
        $DB->delete_records($table, ['companyid' => $oldcompany, 'userid' => $data[1]->id]);
        $this->manager_role($manager->id);
        $this->getDataGenerator()->enrol_user($manager->id, $data[3]->id, 'student');
        $this->setUser($data[1]);
        $GLOBALS['PAGE'] = new \moodle_page();
        [, $file] = learner::document($data[2]->id, $data[1]->id);
        $this->assertSame(service::file($operation)->get_content(), $file->get_content());
        $this->setUser($manager);
        $GLOBALS['SESSION']->currenteditingcompany = $companyid;
        $GLOBALS['PAGE'] = new \moodle_page();
        $this->expectException(\moodle_exception::class);
        learner::document($data[2]->id, $data[1]->id);
    }

    /**
     * A shared user's global activity document cannot be guessed to belong to either company manager.
     */
    public function test_multi_company_activity_delegation_is_refused(): void {
        [$data] = $this->correction();
        $manager = $this->getDataGenerator()->create_user();
        $companyid = $this->company_scope($data, $manager->id);
        $this->company_scope($data, $this->getDataGenerator()->create_user()->id);
        $operation = $this->fresh_copy($data, 'd0');
        $this->publish($data, $operation);
        $this->getDataGenerator()->enrol_user($manager->id, $data[3]->id, 'student');
        $this->manager_role($manager->id);
        $this->setUser($manager);
        $GLOBALS['SESSION']->currenteditingcompany = $companyid;
        $GLOBALS['PAGE'] = new \moodle_page();
        $this->expectException(\required_capability_exception::class);
        learner::document($data[2]->id, $data[1]->id);
    }

    /**
     * Transfer only current membership; preserve the historical record's original company.
     *
     * @param array $data Native fixture.
     */
    private function transfer(array $data): void {
        global $CFG, $DB;
        $old = $this->company_scope($data, $this->getDataGenerator()->create_user()->id);
        $this->company_scope($data, $this->getDataGenerator()->create_user()->id);
        $modern = (int)$CFG->branch >= 501;
        $DB->delete_records($modern ? 'local_iomad_company_users' : 'company_users', [
            'companyid' => $old, 'userid' => $data[1]->id,
        ]);
        $DB->set_field($modern ? 'local_iomad_tracks' : 'local_iomad_track', 'companyid', $old, [
            'courseid' => $data[3]->id, 'userid' => $data[1]->id,
        ]);
    }

    /**
     * Review a new copy after membership setup instead of reusing a stale source fingerprint.
     *
     * @param array $data Native fixture.
     * @param string $key Distinct nonce pair.
     * @return \stdClass Confirmed correction.
     */
    private function fresh_copy(array $data, string $key): \stdClass {
        $copy = service::prepare($data[2]->id, $data[4]->id, str_repeat($key, 16), sesskey());
        return service::confirm($data[2]->id, $copy->id, sesskey());
    }
}
