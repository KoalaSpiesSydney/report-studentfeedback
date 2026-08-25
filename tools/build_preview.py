#!/usr/bin/env python3
"""Build a standalone browser preview of the report generator.

WHY THIS EXISTS
---------------
The .docx layout is the risky part of this plugin and the part you iterate on
most. Spinning up Moodle to check a font size is a poor trade, so this script
bundles the REAL amd/src code, the REAL Mustache template and the REAL language
strings into one self-contained HTML file you can double-click.

It is a preview, not a test suite. It exercises the browser half only:
generator.js, docxtemplate.js and the three vendor libraries. Everything
server-side -- roster.php, config.php, capabilities, privacy -- is faked with
the sample data at the bottom of this file, and is covered by moodle-plugin-ci
instead.

Regenerate after any change to amd/src, templates/ or lang/:

    python3 tools/build_preview.py

Output: preview/preview.html  (git-ignored; it is a build artefact)
"""
import html
import json
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
OUT = ROOT / "preview" / "preview.html"


# --------------------------------------------------------------------------
# Language strings: parse lang/en/*.php well enough for a preview.
# --------------------------------------------------------------------------
def load_strings():
    src = (ROOT / "lang/en/report_studentfeedback.php").read_text(encoding="utf-8")
    out = {}
    for m in re.finditer(r"\$string\['([^']+)'\]\s*=\s*(['\"])(.*?)(?<!\\)\2\s*;",
                         src, re.S):
        key, _, val = m.group(1), m.group(2), m.group(3)
        out[key] = val.replace("\\'", "'").replace('\\"', '"').replace("\\\\", "\\")
    return out



# --------------------------------------------------------------------------
# Moodle CSS placeholders.
# styles.css uses [[font:component|file]] and [[pix:component|image]], which
# Moodle rewrites to real URLs at runtime. A standalone file has no Moodle to
# do that, so the browser silently fails to load Public Sans and falls back to
# a system font -- which makes the preview look wrong in a way that has nothing
# to do with the design. Inline them as data: URIs instead.
# --------------------------------------------------------------------------
def resolve_moodle_css(css):
    import base64

    def font(m):
        f = ROOT / "fonts" / m.group(1)
        if not f.exists():
            print(f"  ! font not found: {m.group(1)}")
            return m.group(0)
        b64 = base64.b64encode(f.read_bytes()).decode()
        print(f"  inlined font: {m.group(1)} ({len(b64)//1024} KB base64)")
        return f"data:font/woff2;base64,{b64}"

    def pix(m):
        for ext in (".svg", ".png"):
            f = ROOT / "pix" / (m.group(1) + ext)
            if f.exists():
                b64 = base64.b64encode(f.read_bytes()).decode()
                mime = "image/svg+xml" if ext == ".svg" else "image/png"
                print(f"  inlined pix: {m.group(1)}{ext} ({len(b64)//1024} KB base64)")
                return f"data:{mime};base64,{b64}"
        print(f"  ! pix not found: {m.group(1)}")
        return m.group(0)

    css = re.sub(r"\[\[font:report_studentfeedback\|([^\]|]+)\]\]", font, css)
    css = re.sub(r"\[\[pix:report_studentfeedback\|([^\]|]+)\]\]", pix, css)
    return css


# --------------------------------------------------------------------------
# Mustache: the subset templates/main.mustache actually uses.
# Rendered here at build time, so the preview markup is what Moodle would emit.
# --------------------------------------------------------------------------
TOKEN = re.compile(r"\{\{([#^/!&]?)\s*(.*?)\s*\}\}|\{\{\{\s*(.*?)\s*\}\}\}", re.S)


