import 'bootstrap';
import jQuery from 'jquery';
import DataTable from 'datatables.net-bs5';

// Import Core Buttons
import 'datatables.net-buttons';
import 'datatables.net-buttons-bs5';

// Import Button Features
import 'datatables.net-buttons/js/buttons.html5.mjs';
import 'datatables.net-buttons/js/buttons.print.mjs';
import 'datatables.net-buttons/js/buttons.colVis.mjs';

// Import Export Dependencies
import JSZip from 'jszip';
import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';

// Global Assignments for Legacy/Blade Compatibility
window.$ = window.jQuery = jQuery;
window.JSZip = JSZip;
pdfMake.vfs = pdfFonts.pdfMake.vfs;
window.pdfMake = pdfMake;
window.DataTable = DataTable;
