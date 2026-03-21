<?php
/**
 * Example 07 — Row Selection Modes
 *
 * Shows the two row selection modes: checkbox and highlight.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Italix\DataSets\DataSet;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

$driver = new TabulatorDriver();

// ============================================================================
// Mode 1: Checkbox selection
// ============================================================================
// Adds a checkbox column at the start of the table. Users click checkboxes
// to select/deselect rows. The header checkbox toggles all rows.

$ds1 = new DataSet($usersTable);
$ds1->columns(['name', 'email', 'role']);
$ds1->ajax_url('/api/users');
$ds1->selectable(true);

// Wire selection events
$ds1->on('row_selected', 'onUserSelected');
$ds1->on('row_deselected', 'onUserDeselected');

$script1 = $driver->render_script($ds1, '#checkbox-table', 'checkboxTable');

// ============================================================================
// Mode 2: Highlight selection
// ============================================================================
// No checkbox column. Clicking a row highlights it. Hold Ctrl/Cmd to select
// multiple rows. Good for single-selection workflows.

$ds2 = new DataSet($usersTable);
$ds2->columns(['name', 'email', 'role']);
$ds2->ajax_url('/api/users');
$ds2->selectable('highlight');

$ds2->on('row_selected', 'onUserSelected');

$script2 = $driver->render_script($ds2, '#highlight-table', 'highlightTable');

?>
<h3>Checkbox Selection</h3>
<div id="checkbox-table"></div>

<h3>Highlight Selection</h3>
<div id="highlight-table"></div>

<script>
function onUserSelected(rowData, row, event) {
    console.log('Selected:', rowData.name);
}

function onUserDeselected(rowData, row, event) {
    console.log('Deselected:', rowData.name);
}

<?= $script1 ?>
<?= $script2 ?>
</script>