def render(tpl, ctx, strings):
    out, i = [], 0
    while i < len(tpl):
        m = TOKEN.search(tpl, i)
        if not m:
            out.append(tpl[i:])
            break
        out.append(tpl[i:m.start()])
        sigil, name, raw = m.group(1), m.group(2), m.group(3)

        if raw is not None:                                   # {{{ raw }}}
            out.append(str(resolve(ctx, raw) or ""))
            i = m.end()
            continue
        if sigil == "!":                                      # comment
            i = m.end()
            continue
        if sigil in ("#", "^"):
            close = find_close(tpl, m.end(), name)
            inner = tpl[m.end():close[0]]
            if name == "str":                                 # {{#str}}key, comp{{/str}}
                key = inner.split(",")[0].strip()
                out.append(html.escape(strings.get(key, f"[[{key}]]")))
            else:
                val = resolve(ctx, name)
                if sigil == "^":
                    if not val:
                        out.append(render(inner, ctx, strings))
                elif isinstance(val, list):
                    for item in val:
                        scope = dict(ctx)
                        scope.update(item if isinstance(item, dict) else {".": item})
                        out.append(render(inner, scope, strings))
                elif val:
                    scope = dict(ctx)
                    if isinstance(val, dict):
                        scope.update(val)
                    out.append(render(inner, scope, strings))
            i = close[1]
            continue

        out.append(html.escape(str(resolve(ctx, name) or "")))  # {{ var }}
        i = m.end()
    return "".join(out)


def find_close(tpl, start, name):
    depth, i = 1, start
    while i < len(tpl):
        m = TOKEN.search(tpl, i)
        if not m:
            break
        if m.group(1) in ("#", "^") and m.group(2) == name:
            depth += 1
        elif m.group(1) == "/" and m.group(2) == name:
            depth -= 1
            if depth == 0:
                return m.start(), m.end()
        i = m.end()
    raise SystemExit(f"Unclosed mustache section: {name}")


def resolve(ctx, path):
    if path == ".":
        return ctx.get(".")
    cur = ctx
    for part in path.split("."):
        if not isinstance(cur, dict) or part not in cur:
            return None
        cur = cur[part]
    return cur


# --------------------------------------------------------------------------
# ES module -> plain script. Mechanical, so the preview cannot drift from source.
# --------------------------------------------------------------------------
def demodule(src, name):
    dropped = len(re.findall(r"^\s*import\s.*?;\s*$", src, re.M))
    src = re.sub(r"^\s*import\s.*?;\s*$", "", src, flags=re.M)
    src = re.sub(r"^export\s+", "", src, flags=re.M)
    print(f"  {name}: stripped {dropped} import(s)")
    return src


def main():
    strings = load_strings()
    print(f"Loaded {len(strings)} language strings")

    students = [
        {"id": 101, "fullname": "Amara Okonkwo"},
        {"id": 102, "fullname": "Bao Nguyen"},
        {"id": 103, "fullname": "Charlotte Reid"},
        {"id": 104, "fullname": "Dmitri Volkov"},
        {"id": 105, "fullname": "Eleni Papadopoulos"},
    ]
    ctx = {
        "students": students,
        "groups": [],
        "hasgroups": False,
        "courseid": 2,
        "currentgroup": 0,
        "formaction": "#",
        "sesskey": "previewsesskey",
        "organisation": "Bluesky Education",
        "patterned": False,
    }

    markup = render((ROOT / "templates/main.mustache").read_text(encoding="utf-8"),
                    ctx, strings)
    print(f"Rendered template: {len(markup)} chars")

    css = resolve_moodle_css((ROOT / "styles.css").read_text(encoding="utf-8"))

    vendor = {
        "jszip": (ROOT / "js/vendor/jszip/jszip.min.js").read_text(encoding="utf-8"),
        "docx": (ROOT / "js/vendor/docx/index.iife.js").read_text(encoding="utf-8"),
        "filesaver": (ROOT / "js/vendor/filesaver/FileSaver.min.js").read_text(encoding="utf-8"),
    }
    for k, v in vendor.items():
        print(f"  vendor/{k}: {len(v)//1024} KB")

    print("De-modularising:")
    docxtemplate = demodule((ROOT / "amd/src/docxtemplate.js").read_text(encoding="utf-8"),
                            "docxtemplate.js")
    generator = demodule((ROOT / "amd/src/generator.js").read_text(encoding="utf-8"),
                         "generator.js")

    config = {
        "templateurl": "",
        "patterned": False,
        "organisation": "Bluesky Education",
        "sections": [
            {"heading": "Progress this term", "prompt": "What has this student achieved?"},
            {"heading": "Areas to work on", "prompt": "What should they focus on next?"},
            {"heading": "Attendance and participation", "prompt": ""},
        ],
        "writinglines": 4,
        "promptstyle": "italic",
    }
    data = {"students": students, "config": config,
            "coursename": "Certificate III in Individual Support", "contextid": 25}

    OUT.parent.mkdir(exist_ok=True)
    OUT.write_text(PAGE.format(
        css=css,
        markup=markup,
        strings=json.dumps(strings),
        data=json.dumps(data),
        jszip=vendor["jszip"], docx=vendor["docx"], filesaver=vendor["filesaver"],
        docxtemplate=docxtemplate, generator=generator,
    ), encoding="utf-8")
    print(f"\nWrote {OUT.relative_to(ROOT)}  ({OUT.stat().st_size//1024} KB)")
    print("Open it in a browser. No server, no Moodle.")


