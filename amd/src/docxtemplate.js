/**
 * Fills placeholders in a Word (.docx) template, preserving all formatting.
 *
 * A .docx is a ZIP containing XML. This opens word/document.xml, substitutes
 * {{placeholder}} tokens, and writes the result back — so the logo, fonts,
 * tables, headers and layout survive exactly as designed in Word.
 *
 * THE HARD PART
 * -------------
 * Word splits text across <w:r> runs at arbitrary points — spell-check state,
 * a stray edit, revision history. So "{{studentname}}" is very often stored as
 * several fragments across several runs:
 *
 *     <w:r><w:t>{{stud</w:t></w:r><w:r><w:t>entname}}</w:t></w:r>
 *
 * A naive string replace across the whole XML misses these, which is why
 * hand-rolled docx templating usually "works on my file" and fails on the
 * customer's. This joins the runs in each paragraph, finds placeholders in the
 * joined text, then writes the value back into the run where the placeholder
 * STARTED and deletes the leftover characters from the runs it spilled into.
 *
 * Writing to the starting run means the value inherits that run's formatting,
 * which is what you want: a bold "Student name:" label followed by a plain
 * placeholder gives you a plain value.
 *
 * @module report_studentfeedback/docxtemplate
 * @copyright 2026 Justine Leigh Kelly
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Matches {{ name }} with optional inner whitespace. */
const TOKEN = /\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g;

/**
 * Escape text for inclusion in XML.
 *
 * Without this, a student called "Tom & Jerry" or a course named
 * "<Advanced>" produces a corrupt document that Word refuses to open.
 *
 * @param {string} text
 * @return {string}
 */
