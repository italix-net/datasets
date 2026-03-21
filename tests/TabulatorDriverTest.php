<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests;

use Italix\DataSets\DataSet;
use Italix\DataSets\DataTree;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;
use Italix\DataSets\Tests\Fixtures\StubTableMeta;
use PHPUnit\Framework\TestCase;

class TabulatorDriverTest extends TestCase
{
    /** @var TabulatorDriver */
    private $driver;

    protected function setUp(): void
    {
        $this->driver = new TabulatorDriver();
    }

    public function test_get_name(): void
    {
        $this->assertSame('tabulator', $this->driver->get_name());
    }

    public function test_basic_render(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name', 'email']);

        $config = $this->driver->render($ds);

        $this->assertArrayHasKey('columns', $config);
        $this->assertCount(2, $config['columns']);
        $this->assertSame('name', $config['columns'][0]['field']);
        $this->assertSame('Name', $config['columns'][0]['title']);
        $this->assertSame('email', $config['columns'][1]['field']);
    }

    public function test_render_with_ajax(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->ajax_url('/api/users');

        $config = $this->driver->render($ds);

        $this->assertSame('/api/users', $config['ajaxURL']);
        $this->assertSame('remote', $config['sortMode']);
        $this->assertSame('remote', $config['filterMode']);
        $this->assertSame('remote', $config['paginationMode']);
    }

    public function test_render_ajax_post_method(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->ajax_url('/api/users')->ajax_method('POST');

        $config = $this->driver->render($ds);

        $this->assertSame('post', $config['ajaxConfig']);
    }

    public function test_render_ajax_params(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->ajax_url('/api/users')->ajax_params(['tenant' => 'acme']);

        $config = $this->driver->render($ds);

        $this->assertSame(['tenant' => 'acme'], $config['ajaxParams']);
    }

    public function test_render_pagination(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->per_page(50)->page_sizes([25, 50, 100]);

        $config = $this->driver->render($ds);

        $this->assertTrue($config['pagination']);
        $this->assertSame(50, $config['paginationSize']);
        $this->assertSame([25, 50, 100], $config['paginationSizeSelector']);
    }

    public function test_render_default_sort(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->default_sort('name', 'desc');

        $config = $this->driver->render($ds);

        $this->assertSame(
            [['column' => 'name', 'dir' => 'desc']],
            $config['initialSort']
        );
    }

    public function test_render_height(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->height('500px');

        $config = $this->driver->render($ds);

        $this->assertSame('500px', $config['height']);
    }

    public function test_render_no_height_when_unset(): void
    {
        $ds = new DataSet(StubTableMeta::users());

        $config = $this->driver->render($ds);

        $this->assertArrayNotHasKey('height', $config);
    }

    public function test_render_layout(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->layout('fitData');

        $config = $this->driver->render($ds);

        $this->assertSame('fitData', $config['layout']);
    }

    public function test_render_column_sortable(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name']);
        $ds->column('name')->sortable(true);

        $config = $this->driver->render($ds);

        $this->assertTrue($config['columns'][0]['headerSort']);
    }

    public function test_render_column_not_sortable(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name']);

        $config = $this->driver->render($ds);

        $this->assertFalse($config['columns'][0]['headerSort']);
    }

    public function test_render_column_width(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name']);
        $ds->column('name')->width('200px');

        $config = $this->driver->render($ds);

        $this->assertSame('200px', $config['columns'][0]['width']);
    }

    public function test_render_column_formatter(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['created_at']);
        $ds->column('created_at')->formatter('datetime', ['outputFormat' => 'YYYY-MM-DD']);

        $config = $this->driver->render($ds);

