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
 * initialization options.
 *
 * Example output:
 *
 *     {
 *         "columns": [
 *             {"title": "Name", "field": "name", "sorter": "string", ...},
 *             {"title": "Email", "field": "email", "sorter": "string", ...}
 *         ],
 *         "ajaxURL": "/api/users",
 *         "pagination": true,
 *         "paginationMode": "remote",
 *         "paginationSize": 25,
 *         "sortMode": "remote",
 *         "filterMode": "remote",
 *         "layout": "fitColumns"
 *     }
 *
 * Usage:
 *
 *     $driver = new TabulatorDriver();
 *     $config = $driver->render($dataset);
 *
 *     // In your template:
 *     // <div id="my-table"></div>
 *     // <script>
 *     //   new Tabulator("#my-table", <?= json_encode($config) ?>);
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

        // Header filter
        if ($col->get_header_filter() !== null) {
            $def['headerFilter'] = $col->get_header_filter();
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

        // Map Tabulator's request params to our ServerSideRequest format
        $config['ajaxURLGenerator'] = '@@TABULATOR_URL_GENERATOR@@';

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
