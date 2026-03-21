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
