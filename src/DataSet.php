<?php
/**
 * Italix DataSets - DataSet
 *
 * @package Italix\DataSets
 * @license LGPL-2.1-or-later
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
 *     // Render with a driver
 *     $driver = new TabulatorDriver();
 *     $config = $driver->render($ds);
 *     // -> JSON config for the JS library
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

    /** @var string|null Default sort column */
    private $default_sort_column = null;

    /** @var string Default sort direction */
    private $default_sort_direction = 'asc';

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
            : array_keys(iterator_to_array($this->source->describe_columns()));

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

        return array_keys(iterator_to_array($this->source->describe_columns()));
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
     * Set the default sort column and direction.
     *
     * @param string $column
     * @param string $direction 'asc' or 'desc'
     * @return self
     */
    public function default_sort(string $column, string $direction = 'asc'): self
    {
        $this->default_sort_column = $column;
        $this->default_sort_direction = strtolower($direction);
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

    /** @return string|null */
    public function get_default_sort_column(): ?string
    {
        return $this->default_sort_column;
    }

    /** @return string */
    public function get_default_sort_direction(): string
    {
        return $this->default_sort_direction;
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
