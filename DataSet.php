<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets - DataSet
 *
 * @package Italix\DataSets
 * @license MPL-2.0
 */

declare(strict_types=1);

namespace Italix\DataSets;

use Italix\Contracts\ColumnMeta;
use Italix\Contracts\TableMeta;
use Italix\DataSets\Drivers\DriverInterface;
use Generator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Core datatable definition.
 *
 * DataSet wraps any TableMeta source and configures which columns to
 * display, how they behave (sortable, searchable, etc.), and where
 * to fetch data from (AJAX URL for server-side processing).
 *
 * Example:
 *
 *     $ds = new DataSet($users_table);
 *     $ds->columns(['id', 'name', 'email', 'created_at']);
 *     $ds->column('name')->label('Full Name')->sortable(true);
 *     $ds->column('email')->sortable(true)->searchable(true);
 *     $ds->column('created_at')->formatter('datetime');
 *     $ds->ajax_url('/api/users');
 *
 *     // Action buttons on each row
 *     $ds->action_column()
 *        ->button('edit', 'Edit');
 *     $ds->action_column()
 *        ->button('delete', 'Delete')->confirm('Are you sure?');
 *
 *     // Toolbar with bulk actions
 *     $ds->selectable(true);
 *     $ds->toolbar()
 *        ->button('bulk_delete', 'Delete Selected', 'selected');
 *
 *     // Events
 *     $ds->on('edit', 'onEditRow');
 *     $ds->on('row_click', 'onRowClick');
 *
 *     // Render with a driver
 *     $driver = new TabulatorDriver();
 *     $js = $driver->render_script($ds, '#my-table');
 */
class DataSet
{
    /** @var TableMeta */
    private $source;

    /** @var array<string, DataSetColumn> */
    private $column_config = [];

    /** @var string[] Ordered list of column names to display */
    private $visible_columns = [];

    /** @var string|null AJAX URL for server-side data */
    private $ajax_url = null;

    /** @var string HTTP method for AJAX requests */
    private $ajax_method = 'GET';

    /** @var array Extra parameters to send with AJAX requests */
    private $ajax_params = [];

    /** @var array<array{column: string, direction: string}> Default sort columns */
    private $default_sorts = [];

    /** @var int Rows per page */
    private $per_page = 25;

    /** @var int[] Available page size options */
    private $page_sizes = [10, 25, 50, 100];

    /** @var string|null Unique identifier for this dataset (used for HTML element IDs) */
    private $id = null;

    /** @var string|null CSS class for the table container */
    private $css_class = null;

    /** @var string|null Table height (CSS value, e.g. '500px', '80vh') */
    private $height = null;

    /** @var string Layout mode hint for the driver */
    private $layout = 'fitColumns';

    /** @var bool Whether to show a global search input */
    private $global_search = false;

    /** @var string|null Placeholder text for the global search input */
    private $search_placeholder = null;

    /** @var int Debounce delay in milliseconds for search/filter inputs */
    private $search_debounce = 300;

    /** @var int Minimum characters before triggering a search */
    private $search_min_length = 1;

    /** @var ActionColumn|null Per-row action buttons column */
    private $action_column = null;

    /** @var Toolbar|null Table toolbar with buttons */
    private $toolbar = null;

    /** @var bool|string Row selection mode: false, true (checkbox), 'highlight' (click-to-select) */
    private $selectable = false;

    /** @var array<string, string> Event name => JS callback function name */
    private $events = [];

    /**
     * Responsive layout mode.
     *
     * Controls how columns are handled when the table is too wide for the viewport.
     *
     * Values:
     *   false       — no responsive handling (default; table may overflow)
     *   'hide'      — columns with lower priority are hidden; no collapsed panel
     *   'collapse'  — hidden columns are folded into a togglable sub-row per record
     *   'scroll'    — hint to drivers that horizontal scrolling is preferred
     *
     * Each driver maps these abstract values to its own API.
     * Column priority is set via DataSetColumn::responsive().
     *
     * @var string|false
     */
    private $responsive_layout = false;

