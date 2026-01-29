import 'bootstrap';
import jQuery from 'jquery';
import DataTable from 'datatables.net-bs5';
import 'datatables.net-buttons-bs5';
import 'datatables.net-buttons/js/buttons.html5.mjs';
import 'datatables.net-buttons/js/buttons.print.mjs';

import JSZip from 'jszip';
import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';

// Required for DataTables Export
window.JSZip = JSZip;
pdfMake.vfs = pdfFonts.pdfMake.vfs;
window.pdfMake = pdfMake;


// Expose jQuery globally for legacy scripts in Blade views
window.$ = window.jQuery = jQuery;

// Expose DataTable globally for Blade scripts
window.DataTable = DataTable;
