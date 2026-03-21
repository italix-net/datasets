<?php
/**
 * Example 03 — Per-row Action Buttons
 *
 * Shows how to add Edit / View / Delete buttons to each row using
 * the ActionColumn, with confirmation dialogs and JS event callbacks.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Italix\DataSets\DataSet;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

$ds = new DataSet($usersTable);

$ds->columns(['name', 'email', 'role']);
$ds->ajax_url('/api/users');

// ---- Action Column ---------------------------------------------------------
// Adds a column with buttons on every row. By default it appears at the end.

$ds->action_column()
    ->label('Actions')
    ->width('180px')
    ->frozen(true);

// View button
$ds->action_column()
    ->button('view', 'View')
    ->css_class('btn btn-sm btn-info')
    ->icon('fa fa-eye')
    ->title('View details');

// Edit button
$ds->action_column()
    ->button('edit', 'Edit')
    ->css_class('btn btn-sm btn-primary')
    ->icon('fa fa-pencil');

// Delete button with confirmation
$ds->action_column()
    ->button('delete', 'Delete')
    ->css_class('btn btn-sm btn-danger')
    ->icon('fa fa-trash')
    ->confirm('Are you sure you want to delete this user?');

// ---- Event Callbacks -------------------------------------------------------
// Map each action name to a JS function that receives the row data.

$ds->on('view', 'onViewUser');
$ds->on('edit', 'onEditUser');
$ds->on('delete', 'onDeleteUser');

// ---- Render ----------------------------------------------------------------

$driver = new TabulatorDriver();
$script = $driver->render_script($ds, '#users-table', 'usersTable');

?>
<div id="users-table"></div>

<script>
// These JS functions are called when buttons are clicked.
// Each receives (rowData, row) where rowData is the row's data object
// and row is the Tabulator Row component.

function onViewUser(rowData, row) {
    window.location.href = '/users/' + rowData.id;
}

function onEditUser(rowData, row) {
    window.location.href = '/users/' + rowData.id + '/edit';
}

function onDeleteUser(rowData, row) {
    // The confirm dialog already fired before this is called.
    fetch('/api/users/' + rowData.id, { method: 'DELETE' })
        .then(function() {
            row.delete();  // Remove the row from the table
        });
}

<?= $script ?>
</script>
