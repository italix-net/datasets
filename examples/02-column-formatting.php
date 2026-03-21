<?php
/**
 * Example 02 — Column Formatting & Display Options
 *
 * Shows column formatters, alignment, frozen columns, visibility,
 * header filters, CSS classes, and extra driver-specific options.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Italix\DataSets\DataSet;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

$ds = new DataSet($productsTable);

$ds->columns(['id', 'sku', 'name', 'price', 'stock', 'category', 'active', 'updated_at']);

// Frozen ID column (stays visible when scrolling horizontally)
$ds->column('id')
    ->label('#')
    ->sortable(true)
    ->frozen(true)
    ->width('60px')
    ->h_align('center');

// SKU: monospace font via CSS class
$ds->column('sku')
    ->label('SKU')
    ->sortable(true)
    ->css_class('font-mono');

// Name: wider column, searchable
$ds->column('name')
    ->label('Product Name')
    ->sortable(true)
    ->searchable(true)
    ->min_width('200px');

// Price: currency formatter, right-aligned
$ds->column('price')
    ->sortable(true)
    ->formatter('money', [
        'decimal' => ',',
        'thousand' => '.',
        'symbol' => '€',
        'symbolAfter' => true,
        'precision' => 2,
    ])
    ->h_align('right')
    ->header_align('right');

// Stock: show progress bar
$ds->column('stock')
    ->sortable(true)
    ->formatter('progress', [
        'min' => 0,
        'max' => 100,
        'color' => ['red', 'orange', 'green'],
    ])
    ->h_align('center');

// Category: dropdown header filter for column-level filtering
$ds->column('category')
    ->sortable(true)
    ->header_filter('select');

// Active: boolean tick/cross formatter
$ds->column('active')
    ->label('Active?')
    ->formatter('tickCross')
    ->h_align('center')
    ->width('80px');

// Updated: hidden by default (can be toggled by the user)
$ds->column('updated_at')
    ->label('Last Update')
    ->visible(false)
    ->sortable(true)
    ->formatter('datetime');

// Extra Tabulator-specific options at the column level
$ds->column('name')->extra([
    'editor' => 'input',          // Make the cell editable
    'validator' => 'required',
]);

// Extra Tabulator-specific options at the dataset level
$ds->extra([
    'movableColumns' => true,
    'responsiveLayout' => 'collapse',
]);

// ---- Render ----------------------------------------------------------------

$driver = new TabulatorDriver();
$script = $driver->render_script($ds, '#products-table', 'productsTable');
