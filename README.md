# Italix DataSets

Pluggable datatable and data-tree rendering for PHP. Works with any `TableMeta`-compatible source and multiple JS datatable libraries.

## Features

- Server-side pagination, sorting, searching, and filtering
- Multi-column sorting with Shift+click interactive support
- Per-row action buttons (edit, delete, view) with confirmation dialogs
- Toolbar with bulk actions on none, selected, or all rows
- Row selection via checkboxes or row highlighting
- Global search with debounce and minimum length
- Column-level header filters (text input, select dropdown)
- Column formatting, alignment, freezing, and visibility control
- Hierarchical data trees with configurable parent-child relationships
- Event system mapping named events to JS callback functions
- Pluggable driver architecture for different JS datatable libraries
- Tabulator JS as the first built-in driver
- PHP 7.4+ compatible, PSR-4 autoloaded
- Fluent, chainable API with snake_case conventions

## Installation

```bash
composer require italix/datasets
```

## Requirements

- PHP 7.4 or higher
- [italix/contracts](https://github.com/italix-net/contracts) (installed automatically)

## Quick Start

```php
<?php

use Italix\DataSets\DataSet;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

// Create a dataset from any TableMeta source (e.g. from italix/orm)
$ds = new DataSet($usersTable);

// Select and configure columns
$ds->columns(['name', 'email', 'role', 'created_at']);

$ds->column('name')->sortable(true)->searchable(true)->width('200px');
$ds->column('email')->sortable(true)->searchable(true);
$ds->column('role')->sortable(true)->h_align('center');
$ds->column('created_at')->sortable(true)->formatter('datetime');

// Server-side data loading
$ds->ajax_url('/api/users')
    ->per_page(25)
    ->default_sort('created_at', 'desc');

// Render with Tabulator
$driver = new TabulatorDriver();
$script = $driver->render_script($ds, '#users-table', 'usersTable');
```

```html
<link href="https://unpkg.com/tabulator-tables/dist/css/tabulator.min.css" rel="stylesheet">
<script src="https://unpkg.com/tabulator-tables/dist/js/tabulator.min.js"></script>

<div id="users-table"></div>
<script><?= $script ?></script>
```

## Action Buttons

Add per-row action buttons with optional confirmation dialogs:

```php
$ds->action_column()
    ->width('150px')
    ->frozen(true);

$ds->action_column()->button('edit', 'Edit')
    ->css_class('btn btn-sm btn-primary')
    ->icon('fa fa-pencil');

$ds->action_column()->button('delete', 'Delete')
    ->css_class('btn btn-sm btn-danger')
    ->icon('fa fa-trash')
    ->confirm('Are you sure?');

// Map actions to JS callback functions
$ds->on('edit', 'onEditUser');
$ds->on('delete', 'onDeleteUser');
```

```javascript
function onEditUser(rowData, row) {
    window.location.href = '/users/' + rowData.id + '/edit';
}

function onDeleteUser(rowData, row) {
    fetch('/api/users/' + rowData.id, { method: 'DELETE' })
        .then(function() { row.delete(); });
}
```

## Toolbar & Bulk Actions

Add toolbar buttons that operate on no data, selected rows, or all rows:

```php
$ds->selectable(true);

$ds->toolbar()->position('top');

// scope='none' — callback receives no arguments
$ds->toolbar()->button('add', 'New User', 'none')
    ->css_class('btn btn-success')
    ->icon('fa fa-plus');

// scope='selected' — callback receives selected row data
$ds->toolbar()->button('delete_selected', 'Delete Selected', 'selected')
    ->css_class('btn btn-danger')
    ->confirm('Delete selected users?');

// scope='all' — callback receives all row data
$ds->toolbar()->button('export', 'Export', 'all')
    ->css_class('btn btn-outline-secondary');

$ds->on('add', 'onAddUser');
$ds->on('delete_selected', 'onDeleteSelected');
$ds->on('export', 'onExportUsers');
```

## Row Selection

Two selection modes:

```php
// Checkbox mode — adds a checkbox column, supports select-all
$ds->selectable(true);

// Highlight mode — click rows to select, Ctrl+click for multi-select
$ds->selectable('highlight');
```

## Search & Filtering

```php
// Global search input above the table
$ds->global_search(true, 'Search users...')
    ->search_debounce(400)
    ->search_min_length(2);

// Mark columns as searchable (sent as search_columns[] in AJAX requests)
$ds->column('name')->searchable(true);
$ds->column('email')->searchable(true);

// Column-level header filters
$ds->column('role')->header_filter('select');
$ds->column('name')->header_filter('input');
```

## Multi-column Sorting

```php
// Call default_sort() multiple times to add sort levels
$ds->default_sort('status', 'asc');
$ds->default_sort('created_at', 'desc');

// Users can also Shift+click column headers interactively
```

## Row Events

```php
$ds->on('row_click', 'onRowClick');
$ds->on('row_dbl_click', 'onRowDblClick');
$ds->on('row_context', 'onRowContext');
$ds->on('row_selected', 'onRowSelected');
$ds->on('row_deselected', 'onRowDeselected');
```

```javascript
// Each callback receives (rowData, row, event)
function onRowDblClick(rowData, row, event) {
    window.location.href = '/users/' + rowData.id + '/edit';
}
```

## Data Trees

Display hierarchical data with parent-child relationships:

```php
use Italix\DataSets\DataTree;

$tree = new DataTree($categoriesTable);
$tree->columns(['name', 'sort_order']);
$tree->ajax_url('/api/categories');

$tree->tree_config()
    ->parent_column('parent_id')
    ->id_column('id')
    ->start_open(true)
    ->toggle_column('name');
```

## Server-Side Handler

Handle AJAX requests on the server with `ServerSideRequest` and `ServerSideResponse`:

```php
use Italix\DataSets\ServerSideRequest;
use Italix\DataSets\ServerSideResponse;

$request = ServerSideRequest::from_globals();

// Pagination
$page     = $request->page();       // 1-based
$per_page = $request->per_page();   // default 25
$offset   = $request->offset();     // calculated

// Sorting (single or multi-column)
$sort_col = $request->sort_column();
$sort_dir = $request->sort_direction();
$sorts    = $request->sorts();  // [['column' => '...', 'direction' => '...'], ...]

// Search
$search   = $request->search();
$columns  = $request->search_columns();

// Column filters
$filters  = $request->filters();  // ['role' => 'admin', ...]

// Build and send the response
$response = ServerSideResponse::from_request($rows, $total, $request);
$response->send();  // outputs JSON and exits
```

The response format:

```json
{
    "data": [{"id": 1, "name": "..."}],
    "total": 150,
    "page": 1,
    "per_page": 25,
    "last_page": 6
}
```

## Column Configuration

```php
$ds->column('price')
    ->label('Unit Price')              // Display label
    ->sortable(true)                   // Enable sorting
    ->searchable(true)                 // Include in global search
    ->visible(false)                   // Hidden (toggle-able by user)
    ->width('120px')                   // Fixed width
    ->min_width('80px')                // Minimum width
    ->formatter('money', ['symbol' => '€'])  // Tabulator formatter
    ->h_align('right')                 // Cell alignment
    ->header_align('right')            // Header alignment
    ->frozen(true)                     // Sticky column
    ->css_class('font-mono')           // CSS class
    ->header_filter('input')           // Column header filter
    ->extra(['editor' => 'input']);     // Driver-specific options
```

## Driver System

DataSets uses a pluggable driver system. Register and switch drivers:

```php
use Italix\DataSets\Drivers\DriverRegistry;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

$registry = new DriverRegistry();
$registry->register(new TabulatorDriver());

$driver = $registry->get('tabulator');
$config = $ds->render($driver);    // array
$json   = $ds->to_json($driver);   // JSON string
$script = $driver->render_script($ds, '#table');  // JS snippet
```

Implement `DriverInterface` to create custom drivers for other JS libraries.

## Helper Functions

```php
use function Italix\DataSets\dataset;
use function Italix\DataSets\data_tree;

$ds   = dataset($usersTable)->columns(['name', 'email'])->ajax_url('/api/users');
$tree = data_tree($categoriesTable)->ajax_url('/api/categories');
```

## Using with Italix ORM

DataSets works with any `TableMeta`-compatible source. With Italix ORM, your table schemas are already `TableMeta`:

```php
use function Italix\Orm\Schema\{mysql_table, integer, varchar, timestamp};

$users = mysql_table('users', [
    'id'         => integer()->primary_key()->auto_increment(),
    'name'       => varchar(100)->not_null(),
    'email'      => varchar(255)->not_null(),
    'created_at' => timestamp()->default_value('CURRENT_TIMESTAMP'),
]);

// Pass directly to DataSet — no adapters needed
$ds = new DataSet($users);
```

## Examples

See the `examples/` directory for complete, runnable examples:

| Example | Description |
|---|---|
| `01-basic-table.php` | Basic table with columns, sorting, pagination, AJAX |
| `02-column-formatting.php` | Formatters, alignment, frozen columns, visibility |
| `03-action-buttons.php` | Per-row Edit/View/Delete buttons with confirm dialogs |
| `04-toolbar-bulk-actions.php` | Toolbar with none/selected/all scope buttons |
| `05-search-and-filtering.php` | Global search and column header filters |
| `06-multi-sort.php` | Multi-column default sorting |
| `07-row-selection.php` | Checkbox vs highlight selection modes |
| `08-row-events.php` | Row click, double-click, context menu events |
| `09-data-tree.php` | Hierarchical data tree with parent-child config |
| `10-server-side-handler.php` | ServerSideRequest/Response with query building |
| `11-complete-crud.php` | Full CRUD table combining all features |
| `12-driver-registry.php` | Driver registry and custom driver pattern |
| `13-helper-functions.php` | dataset() and data_tree() shorthand functions |

## License

LGPL-2.1-or-later. See [LICENSE](LICENSE) for details.
