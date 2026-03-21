<?php
/**
 * Example 09 — Hierarchical Data Tree
 *
 * Shows how to display hierarchical data (categories, org charts,
 * nested menus) using DataTree with parent-child relationships.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Italix\DataSets\DataTree;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

// ============================================================================
// Assume $categoriesTable describes a "categories" table with columns:
//   id (INTEGER), name (VARCHAR), parent_id (INTEGER), sort_order (INTEGER)
//
// Data example:
//   id=1, name="Electronics",  parent_id=NULL
//   id=2, name="Phones",       parent_id=1
//   id=3, name="Laptops",      parent_id=1
//   id=4, name="Clothing",     parent_id=NULL
//   id=5, name="Shirts",       parent_id=4
// ============================================================================

$tree = new DataTree($categoriesTable);

$tree->columns(['name', 'sort_order']);

// Configure the tree structure
$tree->tree_config()
    ->parent_column('parent_id')  // Column holding the parent's ID
    ->id_column('id')             // Column holding the row's own ID
    ->start_open(true)            // Expand all nodes by default
    ->toggle_column('name');      // Show expand/collapse toggle on "name" column

// Column settings
$tree->column('name')
    ->label('Category Name')
    ->sortable(true)
    ->searchable(true)
    ->width('300px');

$tree->column('sort_order')
    ->label('Order')
    ->sortable(true)
    ->h_align('center')
    ->width('80px');

// AJAX loading
$tree->ajax_url('/api/categories');

// Action buttons on each node
$tree->action_column()->button('edit', 'Edit')
    ->css_class('btn btn-sm btn-primary')
    ->icon('fa fa-pencil');

$tree->action_column()->button('add_child', 'Add Child')
    ->css_class('btn btn-sm btn-success')
    ->icon('fa fa-plus');

$tree->action_column()->button('delete', 'Delete')
    ->css_class('btn btn-sm btn-danger')
    ->icon('fa fa-trash')
    ->confirm('Delete this category and all its children?');

$tree->on('edit', 'onEditCategory');
$tree->on('add_child', 'onAddChild');
$tree->on('delete', 'onDeleteCategory');

// ---- Render ----------------------------------------------------------------

$driver = new TabulatorDriver();
$script = $driver->render_script($tree, '#categories-tree', 'categoriesTree');

?>
<div id="categories-tree"></div>

<script>
function onEditCategory(rowData) {
    window.location.href = '/categories/' + rowData.id + '/edit';
}

function onAddChild(rowData) {
    window.location.href = '/categories/create?parent_id=' + rowData.id;
}

function onDeleteCategory(rowData) {
    fetch('/api/categories/' + rowData.id, { method: 'DELETE' })
        .then(function() { categoriesTree.setData(); });
}

<?= $script ?>
</script>
