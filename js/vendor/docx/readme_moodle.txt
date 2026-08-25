docx
====

Version : 9.0.2
Licence : MIT (see LICENSE in this folder)
Source  : https://github.com/dolanmiu/docx

Obtained
--------
  npm install docx@9.0.2
  cp node_modules/docx/build/index.iife.js js/vendor/docx/

Copied unmodified. This is the IIFE build, not the CommonJS or ESM build --
it is the one that exposes a browser global, which is what a non-AMD Moodle
script needs.

Changes made to upstream files: none. Byte-identical to the npm distribution.

LOAD ORDER: JSZip must be defined before this library's browser build runs.
index.php loads them in the correct order -- do not reorder them.

See js/vendor/readme_moodle.txt for the full notes covering all three
libraries, licence compatibility and the upgrade procedure.
