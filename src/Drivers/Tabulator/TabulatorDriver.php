<?php
/**
 * Italix DataSets - TabulatorDriver
 *
 * @package Italix\DataSets
 * @license LGPL-2.1-or-later
 */

declare(strict_types=1);

namespace Italix\DataSets\Drivers\Tabulator;

use Italix\DataSets\DataSet;
use Italix\DataSets\DataSetColumn;
use Italix\DataSets\DataTree;
use Italix\DataSets\Drivers\DriverInterface;

/**
 * Driver for the Tabulator JS library (https://tabulator.info).
 *
 * Translates DataSet/DataTree configuration into Tabulator's JSON
 * initialization options plus a JS bootstrap snippet that wires up
 * the AJAX request/response mapping for server-side processing.
 *
 * Usage:
 *
 *     $driver = new TabulatorDriver();
 *     $config = $driver->render($dataset);
 *     $js     = $driver->render_script($dataset, '#my-table');
 *
 *     // In your template:
 *     // <div id="my-table"></div>
 *     // <script><?= $js ?></script>
 *
 * Or manually with JSON config:
 *
 *     // <div id="my-table"></div>
 *     // <script>
 *     //   var config = <?= json_encode($config) ?>;
 *     //   config.ajaxRequestFunc = ItalixDataSets.tabulatorAjax(config);
 *     //   new Tabulator("#my-table", config);
 *     // </script>
 */
class TabulatorDriver implements DriverInterface
{
    /**
     * {@inheritdoc}
     */
    public function get_name(): string
    {
        return 'tabulator';
    }

    /**
     * {@inheritdoc}
     */
    public function render(DataSet $dataset): array
    {
        $config = [];

        // Columns
        $config['columns'] = $this->build_columns($dataset);

        // Layout
        $config['layout'] = $dataset->get_layout();

        // AJAX / server-side
        if ($dataset->get_ajax_url() !== null) {
            $config = array_merge($config, $this->build_ajax_config($dataset));
        }

        // Pagination
        $config = array_merge($config, $this->build_pagination_config($dataset));

        // Sorting defaults
        if ($dataset->get_default_sort_column() !== null) {
            $config['initialSort'] = [
                [
                    'column' => $dataset->get_default_sort_column(),
                    'dir' => $dataset->get_default_sort_direction(),
                ],
            ];
        }

        // Height
        if ($dataset->get_height() !== null) {
            $config['height'] = $dataset->get_height();
        }

        // Search / filtering metadata (used by the JS bootstrap)
        $config = array_merge($config, $this->build_search_config($dataset));

        // Tree mode
        if ($dataset->is_tree() && $dataset instanceof DataTree) {
            $config = array_merge($config, $this->build_tree_config($dataset));
        }

        // Extra driver-specific options (merged last so they can override)
        if (!empty($dataset->get_extra())) {
            $config = array_merge($config, $dataset->get_extra());
        }

        return $config;
    }

