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
 * Admin settings for the Student Feedback Reports plugin.
 *
 * This file is what turns "one HTML file per client" into "one plugin,
 * configured per site". Everything that was a hardcoded literal in the
 * standalone version becomes a setting here.
 *
 * @package    report_studentfeedback
 * @copyright  2026 Justine Leigh Kelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// $ADMIN and $hassiteconfig are provided by Moodle when it builds the admin tree.
if ($hassiteconfig) {

    $settings = new admin_settingpage(
        'report_studentfeedback',
        get_string('pluginname', 'report_studentfeedback')
    );

    $settings->add(new admin_setting_heading(
        'report_studentfeedback/heading',
        get_string('settingsheading', 'report_studentfeedback'),
        get_string('settingsheadingdesc', 'report_studentfeedback')
    ));

    // Organisation name — replaces the hardcoded school name.
    $settings->add(new admin_setting_configtext(
        'report_studentfeedback/organisationname',
        get_string('organisationname', 'report_studentfeedback'),
        get_string('organisationname_desc', 'report_studentfeedback'),
        '',              // Default: empty, falls back to the site name.
        PARAM_TEXT       // PARAM_TEXT strips tags. Always declare the type.
    ));

    // Section headings — replaces the hardcoded sectionHead() calls.
    $settings->add(new admin_setting_configtextarea(
        'report_studentfeedback/sections',
        get_string('sections', 'report_studentfeedback'),
        get_string('sections_desc', 'report_studentfeedback'),
        get_string('sectionsdefault', 'report_studentfeedback'),
        PARAM_TEXT
    ));

    // Writing lines per section — replaces the hardcoded three writingLine() calls.
    $settings->add(new admin_setting_configselect(
        'report_studentfeedback/writinglines',
        get_string('writinglines', 'report_studentfeedback'),
        get_string('writinglines_desc', 'report_studentfeedback'),
        3,
        array_combine(range(1, 10), range(1, 10))
    ));

    // Decorative background. Deliberately defaults to OFF: a plugin painting a
    // pattern behind its page will clash with a school's own Moodle theme, and
    // an admin should opt into that rather than discover it.
    $settings->add(new admin_setting_configcheckbox(
        'report_studentfeedback/patterned',
        get_string('patterned', 'report_studentfeedback'),
        get_string('patterned_desc', 'report_studentfeedback'),
        0
    ));

    // ------------------------------------------------------------------
    // Word template upload.
    //
    // When a template is uploaded the plugin fills IT, preserving the logo,
    // fonts and layout exactly as designed in Word. With no template it falls
    // back to building a plain document from the settings below.
    // ------------------------------------------------------------------
    $settings->add(new admin_setting_configstoredfile(
        'report_studentfeedback/reporttemplate',
        get_string('reporttemplate', 'report_studentfeedback'),
        get_string('reporttemplate_desc', 'report_studentfeedback'),
        'reporttemplate',
        0,
        [
            'maxfiles' => 1,
            'accepted_types' => ['.docx'],
        ]
    ));

    // How the starter text is styled — replaces the hardcoded sampleText() styling.
    $settings->add(new admin_setting_configselect(
        'report_studentfeedback/promptstyle',
        get_string('promptstyle', 'report_studentfeedback'),
        get_string('promptstyle_desc', 'report_studentfeedback'),
        'draft',
        [
            'draft'  => get_string('promptstyledraft', 'report_studentfeedback'),
            'sample' => get_string('promptstylesample', 'report_studentfeedback'),
        ]
    ));

    $ADMIN->add('reports', $settings);
}
