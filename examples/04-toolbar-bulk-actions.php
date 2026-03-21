<?php
/**
 * Example 04 — Toolbar with Bulk Actions
 *
 * Shows how to add toolbar buttons above/below the table for
 * creating new records, bulk-deleting selected rows, and exporting
 * all visible data.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Italix\DataSets\DataSet;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

$ds = new DataSet($usersTable);

$ds->columns(['name', 'email', 'role', 'created_at']);
$ds->ajax_url('/api/users');

// Enable row selection with checkboxes (required for "selected" scope buttons)
$ds->selectable(true);

// ---- Toolbar ---------------------------------------------------------------
// Toolbar buttons have a "scope" that determines what data they receive:
//   'none'     — callback receives no data (e.g. "Add New")
//   'selected' — callback receives an array of selected rows
//   'all'      — callback receives all rows currently in the table

$ds->toolbar()
    ->position('top')          // 'top', 'bottom', or 'both'
    ->css_class('my-toolbar');

// "Add New" — no row data needed
$ds->toolbar()
    ->button('add', 'Add New', 'none')
    ->css_class('btn btn-success')
    ->icon('fa fa-plus');

// "Delete Selected" — receives the selected rows
$ds->toolbar()
    ->button('delete_selected', 'Delete Selected', 'selected')
    ->css_class('btn btn-danger')
    ->icon('fa fa-trash')
    ->confirm('Delete all selected users?');

// "Export CSV" — receives all visible rows
$ds->toolbar()
    ->button('export', 'Export CSV', 'all')
    ->css_class('btn btn-outline-secondary')
    ->icon('fa fa-download');

// Map toolbar actions to JS callbacks
$ds->on('add', 'onAddUser');
$ds->on('delete_selected', 'onDeleteSelected');
$ds->on('export', 'onExportCsv');

// ---- Render ----------------------------------------------------------------

$driver = new TabulatorDriver();
$script = $driver->render_script($ds, '#users-table', 'usersTable');

?>
<div id="users-table"></div>

<script>
// Toolbar callbacks

function onAddUser() {
    // scope='none': no arguments
    window.location.href = '/users/create';
}

function onDeleteSelected(selectedRows) {
    // scope='selected': receives array of row data objects
    var ids = selectedRows.map(function(r) { return r.id; });
    fetch('/api/users/bulk-delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: ids })
    }).then(function() {
        usersTable.setData();  // Reload the table
    });
}

function onExportCsv(allRows) {
    // scope='all': receives array of all row data objects
    var csv = 'name,email,role\n';
    allRows.forEach(function(row) {
        csv += row.name + ',' + row.email + ',' + row.role + '\n';
    });

    var blob = new Blob([csv], { type: 'text/csv' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'users.csv';
    a.click();
}

<?= $script ?>
</script>
