Third-party JavaScript libraries
================================

All three run in the browser and are loaded as plain (non-AMD) scripts by
index.php. They expose the globals `JSZip`, `saveAs` and `docx`.

LOAD ORDER MATTERS: JSZip must be defined before docx's browser build runs.
index.php loads them in the correct order — do not reorder them.


docx  (js/vendor/docx/)
----------------------
Version : 9.0.2
Licence : MIT (see docx/LICENSE)
Source  : https://github.com/dolanmiu/docx
Obtained: npm install docx@9.0.2, then copied build/index.iife.js unmodified.

Note this is the IIFE build, not the CommonJS or ESM build. It is the one that
exposes a browser global, which is what a non-AMD Moodle script needs.


JSZip  (js/vendor/jszip/)
-------------------------
Version : 3.10.1
Licence : MIT or GPLv3, dual (see jszip/LICENSE.markdown)
Source  : https://github.com/Stuk/jszip
Obtained: npm install jszip@3.10.1, then copied dist/jszip.min.js unmodified.

Used both to build the ZIP of reports for a whole class, and by
amd/src/docxtemplate.js to open and rewrite the uploaded .docx template
(a .docx is itself a ZIP).


FileSaver.js  (js/vendor/filesaver/)
------------------------------------
Version : 2.0.5
Licence : MIT (see filesaver/LICENSE.md)
Source  : https://github.com/eligrey/FileSaver.js
Obtained: npm install file-saver@2.0.5, then copied dist/FileSaver.min.js
          unmodified.


Changes made to upstream files
------------------------------
None. All three files are byte-identical to the npm distributions.


Licence compatibility
---------------------
MIT and the JSZip dual MIT/GPLv3 are both GPLv3-compatible, which is what makes
them legal to redistribute inside this GPL plugin. Declared in
thirdpartylibs.xml. Check any library you add against
https://www.gnu.org/licenses/license-list.en.html before bundling it.


Upgrading
---------
  npm install docx@<version> jszip@<version> file-saver@<version>
  cp node_modules/docx/build/index.iife.js        js/vendor/docx/
  cp node_modules/jszip/dist/jszip.min.js         js/vendor/jszip/
  cp node_modules/file-saver/dist/FileSaver.min.js js/vendor/filesaver/

Then update the versions here and in thirdpartylibs.xml, re-run
`grunt ignorefiles`, and regenerate a report to confirm nothing broke.