    /**
     * Render a complete JS snippet that initializes a Tabulator table.
     *
     * The snippet creates the Tabulator instance with proper AJAX request
     * mapping so that sort/page/search/filter params are sent in the format
     * expected by ServerSideRequest, and responses are parsed from the
     * ServerSideResponse format.
     *
     * @param DataSet $dataset
     * @param string $selector CSS selector for the container element (e.g. '#my-table')
     * @param string|null $var_name JS variable name for the table instance (null = no variable)
     * @return string JavaScript code
     */
    public function render_script(DataSet $dataset, string $selector, ?string $var_name = null): string
    {
        $config = $this->render($dataset);
        $config_json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $var_assignment = $var_name !== null ? "var {$var_name} = " : '';
        $searchable_columns = json_encode($dataset->get_searchable_columns());
        $debounce = $dataset->get_search_debounce();
        $min_length = $dataset->get_search_min_length();
        $has_search = $dataset->has_global_search() ? 'true' : 'false';

        $js = <<<JS
(function() {
    var config = {$config_json};

    var _searchValue = "";
    var _searchableColumns = {$searchable_columns};
    var _debounce = {$debounce};
    var _minLength = {$min_length};
    var _hasGlobalSearch = {$has_search};

    // Map Tabulator's AJAX request to ServerSideRequest format
    config.ajaxRequestFunc = function(url, config, params) {
        var queryParams = {};

        // Pagination
        if (params.page !== undefined) {
            queryParams.page = params.page;
        }
        if (params.size !== undefined) {
            queryParams.per_page = params.size;
        }

        // Sorting (Tabulator sends sorters as array)
        if (params.sorters && params.sorters.length > 0) {
            queryParams.sort = params.sorters[0].field;
            queryParams.sort_dir = params.sorters[0].dir;
        }

        // Column filters (from headerFilter)
        if (params.filters && params.filters.length > 0) {
            queryParams.filters = {};
            for (var i = 0; i < params.filters.length; i++) {
                queryParams.filters[params.filters[i].field] = params.filters[i].value;
            }
        }

        // Global search
        if (_searchValue && _searchValue.length >= _minLength) {
            queryParams.search = _searchValue;
            queryParams.search_columns = _searchableColumns;
        }

        // Build query string
        var qs = [];
        for (var key in queryParams) {
            if (queryParams[key] !== null && typeof queryParams[key] === "object") {
                for (var sub in queryParams[key]) {
                    qs.push(encodeURIComponent(key) + "[" + encodeURIComponent(sub) + "]=" + encodeURIComponent(queryParams[key][sub]));
                }
            } else if (Array.isArray(queryParams[key])) {
                for (var j = 0; j < queryParams[key].length; j++) {
                    qs.push(encodeURIComponent(key) + "[]=" + encodeURIComponent(queryParams[key][j]));
                }
            } else {
                qs.push(encodeURIComponent(key) + "=" + encodeURIComponent(queryParams[key]));
            }
        }

        var fetchUrl = url + (url.indexOf("?") >= 0 ? "&" : "?") + qs.join("&");

        return fetch(fetchUrl)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                // ServerSideResponse format -> Tabulator format
                return { data: data.data, last_page: data.last_page };
            });
    };

    {$var_assignment}new Tabulator("{$selector}", config);

JS;

        // Add global search input wiring if enabled
        if ($dataset->has_global_search()) {
            $placeholder = json_encode($dataset->get_search_placeholder() ?? 'Search...');
            $selector_escaped = json_encode($selector);

            $js .= <<<JS

    // Global search input with debounce
    var _searchTimer = null;
    var _tableEl = document.querySelector({$selector_escaped});
    if (_tableEl) {
        var searchInput = document.createElement("input");
        searchInput.type = "search";
        searchInput.placeholder = {$placeholder};
        searchInput.className = "italix-dataset-search";
        _tableEl.parentNode.insertBefore(searchInput, _tableEl);

        searchInput.addEventListener("input", function(e) {
            clearTimeout(_searchTimer);
            var val = e.target.value;
            _searchTimer = setTimeout(function() {
                _searchValue = val;
                if (val.length >= _minLength || val.length === 0) {
                    Tabulator.findTable({$selector_escaped})[0].setData();
                }
            }, _debounce);
        });
    }

JS;
        }

        $js .= "})();\n";

        return $js;
    }

    /**
     * Build the Tabulator column definitions array.
     *
     * @param DataSet $dataset
     * @return array
     */
    private function build_columns(DataSet $dataset): array
    {
        $columns = [];

        foreach ($dataset->each_column() as $name => $col) {
            $def = $this->build_single_column($col);
            $columns[] = $def;
        }

        return $columns;
    }

    /**
     * Build a single Tabulator column definition.
     *
     * @param DataSetColumn $col
     * @return array
     */
    private function build_single_column(DataSetColumn $col): array
    {
        $def = [
            'title' => $col->get_label(),
            'field' => $col->get_name(),
        ];

        // Sorter
        if ($col->is_sortable()) {
            $def['headerSort'] = true;
            $sorter = $this->infer_sorter($col);
            if ($sorter !== null) {
                $def['sorter'] = $sorter;
            }
        } else {
            $def['headerSort'] = false;
        }

        // Visibility
        if (!$col->is_visible()) {
            $def['visible'] = false;
        }

        // Width
        if ($col->get_width() !== null) {
            $def['width'] = $col->get_width();
        }
        if ($col->get_min_width() !== null) {
            $def['minWidth'] = (int)$col->get_min_width();
        }

        // Formatter
        if ($col->get_formatter() !== null) {
            $def['formatter'] = $col->get_formatter();
            if (!empty($col->get_formatter_params())) {
                $def['formatterParams'] = $col->get_formatter_params();
            }
        }

        // Alignment
        if ($col->get_h_align() !== null) {
            $def['hozAlign'] = $col->get_h_align();
        }
        if ($col->get_header_align() !== null) {
            $def['headerHozAlign'] = $col->get_header_align();
        }

        // Frozen
        if ($col->is_frozen()) {
            $def['frozen'] = true;
        }

        // CSS class
        if ($col->get_css_class() !== null) {
            $def['cssClass'] = $col->get_css_class();
        }

        // Header filter (column-level filtering)
        if ($col->get_header_filter() !== null) {
            $def['headerFilter'] = $col->get_header_filter();
        } elseif ($col->is_searchable()) {
            // Auto-add header filter for searchable columns
            $def['headerFilter'] = 'input';
            $def['headerFilterLiveFilter'] = true;
        }

        // Extra driver-specific options
        if (!empty($col->get_extra())) {
            $def = array_merge($def, $col->get_extra());
        }

        return $def;
    }

