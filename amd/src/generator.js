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
 * Feedback report generator.
 *
 * THIS IS YOUR EXISTING CODE, ported. buildDoc() below is substantially the
 * same function from student_report_generator.html — the docx layout work you
 * already did carries over almost unchanged.
 *
 * Two things changed:
 *   1. Student names arrive from Moodle's enrolment list, not from text boxes.
 *   2. Section headings and organisation name arrive from admin settings,
 *      not from hardcoded literals.
 *
 * The libraries `docx`, `JSZip` and `saveAs` are loaded as plain scripts by
 * index.php and are available as globals here.
 *
 * @module     report_studentfeedback/generator
 * @copyright  2026 Justine Leigh Kelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import {fillTemplate} from './docxtemplate';
import Notification from 'core/notification';

/** @var {Object} Module state. */
/** @type {ArrayBuffer|null} Fetched template, cached across a batch. */
let templateCache = null;

let state = {
    students: [],
    config: {},
    coursename: '',
};

/** @var {String} Prefix for remembering the detail fields per course. */
const STORAGE_PREFIX = 'report_studentfeedback_';

/** @var {Array} The detail fields we remember between visits. */
const DETAIL_FIELDS = ['teacher', 'location', 'programme', 'campname'];

/**
 * Entry point. Called from index.php via js_call_amd().
 *
 * @param {Object} data Server-supplied data: students, config, coursename.
 */
export const init = (data) => {
    state = data;

    const root = document.querySelector('[data-region="studentfeedback"]');
    if (!root) {
        return;
    }

    restoreDetails();
    registerListeners(root);
    updateCount(root);
};

/**
 * Restore the detail fields a teacher typed last time.
 *
 * localStorage can throw in private browsing modes, so every access is guarded.
 * Note this is a per-browser convenience only — it is NOT shared between staff.
 * If the client needs shared defaults, those belong in admin settings instead.
 */
const restoreDetails = () => {
    DETAIL_FIELDS.forEach((field) => {
        try {
            const value = window.localStorage.getItem(STORAGE_PREFIX + field);
            const input = document.querySelector(`[data-field="${field}"]`);
            if (value && input) {
                input.value = value;
            }
        } catch (e) {
            // Storage unavailable. Not fatal — the teacher just retypes.
        }
    });
};

/**
 * Save a detail field for next time.
 *
 * @param {String} field The field name.
 * @param {String} value The value to remember.
 */
const saveDetail = (field, value) => {
    try {
        window.localStorage.setItem(STORAGE_PREFIX + field, value);
    } catch (e) {
        // Storage unavailable. Ignore.
    }
};

/**
 * Wire up the interface.
 *
 * One delegated listener on the root beats one listener per checkbox — it keeps
 * working if the list is ever re-rendered.
 *
 * @param {HTMLElement} root The plugin's root element.
 */
const registerListeners = (root) => {
    root.addEventListener('click', (e) => {
        const action = e.target.closest('[data-action]');

        if (action && action.dataset.action === 'select-all') {
            setAllCheckboxes(root, true);
            updateCount(root);
            return;
        }
        if (action && action.dataset.action === 'select-none') {
            setAllCheckboxes(root, false);
            updateCount(root);
            return;
        }
        if (action && action.dataset.action === 'generate') {
            generateAll(root).catch(Notification.exception);
            return;
        }
        if (e.target.matches('[data-student-id]')) {
            updateCount(root);
        }
    });

    root.addEventListener('input', (e) => {
        if (e.target.matches('[data-field]')) {
            saveDetail(e.target.dataset.field, e.target.value);
        }
    });
};

/**
 * Tick or untick every student.
 *
 * @param {HTMLElement} root The plugin's root element.
 * @param {Boolean} checked Whether to tick.
 */
const setAllCheckboxes = (root, checked) => {
    // Keep the selected-row highlight in step with the checkbox.
    root.querySelectorAll('[data-student-id]').forEach((box) => {
        const row = box.closest('.sf-student');
        if (row) {
            row.classList.toggle('sf-on', box.checked);
        }
    });

    root.querySelectorAll('[data-student-id]').forEach((box) => {
        box.checked = checked;
    });
};

/**
 * Get the students the teacher has ticked.
 *
 * @param {HTMLElement} root The plugin's root element.
 * @return {Array} Objects with id and name.
 */
const getSelectedStudents = (root) => {
    return Array.from(root.querySelectorAll('[data-student-id]:checked'))
        .map((box) => ({
            id: parseInt(box.dataset.studentId, 10),
            name: box.dataset.studentName,
        }));
};

/**
 * Update the "N students selected" counter.
 *
 * @param {HTMLElement} root The plugin's root element.
 */
const updateCount = (root) => {
    const count = getSelectedStudents(root).length;
    const target = root.querySelector('[data-region="student-count"]');
    if (target) {
        getString('studentcount', 'report_studentfeedback', count)
            .then((text) => {
                target.textContent = text;
                return text;
            })
            .catch(() => {
                // A failed string fetch should not break the page.
            });
    }
};

