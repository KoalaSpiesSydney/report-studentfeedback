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
 * Reads the class roster out of Moodle.
 *
 * THIS IS THE WHOLE POINT OF THE PLUGIN. Everything else is packaging.
 *
 * The standalone HTML version made a teacher type 20 names by hand. This class
 * asks Moodle who is actually enrolled. That single change is the difference
 * between a Word template and a product.
 *
 * @package    report_studentfeedback
 * @copyright  2026 Justine Leigh Kelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentfeedback\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Course roster lookup.
 */
class roster {

    /** @var \context_course The course context we are reading. */
    protected $context;

    /** @var \stdClass The course record. */
    protected $course;

    /**
     * Constructor.
     *
     * @param \stdClass $course A full course record.
     */
    public function __construct(\stdClass $course) {
        $this->course = $course;
        $this->context = \context_course::instance($course->id);
    }

    /**
     * Get the students enrolled in this course.
     *
     * Deliberately returns only what the report needs — id and display name.
     * Pulling extra personal fields you do not use is exactly the habit that
     * turns a null privacy provider into a false declaration later.
     *
     * @param int $groupid Optional group to filter by. 0 means all groups.
     * @return array List of ['id' => int, 'fullname' => string], sorted by surname.
     */
    public function get_students(int $groupid = 0): array {
        // get_enrolled_users() respects enrolment status, group restrictions and
        // the course's own visibility rules. Do NOT hand-roll this with SQL —
        // you will get suspended and expired enrolments wrong.
        //
        // Filtering on a *student-ish* capability is how we exclude teachers and
        // managers from the list without hardcoding role names (roles are
        // renameable and sites invent their own).
        $users = get_enrolled_users(
            $this->context,
            'mod/assign:submit',
            $groupid,
            'u.id, u.firstname, u.lastname',
            'u.lastname ASC, u.firstname ASC',
            0,
            0,
            true  // Only active enrolments.
        );

        $students = [];
        foreach ($users as $user) {
            $students[] = [
                'id'       => (int) $user->id,
                // fullname() honours the site's "full name format" setting and
                // the viewer's permission to see identity fields.
                'fullname' => fullname($user),
            ];
        }

        return $students;
    }

    /**
     * Get the groups in this course that the current user may see.
     *
     * Teachers restricted to separate groups must not be offered groups they
     * cannot access, so this respects the course group mode.
     *
     * @return array List of ['id' => int, 'name' => string].
     */
    public function get_groups(): array {
        global $USER;

        $groupmode = groups_get_course_groupmode($this->course);

        if ($groupmode == NOGROUPS) {
            return [];
        }

        // In SEPARATEGROUPS mode, a user without accessallgroups sees only their own.
        if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $this->context)) {
            $groups = groups_get_all_groups($this->course->id, $USER->id);
        } else {
            $groups = groups_get_all_groups($this->course->id);
        }

        $result = [];
        foreach ($groups as $group) {
            $result[] = [
                'id'   => (int) $group->id,
                // format_string() strips unsafe markup and applies filters.
                // Never echo a raw database string into a page.
                'name' => format_string($group->name, true, ['context' => $this->context]),
            ];
        }

        return $result;
    }

    /**
     * The course name, safe to display.
     *
     * @return string
     */
    public function get_coursename(): string {
        return format_string(
            $this->course->fullname,
            true,
            ['context' => $this->context]
        );
    }
}
