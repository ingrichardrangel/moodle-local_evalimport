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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy provider for local_evalimport.
 *
 * @package    local_evalimport
 * @category   privacy
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalimport\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as request_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Describes and manages the scoring rules stored by the importer.
 *
 * @package    local_evalimport
 * @category   privacy
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements core_userlist_provider, metadata_provider, request_provider {
    /** @var string Moodle definitions and their grading contexts. */
    private const DEFINITIONS_SQL = 'FROM {grading_definitions} gd
                                    JOIN {grading_areas} ga ON ga.id = gd.areaid';

    /** @var string Rules linked to their Moodle grading definitions. */
    private const IMPORTED_RULES_SQL = self::DEFINITIONS_SQL . '
                                    JOIN {local_evalimport_rules} er ON er.definitionid = gd.id';

    /**
     * Declare the stored rules and the Moodle subsystems used by the importer.
     *
     * @param collection $collection The metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_evalimport_rules', [
            'definitionid' => 'privacy:metadata',
            'rules' => 'previewvalidation',
        ], 'privacy:metadata');
        $collection->add_subsystem_link('core_grading', [], 'privacy:metadata');
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata');

        return $collection;
    }

    /**
     * Find activities with import rules linked to rubrics created by the user.
     *
     * @param int $userid The user whose data is being requested.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_from_sql(
            'SELECT DISTINCT ga.contextid ' . self::IMPORTED_RULES_SQL . ' WHERE gd.usercreated = :userid',
            ['userid' => $userid]
        );
        return $contextlist;
    }

    /**
     * Find the creators of imported rubric definitions in an activity.
     *
     * @param userlist $userlist The context and list to populate.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $userlist->add_from_sql(
            'userid',
            'SELECT DISTINCT gd.usercreated AS userid ' . self::IMPORTED_RULES_SQL . ' WHERE ga.contextid = :contextid',
            ['contextid' => $context->id]
        );
    }

    /**
     * Export the stored rules for rubrics the user imported.
     *
     * @param approved_contextlist $contextlist The approved contexts for the user.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $contextids = $contextlist->get_contextids();
        if (!$contextids) {
            return;
        }

        [$contextsql, $params] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');
        $params['userid'] = $contextlist->get_user()->id;
        $sql = 'SELECT er.definitionid, er.rules, ga.contextid ' . self::IMPORTED_RULES_SQL .
            " WHERE ga.contextid $contextsql AND gd.usercreated = :userid";
        $subcontext = [get_string('pluginname', 'local_evalimport')];
        foreach ($DB->get_records_sql($sql, $params) as $record) {
            writer::with_context(context::instance_by_id($record->contextid))->export_related_data(
                $subcontext,
                (string)$record->definitionid,
                (object)[
                    'definitionid' => (int)$record->definitionid,
                    'rules' => json_decode($record->rules) ?? $record->rules,
                ]
            );
        }
    }

    /**
     * Delete the import rules for all rubrics in an approved activity context.
     *
     * @param context $context The context approved for deletion.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        if ($context->contextlevel === CONTEXT_MODULE) {
            self::delete_rules([$context->id]);
        }
    }

    /**
     * Delete import rules for rubrics created by one user in approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts for the user.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        self::delete_rules($contextlist->get_contextids(), [$contextlist->get_user()->id]);
    }

    /**
     * Delete import rules for the approved creators within one activity.
     *
     * @param approved_userlist $userlist The approved users and activity context.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel === CONTEXT_MODULE) {
            self::delete_rules([$context->id], $userlist->get_userids());
        }
    }

    /**
     * Remove only the importer's rules. Moodle manages the rubric definition.
     *
     * @param array $contextids Activity contexts approved for deletion.
     * @param array|null $userids Approved creators, or null for all creators in those contexts.
     * @return void
     */
    private static function delete_rules(array $contextids, ?array $userids = null): void {
        global $DB;
        if (!$contextids || $userids === []) {
            return;
        }

        [$contextsql, $params] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');
        $where = "ga.contextid $contextsql";
        if ($userids !== null) {
            [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'usr');
            $where .= " AND gd.usercreated $usersql";
            $params += $userparams;
        }

        $definitions = 'SELECT gd.id ' . self::DEFINITIONS_SQL . " WHERE $where";
        $DB->delete_records_select('local_evalimport_rules', "definitionid IN ($definitions)", $params);
    }
}
