<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets - TabulatorDriver
 *
 * @package Italix\DataSets
 * @license MPL-2.0
 */

declare(strict_types=1);

namespace Italix\DataSets\Drivers\Tabulator;

use Italix\DataSets\ActionButton;
use Italix\DataSets\ActionColumn;
use Italix\DataSets\DataSet;
use Italix\DataSets\DataSetColumn;
use Italix\DataSets\DataTree;
use Italix\DataSets\Drivers\DriverInterface;
use Italix\DataSets\Toolbar;
use Italix\DataSets\ToolbarButton;

/**
 * Driver for the Tabulator JS library (https://tabulator.info).
 *
 * Translates DataSet/DataTree configuration into Tabulator's JSON
 * initialization options plus a JS bootstrap snippet that wires up
 * AJAX request/response mapping, action buttons, toolbar, row
 * selection, and event callbacks.
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

        // Columns (data columns + optional action column)
        $config['columns'] = $this->build_columns($dataset);

        // Layout
        $config['layout'] = $dataset->get_layout();

        // AJAX / server-side
        if ($dataset->get_ajax_url() !== null) {
            $config = array_merge($config, $this->build_ajax_config($dataset));
        }

        // Pagination
        $config = array_merge($config, $this->build_pagination_config($dataset));

        // Sorting defaults (multi-column)
        $sorts = $dataset->get_default_sorts();
        if (!empty($sorts)) {
            $config['initialSort'] = array_map(function (array $sort) {
                return ['column' => $sort['column'], 'dir' => $sort['direction']];
            }, $sorts);
        }

        // Height
        if ($dataset->get_height() !== null) {
            $config['height'] = $dataset->get_height();
        }

        // Row selection
        if ($dataset->is_selectable()) {
            $config = array_merge($config, $this->build_selection_config($dataset));
        }

        // Search / filtering metadata
        $config = array_merge($config, $this->build_search_config($dataset));

        // Tree mode
        if ($dataset->is_tree() && $dataset instanceof DataTree) {
            $config = array_merge($config, $this->build_tree_config($dataset));
        }

        // Toolbar metadata (for render_script)
        if ($dataset->has_toolbar()) {
            $config['_toolbar'] = $dataset->get_toolbar()->to_array();
        }

        // Events metadata (for render_script)
        if (!empty($dataset->get_events())) {
            $config['_events'] = $dataset->get_events();
        }

        // Action column metadata (for render_script)
        if ($dataset->has_action_column()) {
            $config['_actionColumn'] = $dataset->get_action_column()->to_array();
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
     * Includes AJAX request mapping, action buttons, toolbar, row
     * selection, and event callbacks.
     *
     * @param DataSet $dataset
     * @param string $selector CSS selector for the container element
     * @param string|null $var_name JS variable name for the table instance
     * @return string JavaScript code
     */
    public function render_script(DataSet $dataset, string $selector, ?string $var_name = null): string
    {
        $config = $this->render($dataset);
        $config_json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $selector_escaped = json_encode($selector);

        $var_assignment = $var_name !== null ? "var {$var_name} = " : '';
        $table_var = $var_name !== null ? $var_name : '_table';
        $searchable_columns = json_encode($dataset->get_searchable_columns());
        $debounce = $dataset->get_search_debounce();
        $min_length = $dataset->get_search_min_length();
        $events = $dataset->get_events();
        $events_json = json_encode($events);

        $js = "(function() {\n";
        $js .= "    var config = {$config_json};\n";
        $js .= "    var _searchValue = \"\";\n";
        $js .= "    var _searchableColumns = {$searchable_columns};\n";
        $js .= "    var _debounce = {$debounce};\n";
        $js .= "    var _minLength = {$min_length};\n";
        $js .= "    var _events = {$events_json};\n\n";

        // AJAX request function
        $js .= $this->build_ajax_request_func($dataset);

        // Action column formatter (JS function, cannot be in JSON)
        if ($dataset->has_action_column()) {
            $js .= $this->build_action_column_js($dataset);
        }

        // Event wiring (row events)
        $js .= $this->build_row_event_js($dataset);

        // Create the Tabulator instance
        if ($var_name !== null) {
            $js .= "    var {$table_var} = new Tabulator({$selector_escaped}, config);\n\n";
        } else {
            $js .= "    var {$table_var} = new Tabulator({$selector_escaped}, config);\n\n";
        }

        // Toolbar
        if ($dataset->has_toolbar()) {
            $js .= $this->build_toolbar_js($dataset, $selector, $table_var);
        }

        // Global search input
        if ($dataset->has_global_search()) {
            $js .= $this->build_global_search_js($dataset, $selector, $table_var);
        }

        $js .= "})();\n";

        return $js;
    }

    // =========================================================================
    // Column Building
    // =========================================================================

    /**
     * Build the Tabulator column definitions array.
     *
     * @param DataSet $dataset
     * @return array
     */
    private function build_columns(DataSet $dataset): array
    {
        $columns = [];

        // Selection checkbox column
        if ($dataset->is_selectable() && $dataset->get_selectable() === true) {
            $columns[] = [
                'formatter' => 'rowSelection',
                'titleFormatter' => 'rowSelection',
                'headerSort' => false,
                'width' => '40px',
                'hozAlign' => 'center',
                'frozen' => true,
                'cellClick' => '@@ROW_TOGGLE_SELECT@@',
            ];
        }

        // Action column at start
        if ($dataset->has_action_column()
            && $dataset->get_action_column()->get_position() === 'start') {
            $columns[] = $this->build_action_column_def($dataset->get_action_column());
        }

        // Data columns
        foreach ($dataset->each_column() as $name => $col) {
            $columns[] = $this->build_single_column($col);
        }

        // Action column at end (default)
        if ($dataset->has_action_column()
            && $dataset->get_action_column()->get_position() === 'end') {
            $columns[] = $this->build_action_column_def($dataset->get_action_column());
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
     * Build the action column definition (placeholder — the formatter
     * is set up as a JS function in render_script).
     *
     * @param ActionColumn $action_col
     * @return array
     */
    private function build_action_column_def(ActionColumn $action_col): array
    {
        $def = [
            'title' => $action_col->get_label(),
            'field' => '_actions',
            'headerSort' => false,
            'formatter' => '@@ACTION_FORMATTER@@',
        ];

        if ($action_col->get_width() !== null) {
            $def['width'] = $action_col->get_width();
        }
        if ($action_col->is_frozen()) {
            $def['frozen'] = true;
        }
        if ($action_col->get_css_class() !== null) {
            $def['cssClass'] = $action_col->get_css_class();
        }

        $def['hozAlign'] = 'center';
        $def['headerHozAlign'] = 'center';

        return $def;
    }

    // =========================================================================
    // Row Selection
    // =========================================================================

    /**
     * Build row selection configuration.
     *
     * @param DataSet $dataset
     * @return array
     */
    private function build_selection_config(DataSet $dataset): array
    {
        $mode = $dataset->get_selectable();
        $config = ['selectable' => true];

        if ($mode === 'highlight') {
            $config['selectableRangeMode'] = 'click';
        }

        return $config;
    }

    // =========================================================================
    // AJAX Configuration
    // =========================================================================

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

    // =========================================================================
    // Search Configuration
    // =========================================================================

    /**
     * Build search/filter configuration.
     *
     * @param DataSet $dataset
     * @return array
     */
    private function build_search_config(DataSet $dataset): array
    {
        $config = [];

        if ($dataset->get_search_debounce() !== 300) {
            $config['headerFilterLiveFilterDelay'] = $dataset->get_search_debounce();
        }

        $searchable = $dataset->get_searchable_columns();
        if (!empty($searchable)) {
            $config['_searchableColumns'] = $searchable;
        }

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

    // =========================================================================
    // Pagination Configuration
    // =========================================================================

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

        if ($dataset->get_ajax_url() !== null) {
            $config['paginationMode'] = 'remote';
        }

        return $config;
    }

    // =========================================================================
    // Tree Configuration
    // =========================================================================

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

        $config['index'] = $tc->get_id_column();

        return $config;
    }

    // =========================================================================
    // JS Bootstrap: AJAX Request Function
    // =========================================================================

    /**
     * Build the ajaxRequestFunc JS code with multi-sort support.
     *
     * @param DataSet $dataset
     * @return string
     */
    private function build_ajax_request_func(DataSet $dataset): string
    {
        if ($dataset->get_ajax_url() === null) {
            return '';
        }

        return <<<'JS'
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

        // Sorting (multi-column: sends as sorts[] array)
        if (params.sorters && params.sorters.length > 0) {
            // Backwards compat: primary sort as flat params
            queryParams.sort = params.sorters[0].field;
            queryParams.sort_dir = params.sorters[0].dir;
            // Multi-sort: all sorters
            if (params.sorters.length > 1) {
                queryParams.sorts = params.sorters.map(function(s) {
                    return { field: s.field, dir: s.dir };
                });
            }
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
            if (key === "sorts") {
                for (var si = 0; si < queryParams.sorts.length; si++) {
                    qs.push("sorts[" + si + "][field]=" + encodeURIComponent(queryParams.sorts[si].field));
                    qs.push("sorts[" + si + "][dir]=" + encodeURIComponent(queryParams.sorts[si].dir));
                }
            } else if (queryParams[key] !== null && typeof queryParams[key] === "object" && !Array.isArray(queryParams[key])) {
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
                return { data: data.data, last_page: data.last_page };
            });
    };

JS;
    }

    // =========================================================================
    // JS Bootstrap: Action Column
    // =========================================================================

    /**
     * Build the JS code for the action column formatter and click handlers.
     *
     * @param DataSet $dataset
     * @return string
     */
    private function build_action_column_js(DataSet $dataset): string
    {
        $action_col = $dataset->get_action_column();
        if ($action_col === null) {
            return '';
        }

        $buttons = $action_col->get_buttons();
        $events = $dataset->get_events();

        // Build the HTML for each button
        $btn_html_parts = [];
        foreach ($buttons as $btn) {
            $css = $btn->get_css_class() ?? 'italix-action-btn';
            $title = $btn->get_title() !== null ? ' title="' . $this->escape_js_attr($btn->get_title()) . '"' : '';
            $icon_html = $btn->get_icon() !== null ? '<i class="' . $this->escape_js_attr($btn->get_icon()) . '"></i> ' : '';
            $label = $this->escape_js_html($btn->get_label());
            $btn_html_parts[] = '<button class="' . $this->escape_js_attr($css) . '" data-action="' . $this->escape_js_attr($btn->get_name()) . '"' . $title . '>' . $icon_html . $label . '</button>';
        }

        $btn_html = implode(' ', $btn_html_parts);
        $btn_html_escaped = json_encode($btn_html);

        // Build confirm map
        $confirms = [];
        foreach ($buttons as $btn) {
            if ($btn->get_confirm() !== null) {
                $confirms[$btn->get_name()] = $btn->get_confirm();
            }
        }
        $confirms_json = json_encode((object)$confirms);

        $js = <<<JS
    // Action column formatter
    var _actionConfirms = {$confirms_json};
    var _actionFormatter = function(cell) {
        return {$btn_html_escaped};
    };

    // Replace the placeholder formatter with the real function
    for (var ci = 0; ci < config.columns.length; ci++) {
        if (config.columns[ci].formatter === "@@ACTION_FORMATTER@@") {
            config.columns[ci].formatter = _actionFormatter;
            config.columns[ci].cellClick = function(e, cell) {
                var btn = e.target.closest("[data-action]");
                if (!btn) return;
                var action = btn.getAttribute("data-action");
                var rowData = cell.getRow().getData();

                if (_actionConfirms[action] && !confirm(_actionConfirms[action])) {
                    return;
                }

                if (_events[action] && typeof window[_events[action]] === "function") {
                    window[_events[action]](rowData, cell.getRow());
                }
            };
        }
    }

JS;

        return $js;
    }

    // =========================================================================
    // JS Bootstrap: Row Events
    // =========================================================================

    /**
     * Build JS code to wire row-level events.
     *
     * @param DataSet $dataset
     * @return string
     */
    private function build_row_event_js(DataSet $dataset): string
    {
        $events = $dataset->get_events();
        $js = '';

        $row_event_map = [
            'row_click' => 'rowClick',
            'row_dbl_click' => 'rowDblClick',
            'row_context' => 'rowContext',
            'row_selected' => 'rowSelected',
            'row_deselected' => 'rowDeselected',
        ];

        foreach ($row_event_map as $ds_event => $tab_event) {
            if (isset($events[$ds_event])) {
                $callback = json_encode($events[$ds_event]);
                $js .= <<<JS
    config.{$tab_event} = function(e, row) {
        var cb = {$callback};
        if (typeof window[cb] === "function") {
            window[cb](row.getData(), row, e);
        }
    };

JS;
            }
        }

        // Selection checkbox column: wire the cellClick handler
        if ($dataset->is_selectable() && $dataset->get_selectable() === true) {
            $js .= <<<'JS'
    // Wire checkbox selection toggle
    for (var si = 0; si < config.columns.length; si++) {
        if (config.columns[si].cellClick === "@@ROW_TOGGLE_SELECT@@") {
            config.columns[si].cellClick = function(e, cell) {
                cell.getRow().toggleSelect();
            };
        }
    }

JS;
        }

        return $js;
    }

    // =========================================================================
    // JS Bootstrap: Toolbar
    // =========================================================================

    /**
     * Build JS code to create the toolbar DOM and wire button click handlers.
     *
     * @param DataSet $dataset
     * @param string $selector
     * @param string $table_var
     * @return string
     */
    private function build_toolbar_js(DataSet $dataset, string $selector, string $table_var): string
    {
        $toolbar = $dataset->get_toolbar();
        if ($toolbar === null) {
            return '';
        }

        $selector_escaped = json_encode($selector);
        $events = $dataset->get_events();
        $position = $toolbar->get_position();
        $toolbar_css = $toolbar->get_css_class() ?? 'italix-dataset-toolbar';

        // Build button HTML
        $btn_html_parts = [];
        foreach ($toolbar->get_buttons() as $btn) {
            $css = $btn->get_css_class() ?? 'italix-toolbar-btn';
            $icon_html = $btn->get_icon() !== null ? '<i class="' . $this->escape_js_attr($btn->get_icon()) . '"></i> ' : '';
            $title = $btn->get_title() !== null ? ' title="' . $this->escape_js_attr($btn->get_title()) . '"' : '';
            $label = $this->escape_js_html($btn->get_label());
            $btn_html_parts[] = '<button class="' . $this->escape_js_attr($css)
                . '" data-toolbar-action="' . $this->escape_js_attr($btn->get_name())
                . '" data-scope="' . $this->escape_js_attr($btn->get_scope())
                . '"' . $title . '>' . $icon_html . $label . '</button>';
        }

        $toolbar_html = '<div class="' . $this->escape_js_attr($toolbar_css) . '">'
            . implode(' ', $btn_html_parts)
            . '</div>';
        $toolbar_html_escaped = json_encode($toolbar_html);

        // Build confirms map for toolbar buttons
        $confirms = [];
        foreach ($toolbar->get_buttons() as $btn) {
            if ($btn->get_confirm() !== null) {
                $confirms[$btn->get_name()] = $btn->get_confirm();
            }
        }
        $confirms_json = json_encode((object)$confirms);

        $insert_before = ($position === 'top' || $position === 'both') ? 'true' : 'false';
        $insert_after = ($position === 'bottom' || $position === 'both') ? 'true' : 'false';

        $js = <<<JS
    // Toolbar
    var _toolbarConfirms = {$confirms_json};
    var _toolbarHtml = {$toolbar_html_escaped};
    var _tableEl = document.querySelector({$selector_escaped});

    function _createToolbar(refEl, insertBefore) {
        var div = document.createElement("div");
        div.innerHTML = _toolbarHtml;
        var toolbar = div.firstChild;

        if (insertBefore) {
            refEl.parentNode.insertBefore(toolbar, refEl);
        } else {
            refEl.parentNode.insertBefore(toolbar, refEl.nextSibling);
        }

        toolbar.addEventListener("click", function(e) {
            var btn = e.target.closest("[data-toolbar-action]");
            if (!btn) return;

            var action = btn.getAttribute("data-toolbar-action");
            var scope = btn.getAttribute("data-scope");

            if (_toolbarConfirms[action] && !confirm(_toolbarConfirms[action])) {
                return;
            }

            var callbackName = _events[action];
            if (!callbackName || typeof window[callbackName] !== "function") return;

            if (scope === "selected") {
                var selected = {$table_var}.getSelectedData();
                window[callbackName](selected);
            } else if (scope === "all") {
                var all = {$table_var}.getData();
                window[callbackName](all);
            } else {
                window[callbackName]();
            }
        });
    }

    if (_tableEl) {
        if ({$insert_before}) _createToolbar(_tableEl, true);
        if ({$insert_after}) _createToolbar(_tableEl, false);
    }

JS;

        return $js;
    }

    // =========================================================================
    // JS Bootstrap: Global Search
    // =========================================================================

    /**
     * Build JS code for the global search input with debounce.
     *
     * @param DataSet $dataset
     * @param string $selector
     * @param string $table_var
     * @return string
     */
    private function build_global_search_js(DataSet $dataset, string $selector, string $table_var): string
    {
        $selector_escaped = json_encode($selector);
        $placeholder = json_encode($dataset->get_search_placeholder() ?? 'Search...');

        return <<<JS
    // Global search input with debounce
    var _searchTimer = null;
    var _searchEl = document.querySelector({$selector_escaped});
    if (_searchEl) {
        var searchInput = document.createElement("input");
        searchInput.type = "search";
        searchInput.placeholder = {$placeholder};
        searchInput.className = "italix-dataset-search";
        _searchEl.parentNode.insertBefore(searchInput, _searchEl);

        searchInput.addEventListener("input", function(e) {
            clearTimeout(_searchTimer);
            var val = e.target.value;
            _searchTimer = setTimeout(function() {
                _searchValue = val;
                if (val.length >= _minLength || val.length === 0) {
                    {$table_var}.setData();
                }
            }, _debounce);
        });
    }

JS;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

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

    /**
     * Escape a string for safe use inside a JS HTML attribute.
     *
     * @param string $value
     * @return string
     */
    private function escape_js_attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Escape a string for safe use inside JS HTML content.
     *
     * @param string $value
     * @return string
     */
    private function escape_js_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
