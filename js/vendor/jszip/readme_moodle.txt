JSZip
=====

Version : 3.10.1
Licence : MIT or GPLv3, dual (see LICENSE.markdown in this folder)
Source  : https://github.com/Stuk/jszip

Obtained
--------
  npm install jszip@3.10.1
  cp node_modules/jszip/dist/jszip.min.js js/vendor/jszip/

Copied unmodified.

Used both to build the ZIP of reports for a whole class, and by
amd/src/docxtemplate.js to open and rewrite the uploaded .docx template
(a .docx is itself a ZIP).

Changes made to upstream files: none. Byte-identical to the npm distribution.

See js/vendor/readme_moodle.txt for the full notes covering all three
libraries, licence compatibility and the upgrade procedure.
