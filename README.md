# Student feedback reports

A Moodle report plugin that generates printable Word feedback reports for the
students enrolled in a course.

Built for schools and training providers that use Moodle to *deliver* courses
but assess offline — language camps, short courses, primary, tutoring, non-award
training. Moodle's existing reporting plugins are analytics tools: they report on
data Moodle holds. This one produces a **qualitative feedback document** for a
teacher to complete and hand to a student or parent.

## What it does

1. Reads the class list from the course enrolment — no typing names
2. Collects the details that appear on every report (teacher, location, programme)
3. Generates one formatted `.docx` per student, zipped when there's more than one

Section headings, starter text, writing lines and the organisation name are all
admin settings, so one installation serves every course without editing code.

### Word template (recommended)

Upload a `.docx` in *Site administration → Reports → Student feedback reports*
and the plugin fills **that** for each student, preserving your logo, fonts,
tables and layout exactly as designed in Word.

Write these placeholders anywhere in the document:

| Placeholder | Filled with |
|---|---|
| `{{studentname}}` | The student's full name, from the course enrolment |
| `{{coursename}}` | The Moodle course name |
| `{{teachername}}` | Teacher's entry on the report page |
| `{{location}}`, `{{programme}}`, `{{campname}}` | Report page entries |
| `{{organisation}}` | The organisation name setting |
| `{{date}}` | Date the report was generated |

The model writing lives in the template itself, so it is edited in Word — with
real formatting — rather than in a settings box. A placeholder picks up the
formatting of the text it sits in, so a bold `Student name:` label followed by a
plain `{{studentname}}` gives a plain value.

`student_report_template.docx` ships alongside this plugin as a starting point.

**Note on split placeholders.** Word often stores a single word across several
internal runs, so `{{studentname}}` can be stored as `{{stud` + `entname}}`.
`amd/src/docxtemplate.js` joins runs before matching, which is why it works on
templates that have been edited and re-saved. This is the failure mode that
breaks most hand-rolled docx templating.

### Starter text (fallback, no template uploaded)

Each section can carry editable starter text that the teacher keeps as written
or amends. Configure sections one per line:

```
Assessment of progress | Improved confidence in speaking and listening.
Participation in class | Engaged and focused in every lesson.
Action plan
```

Omit the pipe (as in the third line) for a heading with blank writing lines only.

Two styles are available:

| Style | Appearance | Use when |
|---|---|---|
| **Editable draft** (default) | Normal black text | Teachers may keep the wording on a finished report |
| **Greyed example** | Grey italic, centred — as in the original standalone tool | The text is a prompt that must always be replaced |

Grey italic left on a printed report reads as a placeholder somebody forgot to
delete, which is why *Editable draft* is the default.

## Status

**Alpha.** This is a working skeleton, not a finished product. See *Roadmap* below.

## Requirements

- Moodle 4.3 or later
- The three bundled libraries — see `js/vendor/README.md`

## Installation

1. Copy this folder to `{moodle}/report/studentfeedback`
2. Add the libraries listed in `js/vendor/README.md`
3. Visit *Site administration → Notifications* to complete the install
4. Configure defaults at *Site administration → Reports → Student feedback reports*

Teachers reach it from a course via *More → Generate feedback reports*.

## Development setup

Turn on developer debugging first — Moodle stays silent about your mistakes
otherwise:

*Site administration → Development → Debugging* → `DEVELOPER`, and tick
"Display debug messages".

### Seeing JavaScript changes

Moodle normally serves the built, minified copy of an AMD module from
`amd/build/`. While developing, add this to your `config.php` so it serves your
source from `amd/src/` directly:

```php
$CFG->cachejs = false;
```

For a release build, run `npx grunt amd` from the Moodle root.

> If you edit `amd/src/generator.js` and nothing changes in the browser, this
> setting is almost always why. It costs everyone an hour exactly once.

### Running the checks

