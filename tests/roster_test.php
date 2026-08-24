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
 * Unit tests for the roster class.
 *
 * Automated tests are OPTIONAL for free plugins but EXPECTED for paid ones,
 * so if the plan is to sell this, write them from the start rather than
 * retrofitting later.
 *
 * Run with:  vendor/bin/phpunit --filter report_studentfeedback
 *
 * @package    report_studentfeedback
 * @copyright  2026 Justine Leigh Kelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentfeedback;

use report_studentfeedback\local\roster;

/**
 * Tests for \report_studentfeedback\local\roster.
 *
 * @covers \report_studentfeedback\local\roster
 */
final class roster_test extends \advanced_testcase {

    /**
     * Enrolled students are returned; teachers are not.
     */
    public function test_get_students_excludes_teachers(): void {
        // Every test that touches the database MUST call this. It rolls the
        // database back afterwards so tests cannot contaminate each other.
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $student1 = $generator->create_user(['firstname' => 'Ada', 'lastname' => 'Lovelace']);
        $student2 = $generator->create_user(['firstname' => 'Alan', 'lastname' => 'Turing']);
        $teacher  = $generator->create_user(['firstname' => 'Grace', 'lastname' => 'Hopper']);

        $generator->enrol_user($student1->id, $course->id, 'student');
        $generator->enrol_user($student2->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');

        $roster = new roster($course);
        $students = $roster->get_students();

        $this->assertCount(2, $students);

        $names = array_column($students, 'fullname');
        $this->assertContains('Ada Lovelace', $names);
        $this->assertContains('Alan Turing', $names);
        $this->assertNotContains('Grace Hopper', $names);
    }

    /**
     * Students are sorted by surname.
     */
    public function test_get_students_sorted_by_surname(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $zulu  = $generator->create_user(['firstname' => 'Ann', 'lastname' => 'Zulu']);
        $alpha = $generator->create_user(['firstname' => 'Bob', 'lastname' => 'Alpha']);

        $generator->enrol_user($zulu->id, $course->id, 'student');
        $generator->enrol_user($alpha->id, $course->id, 'student');

        $roster = new roster($course);
        $students = $roster->get_students();

        $this->assertSame('Bob Alpha', $students[0]['fullname']);
        $this->assertSame('Ann Zulu', $students[1]['fullname']);
    }

    /**
     * An empty course returns an empty array, not an error.
     */
    public function test_get_students_empty_course(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $roster = new roster($course);

        $this->assertSame([], $roster->get_students());
    }

    /**
     * Suspended enrolments are excluded.
     *
     * This is exactly the kind of case that hand-rolled SQL gets wrong, which
     * is why the roster class uses get_enrolled_users() instead.
     */
    public function test_get_students_excludes_suspended(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $active = $generator->create_user(['firstname' => 'Active', 'lastname' => 'Student']);
        $suspended = $generator->create_user(['firstname' => 'Suspended', 'lastname' => 'Student']);

        $generator->enrol_user($active->id, $course->id, 'student');
        $generator->enrol_user($suspended->id, $course->id, 'student', 'manual', 0, 0, ENROL_USER_SUSPENDED);

        $roster = new roster($course);
        $students = $roster->get_students();

        $this->assertCount(1, $students);
        $this->assertSame('Active Student', $students[0]['fullname']);
    }

    /**
     * A course with no groups returns an empty group list.
     */
    public function test_get_groups_none(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $roster = new roster($course);

        $this->assertSame([], $roster->get_groups());
    }
}