    /**
     * Build AJAX configuration for server-side data loading.
     *
     * @param DataSet $dataset
     * @return array
     */
    private function build_ajax_config(DataSet $dataset): array
    {
        $config = [
            'ajaxURL' => $dataset->get_ajax_url(),
            'sortMode' => 'remote',
            'filterMode' => 'remote',
        ];

        if ($dataset->get_ajax_method() !== 'GET') {
            $config['ajaxConfig'] = strtolower($dataset->get_ajax_method());
        }

        if (!empty($dataset->get_ajax_params())) {
            $config['ajaxParams'] = $dataset->get_ajax_params();
        }

        return $config;
    }

    /**
     * Build search/filter configuration.
     *
     * @param DataSet $dataset
     * @return array
     */
    private function build_search_config(DataSet $dataset): array
    {
        $config = [];

        // Debounce for header filters
        if ($dataset->get_search_debounce() !== 300) {
            $config['headerFilterLiveFilterDelay'] = $dataset->get_search_debounce();
        }

        // Store searchable columns metadata for the JS bootstrap
        $searchable = $dataset->get_searchable_columns();
        if (!empty($searchable)) {
            $config['_searchableColumns'] = $searchable;
        }

        // Global search metadata
        if ($dataset->has_global_search()) {
            $config['_globalSearch'] = true;
            if ($dataset->get_search_placeholder() !== null) {
                $config['_searchPlaceholder'] = $dataset->get_search_placeholder();
            }
            $config['_searchMinLength'] = $dataset->get_search_min_length();
            $config['_searchDebounce'] = $dataset->get_search_debounce();
        }

        return $config;
    }

    /**
     * Build pagination configuration.
     *
     * @param DataSet $dataset
     * @return array
     */
    private function build_pagination_config(DataSet $dataset): array
    {
        $config = [
            'pagination' => true,
            'paginationSize' => $dataset->get_per_page(),
            'paginationSizeSelector' => $dataset->get_page_sizes(),
        ];

        // Remote pagination when using AJAX
        if ($dataset->get_ajax_url() !== null) {
            $config['paginationMode'] = 'remote';
        }

        return $config;
    }

    /**
     * Build tree-specific configuration for DataTree.
     *
     * @param DataTree $tree
     * @return array
     */
    private function build_tree_config(DataTree $tree): array
    {
        $tc = $tree->get_tree_config();

        $config = [
            'dataTree' => true,
            'dataTreeParentField' => $tc->get_parent_column(),
            'dataTreeStartExpanded' => $tc->is_start_open(),
        ];

        if (!$tc->is_show_toggle()) {
            $config['dataTreeElementColumn'] = false;
        } elseif ($tc->get_toggle_column() !== null) {
            $config['dataTreeElementColumn'] = $tc->get_toggle_column();
        }

        // Tabulator uses the row index field to match parent references
        $config['index'] = $tc->get_id_column();

        return $config;
    }

    /**
     * Infer the Tabulator sorter type from column metadata.
     *
     * @param DataSetColumn $col
     * @return string|null
     */
    private function infer_sorter(DataSetColumn $col): ?string
    {
        $type = strtoupper($col->column()->get_type());

        if (in_array($type, ['INTEGER', 'BIGINT', 'SMALLINT', 'SERIAL', 'BIGSERIAL'], true)) {
            return 'number';
        }
        if (in_array($type, ['DECIMAL', 'NUMERIC', 'REAL', 'DOUBLE PRECISION', 'FLOAT'], true)) {
            return 'number';
        }
        if (in_array($type, ['DATE', 'DATETIME', 'TIMESTAMP'], true)) {
            return 'datetime';
        }
        if ($type === 'BOOLEAN') {
            return 'boolean';
        }
        if ($type === 'TIME') {
            return 'time';
        }

        return 'string';
    }
}