function escapeXml(text) {
    return String(text === null || text === undefined ? '' : text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

/**
 * Replace the text content of a single <w:t> element inside a run's XML.
 *
 * Adds xml:space="preserve" so leading and trailing spaces survive — without
 * it Word silently collapses them and your labels run into your values.
 *
 * @param {string} runXml The full <w:r>…</w:r> XML.
 * @param {string} newText Replacement text, already XML-escaped.
 * @return {string}
 */
function setRunText(runXml, newText) {
    return runXml.replace(
        /(<w:t(?:\s[^>]*)?>)([\s\S]*?)(<\/w:t>)/,
        () => '<w:t xml:space="preserve">' + newText + '</w:t>'
    );
}

/**
 * Read the text out of a run's <w:t> element.
 *
 * @param {string} runXml
 * @return {string|null} Null when the run holds no text element at all.
 */
function getRunText(runXml) {
    const m = runXml.match(/<w:t(?:\s[^>]*)?>([\s\S]*?)<\/w:t>/);
    if (!m) {
        return null;
    }
    // Unescape so joined text matches what the user typed in Word.
    return m[1]
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&quot;/g, '"')
        .replace(/&apos;/g, "'")
        .replace(/&amp;/g, '&');
}

/**
 * Substitute placeholders within one paragraph's XML.
 *
 * @param {string} paragraphXml
 * @param {Object} values Placeholder name to replacement text.
 * @return {string}
 */
function fillParagraph(paragraphXml, values) {
    // Split the paragraph into runs, keeping everything between them intact.
    const runPattern = /<w:r(?:\s[^>]*)?>[\s\S]*?<\/w:r>/g;
    const runs = [];
    let match;
    while ((match = runPattern.exec(paragraphXml)) !== null) {
        runs.push({xml: match[0], start: match.index, end: match.index + match[0].length});
    }
    if (!runs.length) {
        return paragraphXml;
    }

    // Build the joined text and remember which run each character came from.
    let joined = '';
    const owner = [];   // owner[i] = index into runs for character i
    runs.forEach((run, index) => {
        const text = getRunText(run.xml);
        if (text === null) {
            return;
        }
        for (let i = 0; i < text.length; i++) {
            owner.push(index);
        }
        joined += text;
    });

    if (joined.indexOf('{{') === -1) {
        return paragraphXml;   // Nothing to do — leave the XML untouched.
    }

    // Find every placeholder in the joined text.
    TOKEN.lastIndex = 0;
    const hits = [];
    while ((match = TOKEN.exec(joined)) !== null) {
        hits.push({
            from: match.index,
            to: match.index + match[0].length,
            name: match[1],
        });
    }
    if (!hits.length) {
        return paragraphXml;
    }

    // Work out the new text for each run.
    const newTexts = runs.map(run => getRunText(run.xml));
    // Track which characters are consumed by a placeholder.
    const consumed = new Array(joined.length).fill(false);
    // Where each run's text begins within the joined string.
    const runOffset = {};
    owner.forEach((runIndex, charIndex) => {
        if (runOffset[runIndex] === undefined) {
            runOffset[runIndex] = charIndex;
        }
    });

    const insertions = {};   // runIndex -> [{at, text}]

    hits.forEach(hit => {
        const startRun = owner[hit.from];
        // Replacement lands in the run where the placeholder opened, so it
        // picks up that run's formatting.
        const value = Object.prototype.hasOwnProperty.call(values, hit.name)
            ? values[hit.name]
            : '';   // Unknown placeholders resolve to empty, never left visible.
        if (!insertions[startRun]) {
            insertions[startRun] = [];
        }
        insertions[startRun].push({at: hit.from - runOffset[startRun], text: value});
        for (let i = hit.from; i < hit.to; i++) {
            consumed[i] = true;
        }
    });

    // Rebuild each run's text: drop consumed characters, splice in values.
    runs.forEach((run, index) => {
        if (newTexts[index] === null) {
            return;
        }
        const offset = runOffset[index];
        if (offset === undefined) {
            return;
        }
        let rebuilt = '';
        for (let i = 0; i < newTexts[index].length; i++) {
            const globalIndex = offset + i;
            const pending = (insertions[index] || []).filter(ins => ins.at === i);
            pending.forEach(ins => {
                rebuilt += ins.text;
            });
            if (!consumed[globalIndex]) {
                rebuilt += newTexts[index][i];
            }
        }
        // Placeholder sitting at the very end of the run.
        (insertions[index] || [])
            .filter(ins => ins.at >= newTexts[index].length)
            .forEach(ins => {
                rebuilt += ins.text;
            });
        newTexts[index] = rebuilt;
    });

    // Splice the rewritten runs back into the paragraph, last first so the
    // earlier offsets stay valid.
    let result = paragraphXml;
    for (let index = runs.length - 1; index >= 0; index--) {
        if (newTexts[index] === null) {
            continue;
        }
        const rewritten = setRunText(runs[index].xml, escapeXml(newTexts[index]));
        result = result.slice(0, runs[index].start) + rewritten + result.slice(runs[index].end);
    }
    return result;
}

/**
 * Fill a .docx template.
 *
 * @param {ArrayBuffer|Blob|Uint8Array} templateData The template file.
 * @param {Object} values Placeholder name to replacement text.
 * @param {Object} JSZipLib The JSZip constructor.
 * @return {Promise<Blob>} The filled document.
 */
export async function fillTemplate(templateData, values, JSZipLib) {
    const zip = await JSZipLib.loadAsync(templateData);

    // Headers and footers can carry placeholders too — a school logo block
    // with the course name in it is a common case.
    const targets = Object.keys(zip.files).filter(name =>
        name === 'word/document.xml' ||
        /^word\/(header|footer)\d*\.xml$/.test(name)
    );

    for (const name of targets) {
        const xml = await zip.file(name).async('string');
        const filled = xml.replace(
            /<w:p(?:\s[^>]*)?>[\s\S]*?<\/w:p>/g,
            paragraph => fillParagraph(paragraph, values)
        );
        zip.file(name, filled);
    }

    return zip.generateAsync({
        type: 'blob',
        mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    });
}

/**
 * List the placeholders a template actually contains.
 *
 * Used to warn an admin who uploads a template with a typo — {{studentnme}}
 * would otherwise fail silently on every report.
 *
 * @param {ArrayBuffer|Blob|Uint8Array} templateData
 * @param {Object} JSZipLib
 * @return {Promise<string[]>} Unique placeholder names, sorted.
 */
export async function listPlaceholders(templateData, JSZipLib) {
    const zip = await JSZipLib.loadAsync(templateData);
    const found = new Set();

    const targets = Object.keys(zip.files).filter(name =>
        name === 'word/document.xml' ||
        /^word\/(header|footer)\d*\.xml$/.test(name)
    );

    for (const name of targets) {
        const xml = await zip.file(name).async('string');
        xml.replace(/<w:p(?:\s[^>]*)?>[\s\S]*?<\/w:p>/g, paragraph => {
            let joined = '';
            const runPattern = /<w:r(?:\s[^>]*)?>[\s\S]*?<\/w:r>/g;
            let m;
            while ((m = runPattern.exec(paragraph)) !== null) {
                const t = getRunText(m[0]);
                if (t !== null) {
                    joined += t;
                }
            }
            TOKEN.lastIndex = 0;
            let hit;
            while ((hit = TOKEN.exec(joined)) !== null) {
                found.add(hit[1]);
            }
            return paragraph;
        });
    }

    return Array.from(found).sort();
}