        $this->assertSame('datetime', $config['columns'][0]['formatter']);
        $this->assertSame(['outputFormat' => 'YYYY-MM-DD'], $config['columns'][0]['formatterParams']);
    }

    public function test_render_column_alignment(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['id']);
        $ds->column('id')->h_align('right')->header_align('center');

        $config = $this->driver->render($ds);

        $this->assertSame('right', $config['columns'][0]['hozAlign']);
        $this->assertSame('center', $config['columns'][0]['headerHozAlign']);
    }

    public function test_render_column_frozen(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['id']);
        $ds->column('id')->frozen(true);

        $config = $this->driver->render($ds);

        $this->assertTrue($config['columns'][0]['frozen']);
    }

    public function test_render_column_header_filter(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name']);
        $ds->column('name')->header_filter('input');

        $config = $this->driver->render($ds);

        $this->assertSame('input', $config['columns'][0]['headerFilter']);
    }

    public function test_render_column_hidden(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['id']);
        $ds->column('id')->visible(false);

        $config = $this->driver->render($ds);

        $this->assertFalse($config['columns'][0]['visible']);
    }

    public function test_render_column_extra(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name']);
        $ds->column('name')->extra(['editor' => 'input', 'validator' => 'required']);

        $config = $this->driver->render($ds);

        $this->assertSame('input', $config['columns'][0]['editor']);
        $this->assertSame('required', $config['columns'][0]['validator']);
    }

    public function test_render_sorter_inferred_from_type(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['id', 'name', 'created_at']);
        $ds->column('id')->sortable(true);
        $ds->column('name')->sortable(true);
        $ds->column('created_at')->sortable(true);

        $config = $this->driver->render($ds);

        $this->assertSame('number', $config['columns'][0]['sorter']);
        $this->assertSame('string', $config['columns'][1]['sorter']);
        $this->assertSame('datetime', $config['columns'][2]['sorter']);
    }

    // =========================================================================
    // Search / Filter Tests
    // =========================================================================

    public function test_searchable_column_gets_header_filter(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name', 'email']);
        $ds->column('name')->searchable(true);
        $ds->column('email')->searchable(true);

        $config = $this->driver->render($ds);

        $this->assertSame('input', $config['columns'][0]['headerFilter']);
        $this->assertTrue($config['columns'][0]['headerFilterLiveFilter']);
        $this->assertSame('input', $config['columns'][1]['headerFilter']);
    }

    public function test_explicit_header_filter_overrides_searchable(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name']);
        $ds->column('name')->searchable(true)->header_filter('select');

        $config = $this->driver->render($ds);

        $this->assertSame('select', $config['columns'][0]['headerFilter']);
        $this->assertArrayNotHasKey('headerFilterLiveFilter', $config['columns'][0]);
    }

    public function test_non_searchable_column_no_header_filter(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['id']);

        $config = $this->driver->render($ds);

        $this->assertArrayNotHasKey('headerFilter', $config['columns'][0]);
    }

    public function test_searchable_columns_metadata_in_config(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name', 'email', 'created_at']);
        $ds->column('name')->searchable(true);
        $ds->column('email')->searchable(true);

        $config = $this->driver->render($ds);

        $this->assertSame(['name', 'email'], $config['_searchableColumns']);
    }

    public function test_no_searchable_columns_no_metadata(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name']);

        $config = $this->driver->render($ds);

        $this->assertArrayNotHasKey('_searchableColumns', $config);
    }

    public function test_global_search_metadata(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->global_search(true, 'Search users...');
        $ds->search_debounce(500);
        $ds->search_min_length(3);

        $config = $this->driver->render($ds);

        $this->assertTrue($config['_globalSearch']);
        $this->assertSame('Search users...', $config['_searchPlaceholder']);
        $this->assertSame(3, $config['_searchMinLength']);
        $this->assertSame(500, $config['_searchDebounce']);
    }

    public function test_no_global_search_no_metadata(): void
    {
        $ds = new DataSet(StubTableMeta::users());

        $config = $this->driver->render($ds);

        $this->assertArrayNotHasKey('_globalSearch', $config);
    }

    public function test_custom_debounce_in_header_filter_delay(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->search_debounce(500);

        $config = $this->driver->render($ds);

        $this->assertSame(500, $config['headerFilterLiveFilterDelay']);
    }

    public function test_default_debounce_no_header_filter_delay(): void
    {
        $ds = new DataSet(StubTableMeta::users());

        $config = $this->driver->render($ds);

        $this->assertArrayNotHasKey('headerFilterLiveFilterDelay', $config);
    }

    // =========================================================================
    // render_script Tests
    // =========================================================================

    public function test_render_script_contains_tabulator_init(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name']);
        $ds->ajax_url('/api/users');

        $js = $this->driver->render_script($ds, '#my-table');

        $this->assertStringContainsString('new Tabulator("#my-table"', $js);
        $this->assertStringContainsString('ajaxRequestFunc', $js);
        $this->assertStringContainsString('/api/users', $js);
    }

    public function test_render_script_with_var_name(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name']);

        $js = $this->driver->render_script($ds, '#tbl', 'myTable');

        $this->assertStringContainsString('var myTable = new Tabulator', $js);
    }

    public function test_render_script_maps_pagination_params(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->ajax_url('/api/users');

        $js = $this->driver->render_script($ds, '#t');

        $this->assertStringContainsString('queryParams.page = params.page', $js);
        $this->assertStringContainsString('queryParams.per_page = params.size', $js);
    }

    public function test_render_script_maps_sort_params(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->ajax_url('/api/users');

        $js = $this->driver->render_script($ds, '#t');

        $this->assertStringContainsString('queryParams.sort = params.sorters[0].field', $js);
        $this->assertStringContainsString('queryParams.sort_dir = params.sorters[0].dir', $js);
    }

    public function test_render_script_maps_filter_params(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->ajax_url('/api/users');

        $js = $this->driver->render_script($ds, '#t');

        $this->assertStringContainsString('queryParams.filters', $js);
        $this->assertStringContainsString('params.filters[i].field', $js);
    }

    public function test_render_script_maps_search_params(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name', 'email']);
        $ds->column('name')->searchable(true);
        $ds->column('email')->searchable(true);
        $ds->ajax_url('/api/users');

        $js = $this->driver->render_script($ds, '#t');

        $this->assertStringContainsString('queryParams.search = _searchValue', $js);
        $this->assertStringContainsString('queryParams.search_columns = _searchableColumns', $js);
        $this->assertStringContainsString('"name","email"', $js);
    }

    public function test_render_script_parses_server_response(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->ajax_url('/api/users');

        $js = $this->driver->render_script($ds, '#t');

        $this->assertStringContainsString('data: data.data', $js);
        $this->assertStringContainsString('last_page: data.last_page', $js);
    }

    public function test_render_script_global_search_creates_input(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->global_search(true, 'Find something...');
        $ds->ajax_url('/api/users');

        $js = $this->driver->render_script($ds, '#t');

        $this->assertStringContainsString('italix-dataset-search', $js);
        $this->assertStringContainsString('Find something...', $js);
        $this->assertStringContainsString('Tabulator.findTable', $js);
    }

    public function test_render_script_no_global_search_no_input(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->ajax_url('/api/users');

        $js = $this->driver->render_script($ds, '#t');

        $this->assertStringNotContainsString('italix-dataset-search', $js);
    }

    public function test_render_script_debounce_and_min_length(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->search_debounce(500);
        $ds->search_min_length(3);
        $ds->ajax_url('/api/users');

        $js = $this->driver->render_script($ds, '#t');

        $this->assertStringContainsString('var _debounce = 500', $js);
        $this->assertStringContainsString('var _minLength = 3', $js);
    }

    public function test_render_extra_at_dataset_level(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->extra(['movableColumns' => true, 'responsiveLayout' => 'collapse']);

        $config = $this->driver->render($ds);

        $this->assertTrue($config['movableColumns']);
        $this->assertSame('collapse', $config['responsiveLayout']);
    }

    // =========================================================================
    // Tree Tests
    // =========================================================================

    public function test_render_tree(): void
    {
        $tree = new DataTree(StubTableMeta::categories());
        $tree->columns(['id', 'name', 'parent_id']);
        $tree->tree_config()
             ->parent_column('parent_id')
             ->id_column('id');

        $config = $this->driver->render($tree);

        $this->assertTrue($config['dataTree']);
        $this->assertSame('parent_id', $config['dataTreeParentField']);
        $this->assertSame('id', $config['index']);
        $this->assertFalse($config['dataTreeStartExpanded']);
    }

    public function test_render_tree_start_open(): void
    {
        $tree = new DataTree(StubTableMeta::categories());
        $tree->tree_config()->start_open(true);

        $config = $this->driver->render($tree);

        $this->assertTrue($config['dataTreeStartExpanded']);
    }

    public function test_render_tree_toggle_column(): void
    {
        $tree = new DataTree(StubTableMeta::categories());
        $tree->tree_config()->toggle_column('name');

        $config = $this->driver->render($tree);

        $this->assertSame('name', $config['dataTreeElementColumn']);
    }

    public function test_render_tree_no_toggle(): void
    {
        $tree = new DataTree(StubTableMeta::categories());
        $tree->tree_config()->show_toggle(false);

        $config = $this->driver->render($tree);

        $this->assertFalse($config['dataTreeElementColumn']);
    }

    public function test_render_tree_with_ajax(): void
    {
        $tree = new DataTree(StubTableMeta::categories());
        $tree->ajax_url('/api/categories');
        $tree->tree_config()->parent_column('parent_id');

        $config = $this->driver->render($tree);

        $this->assertTrue($config['dataTree']);
        $this->assertSame('/api/categories', $config['ajaxURL']);
        $this->assertSame('remote', $config['sortMode']);
    }

    // =========================================================================
    // Integration: DataSet::render() convenience
    // =========================================================================

    public function test_dataset_render_convenience(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name']);
        $ds->ajax_url('/api/users');

        $config = $ds->render($this->driver);

        $this->assertArrayHasKey('columns', $config);
        $this->assertSame('/api/users', $config['ajaxURL']);
    }

    public function test_dataset_to_json_convenience(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name']);

        $json = $ds->to_json($this->driver);
        $decoded = json_decode($json, true);

        $this->assertArrayHasKey('columns', $decoded);
        $this->assertCount(1, $decoded['columns']);
    }
}