`moodle-plugin-ci` runs the same automated checks the Marketplace runs. Use it
before every submission:

```bash
moodle-plugin-ci phplint
moodle-plugin-ci phpcs          # coding style
moodle-plugin-ci phpunit        # unit tests
moodle-plugin-ci behat          # browser tests
```

### Running the unit tests directly

```bash
vendor/bin/phpunit --filter report_studentfeedback
```

## File map

| File | What it does |
|---|---|
| `version.php` | Version and compatibility. Bump on every release |
| `index.php` | The page teachers land on |
| `lib.php` | Adds the course navigation link |
| `settings.php` | Site admin configuration |
| `db/access.php` | Permission definitions |
| `lang/en/report_studentfeedback.php` | All visible text |
| `classes/local/roster.php` | **Reads the class list from Moodle** — the core of the plugin |
| `classes/local/config.php` | Turns admin settings into report configuration |
| `classes/privacy/provider.php` | Data-protection declaration (mandatory) |
| `templates/main.mustache` | The interface |
| `amd/src/generator.js` | Document generation, ported from the standalone tool |
| `thirdpartylibs.xml` | Declares bundled libraries (mandatory) |
| `tests/` | Automated tests |

## Appearance

`styles.css` reproduces the palette of the original standalone tool: near-black
`#111` on off-white `#fbfbfa`, translucent cards with soft shadows, black
buttons, and the muted alert colours.

**Typeface: Public Sans**, bundled in `fonts/` and served from Moodle. Three
weights (400/500/700), two subsets each so accented student names render
correctly. Full notes in `fonts/readme_moodle.txt`.

Do **not** move the fonts to Google's CDN. Loading from `fonts.googleapis.com`
sends every visitor's IP to Google, which German courts have ruled a GDPR
breach — a liability any school running this plugin would inherit.

Note the interface font and the document font are separate decisions. The
generated `.docx` files still specify **Aptos**, which is correct: nearly
everyone opening a Word file has Office, and Word substitutes for anyone who
doesn't. A webfont only matters for the browser.

Three more things to know:

**Every rule is scoped under `.report-studentfeedback`.** Moodle concatenates
plugin stylesheets into one file loaded on *every* page of the site. An unscoped
rule here would restyle the whole Moodle. Keep the scope when editing.

**The gum-leaf pattern is off by default** (*Decorative background* setting).
On a school's own branded Moodle a decorative background coming from a plugin
usually reads as a theme fault rather than a design choice. The palette,
typography and card treatment carry the look without it. The SVG lives in
`pix/gumleaf.svg` and is referenced with Moodle's `[[pix:...]]` syntax rather
than inlined — the original embedded it as 126KB of URL-encoded SVG, which is
fine in a single-file page but would add that weight to every page load
site-wide from a plugin stylesheet.

## Roadmap

Ordered by what makes it sellable, not by what's most fun.

- [ ] Logo upload as an admin setting (currently omitted; was base64 in the standalone tool)
- [ ] Watermark support, ported from the standalone version
- [ ] Per-course templates so different courses can use different report formats
- [x] Starter text per section, editable by admins
- [ ] Save draft comments so teachers can type feedback in Moodle rather than in Word
- [ ] PDF output alongside `.docx`
- [ ] Behat coverage for the generate flow
- [ ] Issued-reports log — **note: this requires replacing the null privacy provider**

## Before you publish

- [ ] Choose a product name with no "Moodle" in it (trademark policy — automatic rejection)
- [ ] Public Git repository, plugin root at the repo root
- [ ] Public issue tracker enabled
- [ ] Tested on MySQL **and** PostgreSQL
- [ ] Zero notices with debugging set to DEVELOPER
- [ ] `moodle-plugin-ci` passing clean
- [ ] `readme_moodle.txt` in each vendor library folder

## Licence

GNU GPL v3 or later.

## Author

Justine Leigh Kelly
