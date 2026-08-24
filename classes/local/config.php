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
 * Report configuration.
 *
 * This class is what kills the "six near-identical HTML files in a folder"
 * problem. Everything that used to be a hardcoded literal — organisation name,
 * section headings, number of writing lines — is read from admin settings here,
 * so one codebase serves every client.
 *
 * @package    report_studentfeedback
 * @copyright  2026 Justine Leigh Kelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentfeedback\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads plugin settings and builds the config handed to the browser.
 */
class config {

    /**
     * Build the report configuration for a given course.
     *
     * @param \context_course $context The course context.
     * @return array Config array, safe to JSON-encode and send to the browser.
     */
    public static function for_context(\context_course $context): array {
        global $SITE;

        // get_config() returns false when a setting has never been saved,
        // so every read needs a sensible fallback.
        $orgname = get_config('report_studentfeedback', 'organisationname');
        if ($orgname === false || trim($orgname) === '') {
            $orgname = format_string($SITE->fullname, true, ['context' => $context]);
        }

        $sectionsraw = get_config('report_studentfeedback', 'sections');
        if ($sectionsraw === false || trim($sectionsraw) === '') {
            $sectionsraw = get_string('sectionsdefault', 'report_studentfeedback');
        }

        // One section per line, in the form:
        //
        //     Heading | Starter text the teacher can keep or amend
        //
        // The starter text is optional — a line with no pipe is just a heading
        // with blank lines under it. That also keeps older configurations,
        // written before starter text existed, working unchanged.
        $sections = [];
        foreach (preg_split('/\R/', $sectionsraw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            // Limit of 2 means any pipes in the starter text itself survive.
            $parts = explode('|', $line, 2);
            $sections[] = [
                'heading' => trim($parts[0]),
                'prompt'  => isset($parts[1]) ? trim($parts[1]) : '',
            ];
        }

        $writinglines = (int) get_config('report_studentfeedback', 'writinglines');
        if ($writinglines < 1 || $writinglines > 20) {
            $writinglines = 3;
        }

        // How the starter text should look in the document.
        //
        //   'draft'  — normal black text. Reads as the teacher's own words, so
        //              it can be left exactly as it is on a finished report.
        //   'sample' — grey italic, as in the original standalone tool. Reads
        //              as "an example, replace me".
        //
        // Choose 'draft' if teachers are expected to keep the text sometimes.
        // Grey italic left on a printed report looks like a forgotten placeholder.
        $promptstyle = get_config('report_studentfeedback', 'promptstyle');
        if ($promptstyle !== 'sample') {
            $promptstyle = 'draft';
        }

        // URL of the uploaded Word template, or null when none is set.
        //
        // A file in a plugin file area is not directly reachable, so it has to
        // be routed through /pluginfile.php — see report_studentfeedback_pluginfile()
        // in lib.php, which checks permissions before serving it.
        $templateurl = null;
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            \context_system::instance()->id,
            'report_studentfeedback',
            'reporttemplate',
            0,
            'itemid, filepath, filename',
            false   // Exclude directory records.
        );
        if ($files) {
            $file = reset($files);
            $templateurl = \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                null,               // Null itemid — the setting stores at itemid 0.
                $file->get_filepath(),
                $file->get_filename()
            )->out(false);
        }

        // Decorative background pattern. Off unless an admin turns it on.
        $patterned = (bool) get_config('report_studentfeedback', 'patterned');

        return [
            'templateurl'  => $templateurl,
            'patterned'    => $patterned,
            'organisation' => $orgname,
            'sections'     => $sections,
            'writinglines' => $writinglines,
            'promptstyle'  => $promptstyle,
        ];
    }
}
