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
 * Hook functions for the Student Feedback Reports plugin.
 *
 * Moodle calls functions in lib.php automatically, by name. The names are not
 * arbitrary — <frankenstyle>_<hookname> is a contract. Rename one and Moodle
 * simply stops calling it, silently, which is a confusing way to lose an hour.
 *
 * @package    report_studentfeedback
 * @copyright  2026 Justine Leigh Kelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add a link to this report into the course navigation.
 *
 * Moodle calls this for every course page. Keep it cheap — a slow function
 * here slows down the whole site.
 *
 * @param navigation_node $navigation The course navigation node.
 * @param stdClass $course The course record.
 * @param context_course $context The course context.
 * @return void
 */
function report_studentfeedback_extend_navigation_course($navigation, $course, $context) {
    // Only show the link to people who can actually use it. Showing a link that
    // leads to an "access denied" page is a bug, not a security feature.
    if (!has_capability('report/studentfeedback:view', $context)) {
        return;
    }

    $url = new moodle_url('/report/studentfeedback/index.php', ['id' => $course->id]);

    $navigation->add(
        get_string('generatereports', 'report_studentfeedback'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'report_studentfeedback',
        new pix_icon('i/report', '')
    );
}

/**
 * Serve the uploaded Word template file.
 *
 * Files stored in a plugin file area are NOT publicly reachable. Moodle routes
 * requests to /pluginfile.php through this callback so the plugin can check
 * permissions before handing anything over. Without this function the template
 * upload setting stores a file that nothing can ever download.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool False if the file was not found or access is denied.
 */
function report_studentfeedback_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // The template is a site-level setting, so it lives in the system context.
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    if ($filearea !== 'reporttemplate') {
        return false;
    }

    // Must be logged in. The template can carry a school logo and house style,
    // so it is not for anonymous download.
    require_login();

    $fs = get_file_storage();
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $file = $fs->get_file($context->id, 'report_studentfeedback', $filearea, 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    // 0 lifetime: never cache. An admin who uploads a corrected template expects
    // the next report to use it, not one from an hour ago.
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
