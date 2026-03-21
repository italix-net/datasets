<?php
/**
 * Example 01 — Basic Table
 *
 * Shows how to create a simple data table with column selection,
 * sorting, pagination, and AJAX server-side loading.
 *
 * Prerequisites:
 *   - A class implementing Italix\Contracts\TableMeta (e.g. from italix/orm)
 *   - The Tabulator JS/CSS loaded in your page
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Italix\DataSets\DataSet;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

// ============================================================================
// Assume $usersTable is a TableMeta describing a "users" table with columns:
//   id (INTEGER), name (VARCHAR), email (VARCHAR), role (VARCHAR),
//   created_at (TIMESTAMP)
// ============================================================================

$ds = new DataSet($usersTable);

// Pick which columns to show (and in what order)
$ds->columns(['name', 'email', 'role', 'created_at']);

// Column-level configuration
$ds->column('name')
    ->label('Full Name')
    ->sortable(true)
    ->width('200px');

$ds->column('email')
    ->sortable(true)
    ->searchable(true);

$ds->column('role')
    ->label('User Role')
    ->sortable(true)
    ->h_align('center');

$ds->column('created_at')
    ->label('Registered')
    ->sortable(true)
    ->formatter('datetime', ['outputFormat' => 'YYYY-MM-DD']);

// Default sort
$ds->default_sort('created_at', 'desc');

// AJAX: load data from server
$ds->ajax_url('/api/users')
    ->ajax_method('GET');

// Pagination
$ds->per_page(25)
    ->page_sizes([10, 25, 50, 100]);

// Display options
$ds->id('users-table')
    ->css_class('table-striped')
    ->height('600px')
    ->layout('fitColumns');

// ---- Render ----------------------------------------------------------------

$driver = new TabulatorDriver();

// Option A: Get the JSON config (for embedding in a JS variable)
$json = $ds->to_json($driver);

// Option B: Get a complete JS snippet that creates the table
$script = $driver->render_script($ds, '#users-table', 'usersTable');

?>
<!-- In your HTML template -->
<link href="https://unpkg.com/tabulator-tables/dist/css/tabulator.min.css" rel="stylesheet">
<script src="https://unpkg.com/tabulator-tables/dist/js/tabulator.min.js"></script>

<div id="users-table"></div>

<script>
<?= $script ?>
</script>
