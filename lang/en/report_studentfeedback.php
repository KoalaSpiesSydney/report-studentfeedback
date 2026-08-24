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
 * English language strings for the Student Feedback Reports plugin.
 *
 * EVERY piece of text a user can see must live in here and be fetched with
 * get_string(). That is what lets the Moodle community translate your plugin
 * into 100+ languages for free. Hardcoded English in a .php or .mustache file
 * is a review blocker.
 *
 * NOTE: language files must never require_once() anything.
 *
 * @package    report_studentfeedback
 * @copyright  2026 Justine Leigh Kelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Student feedback reports';

// Navigation and page titles.
$string['generatereports'] = 'Generate feedback reports';
$string['pagetitle'] = 'Student feedback reports: {$a}';

// Capabilities. The key format is exact: <type>/<name>:<capability>.
$string['studentfeedback:view'] = 'Generate student feedback reports';
$string['studentfeedback:managetemplates'] = 'Manage feedback report templates';

// The roster step.
$string['step1heading'] = 'Step 1 — Check the class list';
$string['step1help'] = 'These students are enrolled in this course. Untick anyone who should not receive a report.';
$string['nostudents'] = 'No students are enrolled in this course, so there is nothing to generate.';
$string['fromthiscourse'] = 'From this course';
$string['savedautomatically'] = 'Saved automatically';
$string['detailssaved'] = '&#10003; Details saved — remembered next time.';
$string['selectall'] = 'Select all';
$string['selectnone'] = 'Select none';
$string['studentcount'] = '{$a} student(s) selected';

// The details step.
$string['step2heading'] = 'Step 2 — Report details';
$string['step2help'] = 'These appear on every report. They are remembered for next time.';
$string['teachername'] = 'Teacher name';
$string['teachername_help'] = 'Printed under the signature line on each report.';
$string['location'] = 'Location';
$string['programme'] = 'Programme';
$string['campname'] = 'Course or camp name';

// The download step.
$string['step3heading'] = 'Step 3 — Download';
$string['downloadreports'] = 'Download Word reports';
$string['generating'] = 'Creating {$a->done} of {$a->total}…';
$string['zipping'] = 'Preparing your download…';
$string['generatedsingle'] = 'Downloaded a report for {$a}.';
$string['generatedmultiple'] = 'Downloaded {$a} reports as a ZIP file.';
$string['generationfailed'] = 'Could not generate the reports: {$a}';

// Settings.
$string['patterned'] = 'Decorative background';
$string['patterned_desc'] = 'Paint a soft gum-leaf pattern behind the report page, as in the original standalone tool.

Off by default. On a Moodle site with its own branding, a decorative background coming from a plugin usually looks like a theme fault rather than a design choice.';
$string['reporttemplate'] = 'Word report template';
$string['reporttemplate_desc'] = 'Upload a Word (.docx) template and the plugin will fill it in for each student, keeping your logo, fonts and layout exactly as designed.

Write these placeholders anywhere in the document — they are replaced per student:

<ul>
<li><code>{{studentname}}</code> — the student\'s full name</li>
<li><code>{{coursename}}</code> — the Moodle course name</li>
<li><code>{{teachername}}</code> — from the teacher\'s entry on the report page</li>
<li><code>{{location}}</code>, <code>{{programme}}</code>, <code>{{campname}}</code> — from the report page</li>
<li><code>{{organisation}}</code> — the organisation name set below</li>
<li><code>{{date}}</code> — the date the report was generated</li>
</ul>

Leave this empty to build a plain document from the section settings below instead.';
$string['templateinuse'] = 'Reports will be built from the uploaded Word template.';
$string['templateplaceholders'] = 'Placeholders found in the template: {$a}';
$string['templatenoplaceholders'] = 'Warning: no placeholders were found in the uploaded template. Every report will come out identical. Check for typos — placeholders look like {{studentname}}.';
$string['templateloadfailed'] = 'The Word template could not be loaded: {$a}';

$string['settingsheading'] = 'Default report content';
$string['settingsheadingdesc'] = 'These defaults apply site-wide. Teachers can override the details on each report.';
$string['organisationname'] = 'Organisation name';
$string['organisationname_desc'] = 'Printed in the header of every report. Leave blank to use the site name.';
$string['sections'] = 'Report sections';
$string['sections_desc'] = 'One section per line, in the form <strong>Heading | Starter text</strong>.

The starter text is printed under the heading and can be kept as written or edited by the teacher. Leave off the pipe and the starter text to print a heading with blank lines only.';
$string['sectionsdefault'] = 'Assessment of progress | Improved confidence in speaking and listening. Writing tasks show stronger grammar awareness and better sentence structure.
Participation in class | Engaged and focused in every lesson. Always willing to help classmates and take part in group tasks.
Action plan | Keep expanding vocabulary through reading. Try using new expressions in speaking activities to build fluency.
Final comments | A pleasure to teach this term. Steady progress across all areas and a positive attitude throughout.';
$string['writinglines'] = 'Writing lines per section';
$string['writinglines_desc'] = 'How many ruled blank lines to print under each section, for the teacher to write on.';
$string['promptstyle'] = 'Starter text style';
$string['promptstyle_desc'] = 'How the starter text appears in the document.

<strong>Editable draft</strong> prints it as normal text, so a teacher can leave it exactly as it is on a finished report.

<strong>Greyed example</strong> prints it in grey italics, as a prompt clearly meant to be replaced. Be aware that grey italic text left on a printed report reads as a placeholder the teacher forgot to remove.';
$string['promptstyledraft'] = 'Editable draft — normal text, safe to keep';
$string['promptstylesample'] = 'Greyed example — grey italics, meant to be replaced';

// Privacy.
$string['privacy:metadata'] = 'The Student feedback reports plugin does not store any personal data. It reads the existing course enrolment list and builds documents in the user\'s browser.';
