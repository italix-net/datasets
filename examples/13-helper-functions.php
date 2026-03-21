<?php
/**
 * Example 13 — Helper Functions
 *
 * Shows the shorthand helper functions for creating DataSet and DataTree
 * instances, for a more concise syntax.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;
use function Italix\DataSets\dataset;
use function Italix\DataSets\data_tree;

$driver = new TabulatorDriver();

// ============================================================================
// dataset() — shorthand for new DataSet($tableMeta)
// ============================================================================

$ds = dataset($usersTable)
    ->columns(['name', 'email', 'role'])
    ->ajax_url('/api/users')
    ->per_page(25)
    ->default_sort('name', 'asc');

$ds->column('name')->sortable(true)->searchable(true);
$ds->column('email')->sortable(true)->searchable(true);

$script = $driver->render_script($ds, '#users-table');

// ============================================================================
// data_tree() — shorthand for new DataTree($tableMeta)
// ============================================================================

$tree = data_tree($categoriesTable)
    ->columns(['name', 'sort_order'])
    ->ajax_url('/api/categories');

$tree->tree_config()
    ->parent_column('parent_id')
    ->start_open(true);

$tree->column('name')->sortable(true);

$tree_script = $driver->render_script($tree, '#categories-tree');
