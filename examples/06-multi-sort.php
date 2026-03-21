<?php
/**
 * Example 06 — Multi-column Sorting
 *
 * Shows how to set up default multi-column sorting. Users can also
 * hold Shift and click column headers to sort by multiple columns
 * interactively.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Italix\DataSets\DataSet;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

$ds = new DataSet($ordersTable);

$ds->columns(['order_number', 'customer', 'status', 'total', 'created_at']);
$ds->ajax_url('/api/orders');

// Make columns sortable
$ds->column('customer')->sortable(true);
$ds->column('status')->sortable(true);
$ds->column('total')->sortable(true);
$ds->column('created_at')->sortable(true);

// Default multi-column sort: first by status ascending, then by date descending.
// Call default_sort() multiple times to add sort levels.
$ds->default_sort('status', 'asc');
$ds->default_sort('created_at', 'desc');

// ---- Render ----------------------------------------------------------------

$driver = new TabulatorDriver();
$config = $driver->render($ds);

// The config will contain:
//   "initialSort": [
//       { "column": "status", "dir": "asc" },
//       { "column": "created_at", "dir": "desc" }
//   ]

$script = $driver->render_script($ds, '#orders-table');
