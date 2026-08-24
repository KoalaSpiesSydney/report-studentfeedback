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
 * The page a teacher lands on.
 *
 * Read the order of operations here carefully — it is the standard Moodle
 * page pattern and reviewers expect to see it in exactly this shape:
 *
 *   1. require config.php
 *   2. read parameters with required_param() / optional_param()
 *   3. load the records those parameters point at
 *   4. require_login()
 *   5. require_capability()
 *   6. set up $PAGE
 *   7. only then produce output
 *
 * @package    report_studentfeedback
 * @copyright  2026 Justine Leigh Kelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This path walks up to Moodle's root. It is correct for a plugin living in
// /report/studentfeedback/ — three levels down from the Moodle root.
require_once(__DIR__ . '/../../config.php');

// STEP 2 — Parameters.
// NEVER touch $_GET or $_POST directly. required_param() enforces the type as
// well as reading the value, so PARAM_INT *is* your input validation.
$courseid = required_param('id', PARAM_INT);
$groupid  = optional_param('group', 0, PARAM_INT);

// STEP 3 — Load the records. MUST_EXIST turns a bad id into a clean error
// rather than a fatal further down.
$course = get_course($courseid);
$context = context_course::instance($course->id);

// STEP 4 — Authentication. This also handles guest access, enrolment checks
// and the "you must log in" redirect for you.
require_login($course);

// STEP 5 — Authorisation. Skipping this is a security blocker; it is the first
// thing a reviewer looks for.
require_capability('report/studentfeedback:view', $context);

// STEP 6 — Page setup.
$url = new moodle_url('/report/studentfeedback/index.php', ['id' => $course->id]);
if ($groupid) {
    $url->param('group', $groupid);
}
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pagetitle', 'report_studentfeedback', format_string($course->shortname)));
$PAGE->set_heading(format_string($course->fullname));

// Gather the data.
$roster = new \report_studentfeedback\local\roster($course);
$students = $roster->get_students($groupid);
$groups = $roster->get_groups();
$reportconfig = \report_studentfeedback\local\config::for_context($context);

// Load the bundled document libraries.
//
// These are plain (non-AMD) scripts that define the globals `docx`, `JSZip`
// and `saveAs`. They must be declared in thirdpartylibs.xml — see that file.
// The second argument puts them in <head> so they are defined before our AMD
// module runs.
// Order matters: docx's browser build expects JSZip to already exist.
$PAGE->requires->js(new moodle_url('/report/studentfeedback/js/vendor/jszip/jszip.min.js'), true);
$PAGE->requires->js(new moodle_url('/report/studentfeedback/js/vendor/filesaver/FileSaver.min.js'), true);
$PAGE->requires->js(new moodle_url('/report/studentfeedback/js/vendor/docx/index.iife.js'), true);

// Hand the data to our own JavaScript.
//
// call_amd() JSON-encodes its arguments safely. This is the correct way to get
// server data into browser code — never echo a raw <script> block with values
// interpolated into it.
$PAGE->requires->js_call_amd('report_studentfeedback/generator', 'init', [[
    'students'   => $students,
    'config'     => $reportconfig,
    'coursename' => $roster->get_coursename(),
    'contextid'  => $context->id,
]]);

// Preload the strings our JavaScript needs, so it can fetch them without a
// round trip. Every one of these must exist in lang/en/.
$PAGE->requires->strings_for_js([
    'generating',
    'zipping',
    'generatedsingle',
    'generatedmultiple',
    'generationfailed',
    'studentcount',
], 'report_studentfeedback');

// STEP 7 — Output.
echo $OUTPUT->header();

if (empty($students)) {
    // notification() escapes its input and renders a proper Moodle alert.
    echo $OUTPUT->notification(
        get_string('nostudents', 'report_studentfeedback'),
        \core\output\notification::NOTIFY_INFO
    );
    echo $OUTPUT->footer();
    exit;
}

// Render through a Mustache template rather than echoing HTML from PHP.
// Templates are themeable, translatable and testable; inline HTML is none of those.
$templatedata = [
    'students'    => $students,
    'groups'      => $groups,
    'hasgroups'   => !empty($groups),
    'courseid'    => $course->id,
    'currentgroup' => $groupid,
    'formaction'  => (new moodle_url('/report/studentfeedback/index.php'))->out(false),
    // sesskey is Moodle's CSRF token. Any form that causes an action needs it.
    'sesskey'     => sesskey(),
    // Shown in the black panel header when set.
    'organisation' => $reportconfig['organisation'],
    // Whether to paint the decorative gum-leaf pattern behind the page.
    // Off by default: on a school's own branded Moodle a decorative background
    // from a plugin reads as a theme fault rather than a feature.
    'patterned'   => !empty($reportconfig['patterned']),
];

echo $OUTPUT->render_from_template('report_studentfeedback/main', $templatedata);

echo $OUTPUT->footer();
