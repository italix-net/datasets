<?php
/**
 * Example 08 — Row Events
 *
 * Shows how to handle row-level events like click, double-click,
 * and right-click (context menu).
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Italix\DataSets\DataSet;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

$ds = new DataSet($usersTable);

$ds->columns(['name', 'email', 'role', 'created_at']);
$ds->ajax_url('/api/users');

// ---- Row Events ------------------------------------------------------------
// Map event names to JS function names. The JS function receives:
//   (rowData, row, event) for row events
//
// Available row events:
//   row_click       — single click on a row
//   row_dbl_click   — double click on a row
//   row_context     — right-click on a row
//   row_selected    — row becomes selected
//   row_deselected  — row becomes deselected

$ds->on('row_click', 'onRowClick');
$ds->on('row_dbl_click', 'onRowDblClick');
$ds->on('row_context', 'onRowContext');

// ---- Render ----------------------------------------------------------------

$driver = new TabulatorDriver();
$script = $driver->render_script($ds, '#users-table');

?>
<div id="users-table"></div>

<script>
function onRowClick(rowData, row, event) {
    console.log('Clicked row:', rowData.name);
}

function onRowDblClick(rowData, row, event) {
    // Open the edit page on double-click
    window.location.href = '/users/' + rowData.id + '/edit';
}

function onRowContext(rowData, row, event) {
    event.preventDefault();
    // Show a custom context menu
    showContextMenu(event.pageX, event.pageY, [
        { label: 'Edit', action: function() { onRowDblClick(rowData); } },
        { label: 'Copy Email', action: function() { navigator.clipboard.writeText(rowData.email); } },
    ]);
}

<?= $script ?>
</script>