/**
 * Read the detail fields.
 *
 * @param {HTMLElement} root The plugin's root element.
 * @return {Object} The detail values.
 */
const getDetails = (root) => {
    const details = {};
    DETAIL_FIELDS.forEach((field) => {
        const input = root.querySelector(`[data-field="${field}"]`);
        details[field] = input ? input.value.trim() : '';
    });
    return details;
};

/**
 * Show or hide a message region.
 *
 * @param {HTMLElement} root The plugin's root element.
 * @param {String} region The data-region name.
 * @param {String|null} text Text to show, or null to hide.
 */
const setMessage = (root, region, text) => {
    const el = root.querySelector(`[data-region="${region}"]`);
    if (!el) {
        return;
    }
    if (text === null) {
        el.classList.remove('sf-show');
        el.textContent = '';
    } else {
        el.classList.add('sf-show');
        // textContent, not innerHTML — this may contain a student name.
        el.textContent = text;
    }
};

/**
 * Update the progress bar.
 *
 * @param {HTMLElement} root The plugin's root element.
 * @param {Number} percent 0-100.
 * @param {String} label Text under the bar.
 */
const setProgress = (root, percent, label) => {
    const wrap = root.querySelector('[data-region="progress"]');
    const bar = wrap ? wrap.querySelector('.sf-bar') : null;
    const text = root.querySelector('[data-region="progress-label"]');

    if (wrap) {
        wrap.classList.toggle('sf-show', percent !== null);
    }
    if (bar && percent !== null) {
        bar.style.width = percent + '%';
        bar.setAttribute('aria-valuenow', percent);
    }
    if (text) {
        text.textContent = label || '';
    }
};

/**
 * Generate a document for every selected student.
 *
 * @param {HTMLElement} root The plugin's root element.
 * @return {Promise}
 */
const generateAll = async(root) => {
    setMessage(root, 'error', null);
    setMessage(root, 'success', null);

    const students = getSelectedStudents(root);
    if (students.length === 0) {
        return;
    }

    const details = getDetails(root);
    const button = root.querySelector('[data-action="generate"]');
    button.disabled = true;

    try {
        if (students.length === 1) {
            setProgress(root, 60, '');
            const blob = await buildDoc(students[0].name, details);
            setProgress(root, 100, '');
            window.saveAs(blob, safeFilename(students[0].name) + '_report.docx');
            setMessage(root, 'success',
                await getString('generatedsingle', 'report_studentfeedback', students[0].name));
        } else {
            const zip = new window.JSZip();
            for (let i = 0; i < students.length; i++) {
                setProgress(root, (i / students.length) * 90,
                    await getString('generating', 'report_studentfeedback',
                        {done: i + 1, total: students.length}));
                // eslint-disable-next-line no-await-in-loop
                const blob = await buildDoc(students[i].name, details);
                zip.file(safeFilename(students[i].name) + '_report.docx', blob);
            }
            setProgress(root, 95, await getString('zipping', 'report_studentfeedback'));
            const zipBlob = await zip.generateAsync({type: 'blob'});
            setProgress(root, 100, '');
            window.saveAs(zipBlob, 'feedback_reports.zip');
            setMessage(root, 'success',
                await getString('generatedmultiple', 'report_studentfeedback', students.length));
        }
    } catch (error) {
        setProgress(root, null, '');
        setMessage(root, 'error',
            await getString('generationfailed', 'report_studentfeedback', error.message || String(error)));
    } finally {
        button.disabled = false;
    }
};

/**
 * Make a filename safe across operating systems.
 *
 * @param {String} name The student name.
 * @return {String} A safe filename fragment.
 */
const safeFilename = (name) => {
    return name.replace(/[^a-z0-9_-]/gi, '_').substring(0, 80);
};

/**
 * Build one Word document.
 *
 * This is your original buildDoc(), with the hardcoded section headings and
 * sample text replaced by values from state.config.
 *
 * @param {String} studentName The student's name.
 * @param {Object} details Teacher, location, programme, campname.
 * @return {Promise<Blob>} The .docx file.
 */
/**
 * Build one student's document.
 *
 * Two routes. When an admin has uploaded a Word template we fill THAT, so the
 * school's logo, fonts and layout survive exactly as designed. With no template
 * we fall back to composing a plain document from the section settings.
 *
 * @param {string} studentName
 * @param {Object} details Teacher-entered fields from the report page.
 * @return {Promise<Blob>}
 */
const buildDoc = async(studentName, details) => {
    if (state.config.templateurl) {
        return buildFromTemplate(studentName, details);
    }
    return buildFromSettings(studentName, details);
};

/**
 * Fill the uploaded Word template.
 *
 * The template is fetched once and cached — refetching it for every student in
 * a class of 300 would hammer the server for nothing.
 *
 * @param {string} studentName
 * @param {Object} details
 * @return {Promise<Blob>}
 */
