Public Sans
===========

Description
-----------
Public Sans is the typeface used by the plugin interface. It is a neutral,
open-source sans-serif originally commissioned for the US Web Design System,
chosen here for its clear disambiguation of similar characters (capital I,
lowercase l, digit 1) — which matters on a page listing students by name.

It replaces Aptos, which was used during development. Aptos ships with Microsoft
Office and is not a web font, so it renders only for people who happen to have
Office installed. Public Sans is bundled with the plugin and therefore looks
identical on every browser and device.

Note that the *generated Word documents* still specify Aptos. That is deliberate
and is not affected by this: nearly everyone opening a .docx has Office, and
Word substitutes sensibly for anyone who does not.


Source
------
https://github.com/uswds/public-sans
Obtained via the @fontsource/public-sans npm package (version 5.x), which
repackages the Google Fonts builds.


Version
-------
2.001


Licence
-------
SIL Open Font License, Version 1.1.
Full text in LICENSE_publicsans.txt in this directory.

The OFL permits bundling and redistribution, including inside GPL software,
provided the licence text travels with the font files. It is listed as a free
licence by the FSF.


Files included
--------------
Only the three weights the interface actually uses, in the two subsets that
cover the languages Moodle sites in scope are likely to need. Italics and the
other six weights were deliberately excluded to keep the plugin small.

  publicsans_regular.woff2       400, latin
  publicsans_regular_ext.woff2   400, latin-ext
  publicsans_medium.woff2        500, latin
  publicsans_medium_ext.woff2    500, latin-ext
  publicsans_bold.woff2          700, latin
  publicsans_bold_ext.woff2      700, latin-ext

woff2 only. Every browser Moodle 4.3+ supports handles it, and it is roughly
30% smaller than woff.


Changes made to the upstream files
----------------------------------
None. The .woff2 files are byte-identical to those in the npm package.

They were RENAMED, because Moodle's [[font:...]] placeholder requires font
filenames to use only lowercase alphanumeric characters and underscores —
hyphens are not permitted. Original names were of the form
public-sans-latin-400-normal.woff2.


Important: do not switch to a CDN
---------------------------------
These files are served from Moodle on purpose. Loading them from
fonts.googleapis.com instead would transmit every visitor's IP address to
Google, which German courts have found to breach the GDPR. Any school running
this plugin would inherit that liability. Self-hosting also removes an external
dependency that can fail, be blocked, or slow the page down.


Upgrading
---------
  npm install @fontsource/public-sans
  cp node_modules/@fontsource/public-sans/files/public-sans-latin-400-normal.woff2 \
     fonts/publicsans_regular.woff2
  (and so on for the other five files, following the table above)

Then update the version number here and in thirdpartylibs.xml, and re-run
`grunt ignorefiles`.

If the upstream unicode-range values change, copy the new ones into the
@font-face block in styles.css.
