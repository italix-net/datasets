<?php
/**
 * Example 11 — Complete CRUD Table
 *
 * Puts it all together: a full-featured users management table with
 * search, filtering, sorting, action buttons, toolbar, selection,
 * and all event callbacks wired up.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Italix\DataSets\DataSet;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

// ============================================================================
// Build the DataSet
// ============================================================================

$ds = new DataSet($usersTable);

// -- Columns -----------------------------------------------------------------

$ds->columns(['name', 'email', 'role', 'active', 'created_at']);

$ds->column('name')
    ->label('Full Name')
    ->sortable(true)
    ->searchable(true)
    ->width('200px');

$ds->column('email')
    ->sortable(true)
    ->searchable(true);

$ds->column('role')
    ->sortable(true)
    ->header_filter('select')
    ->h_align('center');

$ds->column('active')
    ->label('Status')
    ->formatter('tickCross')
    ->h_align('center')
    ->width('80px');

$ds->column('created_at')
    ->label('Registered')
    ->sortable(true)
    ->formatter('datetime', ['outputFormat' => 'DD/MM/YYYY']);

// -- Data source -------------------------------------------------------------

$ds->ajax_url('/api/users')
    ->ajax_method('GET')
    ->ajax_params(['tenant' => 'acme']);

// -- Display -----------------------------------------------------------------

$ds->id('users-crud')
    ->height('600px')
    ->layout('fitColumns')
    ->default_sort('created_at', 'desc')
    ->per_page(25)
    ->page_sizes([10, 25, 50, 100]);

// -- Search ------------------------------------------------------------------

$ds->global_search(true, 'Search by name or email...')
    ->search_debounce(300)
    ->search_min_length(2);

// -- Selection ---------------------------------------------------------------

$ds->selectable(true);

// -- Toolbar -----------------------------------------------------------------

$ds->toolbar()
    ->position('top')
    ->css_class('d-flex gap-2 mb-2');

$ds->toolbar()
    ->button('add', 'New User', 'none')
    ->css_class('btn btn-success')
    ->icon('fa fa-plus');

$ds->toolbar()
    ->button('delete_selected', 'Delete Selected', 'selected')
    ->css_class('btn btn-danger')
    ->icon('fa fa-trash')
    ->confirm('Delete all selected users? This cannot be undone.');

$ds->toolbar()
    ->button('export', 'Export', 'all')
    ->css_class('btn btn-outline-secondary')
    ->icon('fa fa-download');

// -- Action Column -----------------------------------------------------------

$ds->action_column()
    ->width('140px')
    ->frozen(true);

$ds->action_column()
    ->button('edit', 'Edit')
    ->css_class('btn btn-sm btn-primary')
    ->icon('fa fa-pencil');

$ds->action_column()
    ->button('delete', 'Delete')
    ->css_class('btn btn-sm btn-danger')
    ->icon('fa fa-trash')
    ->confirm('Delete this user?');

// -- Events ------------------------------------------------------------------

$ds->on('add', 'onAddUser');
$ds->on('edit', 'onEditUser');
$ds->on('delete', 'onDeleteUser');
$ds->on('delete_selected', 'onDeleteSelected');
$ds->on('export', 'onExportUsers');
$ds->on('row_dbl_click', 'onEditUser');

// ============================================================================
// Render
// ============================================================================

$driver = new TabulatorDriver();
$script = $driver->render_script($ds, '#users-crud', 'usersTable');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users Management</title>
    <link href="https://unpkg.com/tabulator-tables/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .italix-dataset-search {
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 8px;
            width: 300px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Users</h1>
        <div id="users-crud"></div>
    </div>

    <script src="https://unpkg.com/tabulator-tables/dist/js/tabulator.min.js"></script>
    <script>
    // ---- Toolbar callbacks --------------------------------------------------

    function onAddUser() {
        window.location.href = '/users/create';
    }

    function onDeleteSelected(selectedRows) {
        var ids = selectedRows.map(function(r) { return r.id; });
        fetch('/api/users/bulk-delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids })
        }).then(function() {
            usersTable.setData();
        });
    }

    function onExportUsers(allRows) {
        var headers = ['Name', 'Email', 'Role', 'Active', 'Registered'];
        var csv = headers.join(',') + '\n';
        allRows.forEach(function(row) {
            csv += [row.name, row.email, row.role, row.active, row.created_at]
                .map(function(v) { return '"' + String(v).replace(/"/g, '""') + '"'; })
                .join(',') + '\n';
        });
        var blob = new Blob([csv], { type: 'text/csv' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'users.csv';
        a.click();
    }

    // ---- Row action callbacks -----------------------------------------------

    function onEditUser(rowData) {
        window.location.href = '/users/' + rowData.id + '/edit';
    }

    function onDeleteUser(rowData, row) {
        fetch('/api/users/' + rowData.id, { method: 'DELETE' })
            .then(function() { row.delete(); });
    }

    // ---- Initialize table ---------------------------------------------------

    <?= $script ?>
    </script>
</body>
</html>