    /**
     * Card layout configuration for narrow viewports.
     *
     * When set, the driver renders each row as a vertical card when the
     * viewport width is at or below the breakpoint. On wider viewports the
     * normal table is shown. Drivers that do not support card layout ignore
     * this setting.
     *
     * @var array|null  ['title_field'=>string, 'breakpoint'=>int, 'columns_per_row'=>int]
     */
    private $card_layout = null;

    /** @var array Extra driver-specific options merged at the top level */
    private $extra = [];

    public function __construct(TableMeta $source)
    {
        $this->source = $source;
    }

    // =========================================================================
    // Column Configuration
    // =========================================================================

    /**
     * Set the columns to display, in order.
     *
     * Accepts an array of column names. Columns not in this list
     * will not be shown. If never called, all source columns are used.
     *
     * @param string[] $columns
     * @return self
     * @throws InvalidArgumentException If a column is not found in the source
     */
    public function columns(array $columns): self
    {
        foreach ($columns as $name) {
            if ($this->source->describe_column($name) === null) {
                throw new InvalidArgumentException(
                    "Column '{$name}' not found in the TableMeta source."
                );
            }
        }

        $this->visible_columns = $columns;
        return $this;
    }

    /**
     * Get or create the column configuration for a specific column.
     *
     * @param string $name
     * @return DataSetColumn
     * @throws InvalidArgumentException If the column is not found in the source
     */
    public function column(string $name): DataSetColumn
    {
        if (!isset($this->column_config[$name])) {
            $descriptor = $this->source->describe_column($name);
            if ($descriptor === null) {
                throw new InvalidArgumentException(
                    "Column '{$name}' not found in the TableMeta source."
                );
            }
            $this->column_config[$name] = new DataSetColumn($descriptor);
        }

        return $this->column_config[$name];
    }

    /**
     * Iterate over the configured columns in display order.
     *
     * If columns() was called, uses that order. Otherwise, iterates
     * all source columns.
     *
     * @return Generator<string, DataSetColumn>
     */
    public function each_column(): Generator
    {
        $names = !empty($this->visible_columns)
            ? $this->visible_columns
            : $this->source_column_names();

        foreach ($names as $name) {
            yield $name => $this->column($name);
        }
    }

    /**
     * Get the ordered list of visible column names.
     *
     * @return string[]
     */
    public function get_visible_columns(): array
    {
        if (!empty($this->visible_columns)) {
            return $this->visible_columns;
        }

        return $this->source_column_names();
    }

    /**
     * The column names the source declares.
     *
     * `TableMeta::describe_columns()` returns an **iterable**, which a source is
     * entitled to satisfy with a plain array — the interface's own example does.
     * `iterator_to_array()` did not accept arrays until PHP 8.2, and this
     * library declares `php: >=7.4`, so calling it directly threw a TypeError
     * on every array-backed source on every supported version below that.
     *
     * `Italix\Rules\Checker::keys_of()` already had the right shape; this is
     * the same guard, in the library that needed it and did not have it.
     */
    private function source_column_names(): array
    {
        $columns = $this->source->describe_columns();

        return array_keys(is_array($columns) ? $columns : iterator_to_array($columns));
    }

    // =========================================================================
    // Data Source Configuration
    // =========================================================================

    /**
     * Set the AJAX URL for server-side data loading.
     *
     * @param string $url
     * @return self
     */
    public function ajax_url(string $url): self
    {
        $this->ajax_url = $url;
        return $this;
    }

    /**
     * Set the HTTP method for AJAX requests.
     *
     * @param string $method 'GET' or 'POST'
     * @return self
     */
    public function ajax_method(string $method): self
    {
        $this->ajax_method = strtoupper($method);
        return $this;
    }

    /**
     * Set extra parameters to send with every AJAX request.
     *
     * @param array $params
     * @return self
     */
    public function ajax_params(array $params): self
    {
        $this->ajax_params = array_merge($this->ajax_params, $params);
        return $this;
    }

    // =========================================================================
    // Pagination & Sorting
    // =========================================================================

