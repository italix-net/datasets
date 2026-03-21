<?php
/**
 * Example 05 — Global Search & Column Filtering
 *
 * Shows how to enable a global search box, per-column header filters,
 * and how to configure debounce and minimum search length.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Italix\DataSets\DataSet;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

$ds = new DataSet($usersTable);

$ds->columns(['name', 'email', 'role', 'created_at']);
$ds->ajax_url('/api/users');

// ---- Global Search ---------------------------------------------------------
// Creates a search input above the table that triggers a server reload.

$ds->global_search(true, 'Search users by name or email...')
    ->search_debounce(400)     // Wait 400ms after typing before searching
    ->search_min_length(2);    // Don't search until at least 2 chars typed

// Mark which columns the server should search in.
// These column names are sent as search_columns[] in the AJAX request.
$ds->column('name')->searchable(true);
$ds->column('email')->searchable(true);

// ---- Column Header Filters -------------------------------------------------
// These add a filter input directly inside the column header.
// For server-side mode they are sent as filters[column]=value.

// Free-text filter
$ds->column('name')
    ->sortable(true)
    ->header_filter('input');

// Dropdown filter
$ds->column('role')
    ->sortable(true)
    ->header_filter('select');

// ---- Render ----------------------------------------------------------------

$driver = new TabulatorDriver();
$script = $driver->render_script($ds, '#users-table');
