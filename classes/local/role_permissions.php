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
 * Edit only this plugin's role definition through the native Access API.
 */
final class role_permissions {
    /** @var string The only editable capability. */
    public const CAPABILITY = 'local/iomadcertregen:regenerate';

    /**
     * Read the installed roles, including custom roles, without changing assignments.
     *
     * @return array Roles in their native display order.
     */
    public static function roles(): array {
        global $DB;
        access::require_permission_for_site();
        return role_fix_names($DB->get_records('role', null, 'sortorder, id'), \context_system::instance());
    }

    /**
     * Read the role's system definition; lower-context overrides are deliberately untouched.
     *
     * @param int $roleid Existing role.
     * @return int Native permission constant.
     */
    public static function permission(int $roleid): int {
        global $DB;
        return (int)$DB->get_field('role_capabilities', 'permission', [
            'roleid' => $roleid, 'contextid' => \context_system::instance()->id, 'capability' => self::CAPABILITY,
        ]);
    }

    /**
     * Fingerprint the editable definition to detect a stale administration form.
     *
     * @param int $roleid Existing role.
     * @return string Current revision.
     */
    public static function revision(int $roleid): string {
        global $DB;
        return hash('sha256', json_encode($DB->get_record('role_capabilities', [
            'roleid' => $roleid, 'contextid' => \context_system::instance()->id, 'capability' => self::CAPABILITY,
        ]), JSON_THROW_ON_ERROR));
    }

    /**
     * Fingerprint the installed role set and every editable definition.
     *
     * @return string Reviewed selection revision.
     */
    public static function selection_revision(): string {
        $values = [];
        foreach (self::roles() as $role) {
            $values[$role->id] = [$role->shortname, $role->name, self::revision($role->id)];
        }
        return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
    }

    /**
     * Save one reviewed selection of installed roles through the native capability API.
     *
     * @param array $selected Selected installed role IDs; empty removes all role grants.
     * @param string $revision Reviewed selection revision.
     * @param string $sesskey CSRF protection.
     */
    public static function save_selection(array $selected, string $revision, string $sesskey): void {
        global $DB;
        access::require_permission_for_site();
        require_capability('moodle/role:manage', \context_system::instance());
        if (!confirm_sesskey($sesskey)) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }
        $roles = self::roles();
        foreach ($selected as $id) {
            if (!is_scalar($id) || !ctype_digit((string)$id) || !isset($roles[(int)$id])) {
                throw new \moodle_exception('invalidrolepermission', 'local_iomadcertregen');
            }
        }
        if (!hash_equals(self::selection_revision(), $revision)) {
            throw new \moodle_exception('rolepermissionchanged', 'local_iomadcertregen');
        }
        $transaction = $DB->start_delegated_transaction();
        try {
            foreach ($roles as $role) {
                self::save_access($role->id, (int)in_array($role->id, $selected), self::revision($role->id), $sesskey);
            }
            $transaction->allow_commit();
        } catch (\Throwable $error) {
            $transaction->rollback($error);
        }
    }

    /**
     * Translate the simple choice while preserving an existing refusal when access stays off.
     *
     * @param int $roleid Installed role.
     * @param int $allow One grants access; zero removes this role's grant.
     * @param string $revision Reviewed role definition.
     * @param string $sesskey CSRF protection.
     */
    public static function save_access(int $roleid, int $allow, string $revision, string $sesskey): void {
        access::require_permission_for_site();
        if (!in_array($allow, [0, 1], true)) {
            throw new \moodle_exception('invalidrolepermission', 'local_iomadcertregen');
        }
        $current = self::permission($roleid);
        $permission = $allow ? CAP_ALLOW : ($current === CAP_ALLOW ? CAP_INHERIT : $current);
        self::save($roleid, $permission, $revision, $sesskey);
    }

    /**
     * Save an explicit administrator decision, never a role assignment or another plugin's permission.
     *
     * @param int $roleid Installed role.
     * @param int $permission Native permission constant.
     * @param string $revision Reviewed definition revision.
     * @param string $sesskey CSRF protection.
     */
    public static function save(int $roleid, int $permission, string $revision, string $sesskey): void {
        access::require_permission_for_site();
        require_capability('moodle/role:manage', \context_system::instance());
        if (!confirm_sesskey($sesskey)) {
            throw new \moodle_exception('invalidsesskey', 'error');
        }
        $roles = self::roles();
        if (!isset($roles[$roleid]) || !in_array($permission, [CAP_INHERIT, CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT], true)) {
            throw new \moodle_exception('invalidrolepermission', 'local_iomadcertregen');
        }
        if (!hash_equals(self::revision($roleid), $revision)) {
            throw new \moodle_exception('rolepermissionchanged', 'local_iomadcertregen');
        }
        if (self::permission($roleid) !== $permission) {
            assign_capability(self::CAPABILITY, $permission, $roleid, \context_system::instance(), true);
        }
    }
}
