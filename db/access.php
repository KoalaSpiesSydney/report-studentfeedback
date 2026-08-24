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
 * Capabilities for the Student Feedback Reports plugin.
 *
 * A "capability" is a named permission. Moodle lets site admins grant or deny
 * each one per role. Declaring them here is what makes your plugin respect
 * Moodle's permission system instead of inventing its own.
 *
 * @package    report_studentfeedback
 * @copyright  2026 Justine Leigh Kelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    // Allows a user to open the report and generate feedback documents for a course.
    'report/studentfeedback:view' => [
        // 'read' because generating a document does not change anything in Moodle.
        'captype'      => 'read',
        // Granted per course, so a teacher gets it only in their own courses.
        'contextlevel' => CONTEXT_COURSE,
        // Sensible defaults. Admins can override any of these.
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],

    // Allows editing the report templates (headings, prompts, branding).
    'report/studentfeedback:managetemplates' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],
];