const buildFromTemplate = async(studentName, details) => {
    if (!templateCache) {
        const response = await fetch(state.config.templateurl);
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        templateCache = await response.arrayBuffer();
    }

    return fillTemplate(templateCache, {
        studentname: studentName,
        coursename: state.coursename,
        organisation: state.config.organisation || '',
        teachername: details.teacher || '',
        location: details.location || '',
        programme: details.programme || '',
        campname: details.campname || '',
        date: new Date().toLocaleDateString(),
    }, window.JSZip);
};

/**
 * Compose a plain document from the section settings.
 *
 * Used when no Word template has been uploaded.
 *
 * @param {string} studentName
 * @param {Object} details
 * @return {Promise<Blob>}
 */
const buildFromSettings = async(studentName, details) => {
    const D = window.docx;

    // Aptos replaced Calibri as the Word default. Word substitutes a similar
    // face on older Office installs that do not have it.
    const FONT = 'Aptos';
    const SZ = 24;        // Half-points, so 12pt.
    const SZ_TITLE = 40;  // 20pt.

    const para = (runs, opts = {}) => new D.Paragraph({children: runs, ...opts});
    const run = (text, rOpts = {}) => new D.TextRun({text, font: FONT, size: SZ, ...rOpts});

    const metaPara = (label, value) => para([
        run(label, {bold: true}),
        run(value || ''),
    ], {spacing: {after: 60}});

    const sectionHead = (title) => para([
        run(title, {bold: true}),
    ], {alignment: D.AlignmentType.CENTER, spacing: {before: 100, after: 40}});

    const writingLine = () => para([run('')], {
        border: {bottom: {style: D.BorderStyle.SINGLE, size: 4, color: 'cccccc'}},
        spacing: {after: 80},
    });

    // Starter text the teacher can keep or amend.
    //
    // 'sample' reproduces the grey italic look of the original standalone tool
    // — clearly a prompt to be replaced. 'draft' prints normal text, so it can
    // be left as-is on a finished report without looking like a leftover.
    const starterText = (text) => {
        const isSample = state.config.promptstyle === 'sample';
        return para([
            run(text, isSample ? {italics: true, color: 'b7b7b7'} : {}),
        ], {
            alignment: isSample ? D.AlignmentType.CENTER : D.AlignmentType.LEFT,
            spacing: {after: 60},
        });
    };

    const noneB = () => ({style: D.BorderStyle.NONE});

    const sigLine = (label) => new D.TableCell({
        width: {size: 4400, type: D.WidthType.DXA},
        borders: {top: noneB(), left: noneB(), right: noneB(), bottom: noneB()},
        children: [
            para([run('')], {
                border: {bottom: {style: D.BorderStyle.SINGLE, size: 6, color: '666666'}},
                spacing: {before: 300, after: 60},
            }),
            para([run(label, {size: 18})], {alignment: D.AlignmentType.CENTER}),
        ],
    });

    // Build the section blocks from admin settings rather than hardcoded calls.
    //
    // Each section is {heading, prompt}. The prompt is optional — sections
    // configured without starter text just get their blank writing lines.
    const sectionBlocks = [];
    (state.config.sections || []).forEach((section) => {
        sectionBlocks.push(sectionHead(section.heading));
        if (section.prompt) {
            sectionBlocks.push(starterText(section.prompt));
        }
        for (let i = 0; i < (state.config.writinglines || 3); i++) {
            sectionBlocks.push(writingLine());
        }
    });

    return await D.Packer.toBlob(new D.Document({
        sections: [{
            properties: {page: {margin: {top: 500, bottom: 500, left: 720, right: 720}}},
            children: [
                para([run(state.config.organisation || '', {bold: true, size: 22})]),
                para([
                    run('STUDENT FEEDBACK REPORT', {bold: true, size: SZ_TITLE}),
                ], {alignment: D.AlignmentType.CENTER}),

                metaPara('Student name: ', studentName),
                metaPara('Course: ', state.coursename),
                metaPara('Teacher name: ', details.teacher),
                metaPara('Location: ', details.location),
                metaPara('Programme: ', details.programme),
                metaPara('Camp name: ', details.campname),

                ...sectionBlocks,

                new D.Table({
                    width: {size: 100, type: D.WidthType.PERCENTAGE},
                    borders: {
                        top: noneB(), bottom: noneB(), left: noneB(),
                        right: noneB(), insideH: noneB(), insideV: noneB(),
                    },
                    rows: [new D.TableRow({
                        children: [
                            sigLine('Teacher\'s name'),
                            new D.TableCell({
                                width: {size: 500, type: D.WidthType.DXA},
                                borders: {top: noneB(), bottom: noneB(), left: noneB(), right: noneB()},
                                children: [para([run('')])],
                            }),
                            sigLine('Teacher\'s signature'),
                        ],
                    })],
                }),
            ],
        }],
    }));
};