    /**
     * Set default sort column(s).
     *
     * Can be called once for a single sort, or multiple times to sort
     * by multiple columns. Each call appends to the sort list.
     *
     * Example:
     *
     *     $ds->default_sort('name', 'asc');
     *     $ds->default_sort('created_at', 'desc');
     *     // -> sorts by name ASC first, then created_at DESC
     *
     * @param string $column
     * @param string $direction 'asc' or 'desc'
     * @return self
     */
    public function default_sort(string $column, string $direction = 'asc'): self
    {
        $this->default_sorts[] = [
            'column' => $column,
            'direction' => strtolower($direction),
        ];
        return $this;
    }

    /**
     * Set the number of rows per page.
     *
     * @param int $per_page
     * @return self
     */
    public function per_page(int $per_page): self
    {
        $this->per_page = $per_page;
        return $this;
    }

    /**
     * Set the available page size options.
     *
     * @param int[] $sizes
     * @return self
     */
    public function page_sizes(array $sizes): self
    {
        $this->page_sizes = $sizes;
        return $this;
    }

    // =========================================================================
    // Display Configuration
    // =========================================================================

    /**
     * Set a unique identifier for this dataset.
     *
     * Used as the HTML element ID for the table container.
     *
     * @param string $id
     * @return self
     */
    public function id(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set a CSS class for the table container.
     *
     * @param string $css_class
     * @return self
     */
    public function css_class(string $css_class): self
    {
        $this->css_class = $css_class;
        return $this;
    }

    /**
     * Set the table height.
     *
     * @param string $height CSS value, e.g. '500px', '80vh'
     * @return self
     */
    public function height(string $height): self
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Set the layout mode hint for the driver.
     *
     * Common values: 'fitColumns', 'fitData', 'fitDataFill', 'fitDataStretch'.
     *
     * @param string $layout
     * @return self
     */
    public function layout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Enable a global search input.
     *
     * @param bool $enabled
     * @param string|null $placeholder
     * @return self
     */
    public function global_search(bool $enabled = true, ?string $placeholder = null): self
    {
        $this->global_search = $enabled;
        $this->search_placeholder = $placeholder;
        return $this;
    }

    /**
     * Set the debounce delay for search/filter inputs.
     *
     * Controls how long to wait (in ms) after the user stops typing
     * before sending an AJAX request. Prevents excessive requests.
     *
     * @param int $ms Delay in milliseconds (default: 300)
     * @return self
     */
    public function search_debounce(int $ms): self
    {
        $this->search_debounce = $ms;
        return $this;
    }

    /**
     * Set the minimum number of characters before triggering a search.
     *
     * @param int $length Minimum characters (default: 1)
     * @return self
     */
    public function search_min_length(int $length): self
    {
        $this->search_min_length = $length;
        return $this;
    }

    // =========================================================================
    // Action Column
    // =========================================================================

    /**
     * Get or create the per-row action column.
     *
     * @return ActionColumn
     */
    public function action_column(): ActionColumn
    {
        if ($this->action_column === null) {
            $this->action_column = new ActionColumn();
        }
        return $this->action_column;
    }

    /**
     * Check if an action column is configured.
     *
     * @return bool
     */
    public function has_action_column(): bool
    {
        return $this->action_column !== null && !empty($this->action_column->get_buttons());
    }

    /**
     * Get the action column (may be null).
     *
     * @return ActionColumn|null
     */
    public function get_action_column(): ?ActionColumn
    {
        return $this->action_column;
    }

    // =========================================================================
    // Toolbar
    // =========================================================================

    /**
     * Get or create the toolbar.
     *
     * @return Toolbar
     */
    public function toolbar(): Toolbar
    {
        if ($this->toolbar === null) {
            $this->toolbar = new Toolbar();
        }
        return $this->toolbar;
    }

    /**
     * Check if a toolbar is configured.
     *
     * @return bool
     */
    public function has_toolbar(): bool
    {
        return $this->toolbar !== null && !empty($this->toolbar->get_buttons());
    }

    /**
     * Get the toolbar (may be null).
     *
     * @return Toolbar|null
     */
    public function get_toolbar(): ?Toolbar
    {
        return $this->toolbar;
    }

    // =========================================================================
    // Row Selection
    // =========================================================================

    /**
     * Enable row selection.
     *
     * @param bool|string $mode true for checkbox selection, 'highlight' for click-to-select
     * @return self
     */
    public function selectable($mode = true): self
    {
        $this->selectable = $mode;
        return $this;
    }

    /**
     * Check if row selection is enabled.
     *
     * @return bool
     */
    public function is_selectable(): bool
    {
        return $this->selectable !== false;
    }

    /**
     * Get the selection mode.
     *
     * @return bool|string false, true, or 'highlight'
     */
    public function get_selectable()
    {
        return $this->selectable;
    }

    // =========================================================================
    // Events
    // =========================================================================

    /**
     * Register a JS callback for an event.
     *
     * The callback function name refers to a function in the global scope
     * (window.functionName) that the JS bootstrap will call when the event fires.
     *
     * Built-in events:
     * - 'row_click'       — callback(rowData, row)
     * - 'row_dbl_click'   — callback(rowData, row)
     * - 'row_context'     — callback(rowData, row, event)
     * - 'row_selected'    — callback(rowData, row)
     * - 'row_deselected'  — callback(rowData, row)
     *
     * Action events (from action_column buttons):
     * - '{button_name}'   — callback(rowData, row)
     *
     * Toolbar events (from toolbar buttons):
     * - '{button_name}'   — callback(selectedRows) for scope='selected'
     *                     — callback(allData) for scope='all'
     *                     — callback() for scope='none'
     *
     * @param string $event Event or action name
     * @param string $callback JS function name (must exist in window scope)
     * @return self
     */
    public function on(string $event, string $callback): self
    {
        $this->events[$event] = $callback;
        return $this;
    }

    /**
     * Get all registered event callbacks.
     *
     * @return array<string, string>
     */
    public function get_events(): array
    {
        return $this->events;
    }

    /**
     * Get the callback for a specific event.
     *
     * @param string $event
     * @return string|null
     */
    public function get_event_callback(string $event): ?string
    {
        return $this->events[$event] ?? null;
    }

    // =========================================================================
    // Extra
    // =========================================================================

    /**
     * Set the responsive layout mode for this dataset.
     *
     * Controls what happens when the table is wider than the viewport:
     *   false       — no responsive handling (default)
     *   'hide'      — low-priority columns are hidden outright
     *   'collapse'  — low-priority columns collapse into a togglable sub-row
     *   'scroll'    — horizontal scrolling (driver hint only)
     *
     * Column visibility priority is set via DataSetColumn::responsive().
     * Columns not given a priority use the driver's default (usually visible).
     *
     * @param string|false $mode
     * @return self
     */
    public function responsive_layout($mode): self
    {
        $this->responsive_layout = $mode;
        return $this;
    }

    /**
     * Enable a card-style layout for narrow viewports.
     *
     * Each row is rendered as a vertical card when viewport width ≤ $breakpoint.
     * $title_field names the column shown prominently as the card heading.
     * $columns_per_row controls how many fields appear side by side in the card body.
     * Control which columns appear and their order with DataSetColumn::card_visible()
     * and DataSetColumn::card_order().
     *
     * @param string $title_field      Column used as card heading (raw text value)
     * @param int    $breakpoint       Viewport width in px; cards activate at or below this (default: 640)
     * @param int    $columns_per_row  Fields per row in the card body (default: 1)
     * @return self
     */
    public function card_layout(string $title_field, int $breakpoint = 640, int $columns_per_row = 1): self
    {
        $this->card_layout = [
            'title_field'     => $title_field,
            'breakpoint'      => $breakpoint,
            'columns_per_row' => $columns_per_row,
        ];
        return $this;
    }

    /**
     * Set extra driver-specific options merged at the top level.
     *
     * @param array $options
     * @return self
     */
    public function extra(array $options): self
    {
        $this->extra = array_merge($this->extra, $options);
        return $this;
    }

    // =========================================================================
    // Getters
    // =========================================================================

    /**
     * Get the underlying TableMeta source.
     *
     * @return TableMeta
     */
    public function source(): TableMeta
    {
        return $this->source;
    }

    /** @return string|null */
    public function get_ajax_url(): ?string
    {
        return $this->ajax_url;
    }

    /** @return string */
    public function get_ajax_method(): string
    {
        return $this->ajax_method;
    }

    /** @return array */
    public function get_ajax_params(): array
    {
        return $this->ajax_params;
    }

    /**
     * Get the default sort columns.
     *
     * @return array<array{column: string, direction: string}>
     */
    public function get_default_sorts(): array
    {
        return $this->default_sorts;
    }

    /**
     * Get the first default sort column (backwards compat).
     *
     * @return string|null
     */
    public function get_default_sort_column(): ?string
    {
        return !empty($this->default_sorts) ? $this->default_sorts[0]['column'] : null;
    }

    /**
     * Get the first default sort direction (backwards compat).
     *
     * @return string
     */
    public function get_default_sort_direction(): string
    {
        return !empty($this->default_sorts) ? $this->default_sorts[0]['direction'] : 'asc';
    }

    /** @return int */
    public function get_per_page(): int
    {
        return $this->per_page;
    }

    /** @return int[] */
    public function get_page_sizes(): array
    {
        return $this->page_sizes;
    }

    /** @return string|null */
    public function get_id(): ?string
    {
        return $this->id;
    }

    /** @return string|null */
    public function get_css_class(): ?string
    {
        return $this->css_class;
    }

    /** @return string|null */
    public function get_height(): ?string
    {
        return $this->height;
    }

    /** @return string */
    public function get_layout(): string
    {
        return $this->layout;
    }

    /** @return bool */
    public function has_global_search(): bool
    {
        return $this->global_search;
    }

    /** @return string|null */
    public function get_search_placeholder(): ?string
    {
        return $this->search_placeholder;
    }

    /** @return int */
    public function get_search_debounce(): int
    {
        return $this->search_debounce;
    }

    /** @return int */
    public function get_search_min_length(): int
    {
        return $this->search_min_length;
    }

    /**
     * Get the list of searchable column names.
     *
     * @return string[]
     */
    public function get_searchable_columns(): array
    {
        $searchable = [];
        foreach ($this->each_column() as $name => $col) {
            if ($col->is_searchable()) {
                $searchable[] = $name;
            }
        }
        return $searchable;
    }

    /**
     * Get the responsive layout mode.
     *
     * Untyped on purpose: a `string|false` union would raise this package's
     * floor to PHP 8.0 for one return type. The docblock says the same thing.
     *
     * @return string|false
     */
    public function get_responsive_layout()
    {
        return $this->responsive_layout;
    }

    /**
     * Get the card layout configuration, or null if not enabled.
     *
     * @return array|null
     */
    public function get_card_layout(): ?array
    {
        return $this->card_layout;
    }

    /** @return array */
    public function get_extra(): array
    {
        return $this->extra;
    }

    /**
     * Check if this dataset uses tree display.
     *
     * @return bool
     */
    public function is_tree(): bool
    {
        return false;
    }

    // =========================================================================
    // Rendering
    // =========================================================================

    /**
     * Render the dataset configuration using a driver.
     *
     * Convenience method: shorthand for $driver->render($this).
     *
     * @param DriverInterface $driver
     * @return array The driver-specific configuration array
     */
    public function render(DriverInterface $driver): array
    {
        return $driver->render($this);
    }

    /**
     * Render the dataset configuration as JSON using a driver.
     *
     * @param DriverInterface $driver
     * @param int $flags JSON encoding flags
     * @return string
     */
    public function to_json(DriverInterface $driver, int $flags = 0): string
    {
        $json = json_encode($this->render($driver), $flags);
        if ($json === false) {
            throw new RuntimeException(
                'Failed to encode dataset config to JSON: ' . json_last_error_msg()
            );
        }
        return $json;
    }
}
