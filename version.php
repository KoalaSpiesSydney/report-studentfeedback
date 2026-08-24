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
 * Version details for the Student Feedback Reports plugin.
 *
 * @package    report_studentfeedback
 * @copyright  2026 Justine Leigh Kelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// NOTE: version.php must never require_once() anything. Moodle reads this file
// very early, before most of the API is available.

$plugin->component = 'report_studentfeedback';  // Full frankenstyle name. Must match the folder.
$plugin->version   = 2026082400;                // YYYYMMDDXX. Bump on EVERY release or upgrades won't run.
$plugin->requires  = 2023100900;                // Minimum Moodle version (4.3). Raise if you use newer APIs.
$plugin->supported = [43, 501];                 // Lowest and highest Moodle branch tested against.
$plugin->maturity  = MATURITY_ALPHA;            // ALPHA -> BETA -> RC -> STABLE as it firms up.
$plugin->release   = '0.1.0';                   // Human-readable version shown to admins.
