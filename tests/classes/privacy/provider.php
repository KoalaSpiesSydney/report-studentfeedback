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
 * Privacy API implementation for the Student Feedback Reports plugin.
 *
 * THIS FILE IS MANDATORY. A missing privacy provider is the single most common
 * reason plugins are rejected from the Moodle Marketplace.
 *
 * Right now this plugin is a "null provider": it reads the enrolment list that
 * Moodle already holds, builds documents in the teacher's browser, and stores
 * nothing of its own. That is a legitimate declaration and it is honest.
 *
 * ---------------------------------------------------------------------------
 * IMPORTANT — read this before you add features.
 *
 * The moment this plugin stores ANY per-user data in the database — an issued-
 * reports log, saved draft comments, a record of who generated what — this
 * null_provider becomes a FALSE DECLARATION, which is worse than having none.
 *
 * At that point you must switch to:
 *
 *     class provider implements
 *         \core_privacy\local\metadata\provider,
 *         \core_privacy\local\request\plugin\provider,
 *         \core_privacy\local\request\core_userlist_provider {
 *
 * and implement get_metadata(), get_contexts_for_userid(), export_user_data(),
 * delete_data_for_all_users_in_context(), delete_data_for_user(),
 * get_users_in_context() and delete_data_for_users().
 * ---------------------------------------------------------------------------
 *
 * @package    report_studentfeedback
 * @copyright  2026 Justine Leigh Kelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentfeedback\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider. Declares that this plugin stores no personal data.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * Explain why this plugin stores no personal data.
     *
     * Must return a language string KEY (not the text itself) that exists in
     * lang/en/report_studentfeedback.php.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