PAGE = """<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>report_studentfeedback - local preview</title>
<style>
{css}

/* ----------------------------------------------------------------------
   Harness chrome. Not part of the plugin -- but it sits directly above the
   plugin UI, so it uses the same typeface, the same 4px corners and the same
   two-layer shadows. Anything that looked "off" here would read as the
   plugin's fault.
   ---------------------------------------------------------------------- */
:root {{
  --h-ink: #1a1a1a;
  --h-muted: #767676;
  --h-faint: #8a8a8a;
  --h-line: #e2e2e2;
  --h-bar: #14181a;
  --h-field: #fcfcfc;
}}

html {{ -webkit-text-size-adjust: 100%; }}

body {{
  margin: 0;
  background: #f4f5f4;
  color: var(--h-ink);
  font-family: "Public Sans", system-ui, -apple-system, "Segoe UI",
               "Helvetica Neue", Arial, sans-serif;
  font-size: 15px;
  line-height: 1.5;
}}

.h-bar {{ background: var(--h-bar); color: #fff; padding: 16px 28px; }}

.h-bar h1 {{
  margin: 0 0 5px;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}}

.h-bar p {{
  margin: 0;
  font-size: 0.82rem;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.62);
  max-width: 74ch;
}}

.h-bar code {{
  font-family: ui-monospace, Menlo, Consolas, monospace;
  font-size: 0.9em;
  color: rgba(255, 255, 255, 0.86);
}}

.h-panel {{
  background: #fff;
  border-bottom: 1px solid var(--h-line);
  padding: 20px 28px 22px;
}}

/* Auto-fitting columns, so nothing is jammed against its neighbour at any
   width. The sections field spans two because it holds the most text. */
/* Two columns: a stacked left rail of short controls, and the sections box
   filling the whole right half. The old auto-fit row put five controls of
   very different widths on one line, which is what made it feel cramped. */
.h-grid {{
  display: grid;
  grid-template-columns: minmax(240px, 340px) 1fr;
  gap: 20px 32px;
  align-items: start;
  max-width: 1240px;
}}

.h-stack {{ display: flex; flex-direction: column; gap: 16px; }}

.h-sections {{ display: flex; flex-direction: column; height: 100%; }}

@media (max-width: 760px) {{
  .h-grid {{ grid-template-columns: 1fr; }}
}}

.h-field label {{
  display: block;
  margin-bottom: 6px;
  font-size: 0.66rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--h-muted);
}}

/* One rule for every control, so the row reads as a set. */
.h-field input[type="text"],
.h-field input[type="number"],
.h-field select,
.h-field textarea {{
  width: 100%;
  box-sizing: border-box;
  font-family: inherit;
  font-size: 0.88rem;
  line-height: 1.45;
  color: var(--h-ink);
  background: var(--h-field);
  border: 1.5px solid var(--sf-border, #dcdcdc);
  border-radius: 4px;
  padding: 8px 11px;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}}

.h-field textarea {{
  min-height: 190px;
  resize: vertical;
  line-height: 1.65;
}}

.h-field select {{
  appearance: none;
  background-image: linear-gradient(45deg, transparent 50%, #767676 50%),
                    linear-gradient(135deg, #767676 50%, transparent 50%);
  background-position: calc(100% - 15px) 52%, calc(100% - 10px) 52%;
  background-size: 5px 5px, 5px 5px;
  background-repeat: no-repeat;
  padding-right: 30px;
}}

.h-field input:focus,
.h-field select:focus,
.h-field textarea:focus {{
  outline: none;
  border-color: var(--sf-accent, #4a7c6a);
  box-shadow: 0 0 0 3px var(--sf-accent-ring, rgba(74, 124, 106, 0.28));
  background: #fff;
}}

/* File inputs cannot be styled, so the native control is hidden behind a
   label that matches the other buttons. */
.h-file input {{ position: absolute; width: 1px; height: 1px;
                 opacity: 0; pointer-events: none; }}

.h-btn {{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-family: inherit;
  font-size: 0.78rem;
  font-weight: 600;
  letter-spacing: 0.01em;
  padding: 9px 16px;
  border-radius: 4px;
  cursor: pointer;
  border: 1px solid var(--sf-border, #dcdcdc);
  background: #fff;
  color: var(--h-muted);
  box-shadow: 0 1px 2px rgba(17, 24, 21, 0.09), 0 2px 6px rgba(17, 24, 21, 0.06);
  transition: box-shadow 0.15s ease, transform 0.12s ease,
              border-color 0.15s ease, color 0.15s ease;
}}

.h-btn:hover {{
  color: var(--sf-accent-hover, #3b6555);
  border-color: var(--sf-accent, #4a7c6a);
  background: var(--sf-accent-tint, #eef4f1);
  box-shadow: 0 2px 4px rgba(17, 24, 21, 0.12), 0 8px 18px rgba(17, 24, 21, 0.12);
  transform: translateY(-1px);
}}

.h-btn:active {{ transform: translateY(0); }}

.h-btn-primary {{
  background: var(--sf-accent, #4a7c6a);
  border-color: var(--sf-accent, #4a7c6a);
  color: #fff;
}}

.h-btn-primary:hover {{
  background: var(--sf-accent-hover, #3b6555);
  border-color: var(--sf-accent-hover, #3b6555);
  color: #fff;
}}

.h-actions {{
  display: flex;
  align-items: center;
  gap: 14px;
  margin-top: 18px;
  padding-top: 16px;
  border-top: 1px solid var(--h-line);
}}

.h-note {{ font-size: 0.76rem; color: var(--h-faint); }}

@media (prefers-reduced-motion: reduce) {{
  .h-btn {{ transition: none; }}
  .h-btn:hover {{ transform: none; }}
}}

.h-stage {{ padding: 26px 20px 70px; }}
</style>
</head>
<body>

<div class="h-bar">
  <h1>Local preview &mdash; no Moodle</h1>
  <p>Runs the real <code>generator.js</code> and <code>docxtemplate.js</code> against sample
     students. Change the settings below and apply to re-run <code>init()</code>, so you can
     check the .docx the way an administrator's configuration would change it.</p>
</div>

<div class="h-panel">
  <div class="h-grid">
    <div class="h-stack">
      <div class="h-field">
        <label for="p-org">Organisation</label>
        <input id="p-org" type="text" value="Bluesky Education">
      </div>
      <div class="h-field">
        <label for="p-style">Font style</label>
        <select id="p-style">
          <option value="italic">Italic</option>
          <option value="plain">Plain</option>
          <option value="hidden">Hidden</option>
        </select>
      </div>
      <div class="h-field h-file">
        <label for="p-tpl">Word template</label>
        <label class="h-btn" for="p-tpl">Choose .docx&hellip;</label>
        <input id="p-tpl" type="file" accept=".docx">
        <div class="h-note" id="p-tplname" style="margin-top:7px">Optional &mdash; none chosen</div>
      </div>
    </div>

    <div class="h-field h-sections">
      <label for="p-sections">Sections &mdash; one per line, Heading | Prompt</label>
      <textarea id="p-sections">Progress this term | What has this student achieved?
Areas to work on | What should they focus on next?
Attendance and participation |</textarea>
      <div class="h-note" style="margin-top:8px">Each section gets four blank
        writing lines. Leave the part after | empty for no starter text.</div>
    </div>
  </div>

  <div class="h-actions">
    <button type="button" class="h-btn h-btn-primary" id="p-apply">Apply settings</button>
    <span class="h-note" id="p-status">Showing 5 sample students.</span>
  </div>
</div>

<div class="h-stage">{markup}</div>

<script>{jszip}</script>
<script>{docx}</script>
<script>{filesaver}</script>

<script>
// ---- Moodle stubs -------------------------------------------------------
// generator.js needs exactly two things from Moodle. Both are reimplemented
// here against the real language file, so wording matches production.
const MOODLE_STRINGS = {strings};
const get_string = async (key, component, a) => {{
    let s = MOODLE_STRINGS[key] || '[[' + key + ']]';
    if (a === undefined) {{ return s; }}
    if (typeof a === 'object') {{
        for (const k in a) {{ s = s.split('{{$a->' + k + '}}').join(a[k]); }}
        return s;
    }}
    return s.split('{{$a}}').join(a);
}};
const getString = get_string;
const Notification = {{
    exception: (e) => {{
        console.error('[Notification.exception]', e);
        alert('Exception: ' + (e && e.message ? e.message : e));
    }}
}};
</script>

<script>
// ---- Plugin source, imports stripped by tools/build_preview.py ----------
{docxtemplate}
{generator}
</script>

<script>
const BASE = {data};

const readPanel = () => {{
    const sections = document.getElementById('p-sections').value
        .split('\\n').map(l => l.trim()).filter(Boolean)
        .map(l => {{ const p = l.split('|');
            return {{heading: (p[0] || '').trim(), prompt: (p[1] || '').trim()}}; }});
    return Object.assign({{}}, BASE, {{config: Object.assign({{}}, BASE.config, {{
        organisation: document.getElementById('p-org').value,
        // No longer a control: four is the working default and the number was
        // never the interesting variable when checking a layout.
        writinglines: 4,
        promptstyle: document.getElementById('p-style').value,
        sections: sections,
        templateurl: window.__previewTemplateUrl || ''
    }})}});
}};

document.getElementById('p-tpl').addEventListener('change', (e) => {{
    const f = e.target.files[0];
    const note = document.getElementById('p-tplname');
    if (!f) {{
        window.__previewTemplateUrl = '';
        note.textContent = 'Optional \\u2014 none chosen';
        return;
    }}
    window.__previewTemplateUrl = URL.createObjectURL(f);
    note.textContent = f.name;
}});

document.getElementById('p-apply').addEventListener('click', () => {{
    const cfg = readPanel();
    init(cfg);
    document.getElementById('p-status').textContent =
        'Applied \\u2014 ' + cfg.config.sections.length + ' section(s).';
}});

init(readPanel());
</script>
</body>
</html>
"""

if __name__ == "__main__":
    sys.exit(main())
