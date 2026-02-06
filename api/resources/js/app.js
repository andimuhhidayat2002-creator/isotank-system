import 'bootstrap';
import jQuery from 'jquery';
import DataTable from 'datatables.net-bs5';

// Make jQuery global immediately
window.$ = window.jQuery = jQuery;
window.DataTable = DataTable;

// Import Buttons Core and Styles
import 'datatables.net-buttons-bs5';
import 'datatables.net-buttons';

// Import FixedColumns
import 'datatables.net-fixedcolumns-bs5';

// Import Button Modules
import 'datatables.net-buttons/js/buttons.html5.mjs';
import 'datatables.net-buttons/js/buttons.print.mjs';
import 'datatables.net-buttons/js/buttons.colVis.mjs';

// Import Export Libs
import JSZip from 'jszip';
import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';

// Assign Export Libs to Window so DataTables can find them
window.JSZip = JSZip;
pdfMake.vfs = pdfFonts.pdfMake.vfs;
window.pdfMake = pdfMake;

