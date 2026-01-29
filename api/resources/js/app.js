import 'bootstrap';
import jQuery from 'jquery';
import DataTable from 'datatables.net-bs5';
import 'datatables.net-buttons-bs5';
import 'datatables.net-buttons/js/buttons.html5.mjs';
import 'datatables.net-buttons/js/buttons.print.mjs';

// Expose jQuery globally for legacy scripts in Blade views
window.$ = window.jQuery = jQuery;

// Expose DataTable globally for Blade scripts
window.DataTable = DataTable;
