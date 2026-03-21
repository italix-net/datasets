<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests;

use Italix\DataSets\ActionColumn;
use Italix\DataSets\DataSet;
use Italix\DataSets\DataSetColumn;
use Italix\DataSets\Toolbar;
use Italix\DataSets\Tests\Fixtures\StubTableMeta;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DataSetTest extends TestCase
{
    public function test_creates_from_table_meta(): void
    {
        $table = StubTableMeta::users();
        $ds = new DataSet($table);

        $this->assertSame($table, $ds->source());
        $this->assertFalse($ds->is_tree());
    }

    public function test_columns_sets_visible_order(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['email', 'name']);

        $this->assertSame(['email', 'name'], $ds->get_visible_columns());
    }

    public function test_columns_throws_on_unknown_column(): void
    {
        $ds = new DataSet(StubTableMeta::users());

        $this->expectException(InvalidArgumentException::class);
        $ds->columns(['nonexistent']);
    }

    public function test_column_returns_data_set_column(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $col = $ds->column('name');

        $this->assertInstanceOf(DataSetColumn::class, $col);
        $this->assertSame('name', $col->get_name());
    }

    public function test_column_returns_same_instance(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $col1 = $ds->column('name');
        $col2 = $ds->column('name');

        $this->assertSame($col1, $col2);
    }

    public function test_column_throws_on_unknown(): void
    {
        $ds = new DataSet(StubTableMeta::users());

        $this->expectException(InvalidArgumentException::class);
        $ds->column('nonexistent');
    }

    public function test_each_column_iterates_visible(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['name', 'email']);

        $names = [];
        foreach ($ds->each_column() as $name => $col) {
            $names[] = $name;
            $this->assertInstanceOf(DataSetColumn::class, $col);
        }

        $this->assertSame(['name', 'email'], $names);
    }

    public function test_each_column_iterates_all_when_no_selection(): void
    {
        $ds = new DataSet(StubTableMeta::users());

        $names = [];
        foreach ($ds->each_column() as $name => $col) {
            $names[] = $name;
        }

        $this->assertSame(['id', 'name', 'email', 'created_at'], $names);
    }

    public function test_ajax_configuration(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->ajax_url('/api/users')
           ->ajax_method('POST')
           ->ajax_params(['tenant' => 'acme']);

        $this->assertSame('/api/users', $ds->get_ajax_url());
        $this->assertSame('POST', $ds->get_ajax_method());
        $this->assertSame(['tenant' => 'acme'], $ds->get_ajax_params());
    }

    public function test_pagination_defaults(): void
    {
        $ds = new DataSet(StubTableMeta::users());

        $this->assertSame(25, $ds->get_per_page());
        $this->assertSame([10, 25, 50, 100], $ds->get_page_sizes());
    }

    public function test_pagination_configuration(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->per_page(50)->page_sizes([25, 50, 100, 200]);

        $this->assertSame(50, $ds->get_per_page());
        $this->assertSame([25, 50, 100, 200], $ds->get_page_sizes());
    }

    // =========================================================================
    // Multi-column Sorting
    // =========================================================================

    public function test_default_sort_single(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->default_sort('name', 'desc');

        $this->assertSame('name', $ds->get_default_sort_column());
        $this->assertSame('desc', $ds->get_default_sort_direction());
        $this->assertCount(1, $ds->get_default_sorts());
    }

    public function test_default_sort_multi(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->default_sort('name', 'asc');
        $ds->default_sort('created_at', 'desc');

        $sorts = $ds->get_default_sorts();
        $this->assertCount(2, $sorts);
        $this->assertSame('name', $sorts[0]['column']);
        $this->assertSame('asc', $sorts[0]['direction']);
        $this->assertSame('created_at', $sorts[1]['column']);
        $this->assertSame('desc', $sorts[1]['direction']);

        // Backwards compat: returns first sort
        $this->assertSame('name', $ds->get_default_sort_column());
        $this->assertSame('asc', $ds->get_default_sort_direction());
    }

    public function test_no_default_sort(): void
    {
        $ds = new DataSet(StubTableMeta::users());

        $this->assertNull($ds->get_default_sort_column());
        $this->assertSame('asc', $ds->get_default_sort_direction());
        $this->assertSame([], $ds->get_default_sorts());
    }

    // =========================================================================
    // Display
    // =========================================================================

    public function test_display_options(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->id('users-table')
           ->css_class('my-table')
           ->height('500px')
           ->layout('fitData')
           ->global_search(true, 'Search users...');

        $this->assertSame('users-table', $ds->get_id());
        $this->assertSame('my-table', $ds->get_css_class());
        $this->assertSame('500px', $ds->get_height());
        $this->assertSame('fitData', $ds->get_layout());
        $this->assertTrue($ds->has_global_search());
        $this->assertSame('Search users...', $ds->get_search_placeholder());
    }

    public function test_extra_options(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->extra(['movableColumns' => true]);
        $ds->extra(['resizableRows' => true]);

        $this->assertSame(
            ['movableColumns' => true, 'resizableRows' => true],
            $ds->get_extra()
        );
    }

    // =========================================================================
    // Search
    // =========================================================================

    public function test_search_debounce_default(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $this->assertSame(300, $ds->get_search_debounce());
    }

    public function test_search_debounce_custom(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->search_debounce(500);
        $this->assertSame(500, $ds->get_search_debounce());
    }

    public function test_search_min_length_default(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $this->assertSame(1, $ds->get_search_min_length());
    }

    public function test_search_min_length_custom(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->search_min_length(3);
        $this->assertSame(3, $ds->get_search_min_length());
    }

    public function test_get_searchable_columns(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->columns(['id', 'name', 'email', 'created_at']);
        $ds->column('name')->searchable(true);
        $ds->column('email')->searchable(true);

        $this->assertSame(['name', 'email'], $ds->get_searchable_columns());
    }

    public function test_get_searchable_columns_empty(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $this->assertSame([], $ds->get_searchable_columns());
    }

    // =========================================================================
    // Action Column
    // =========================================================================

    public function test_action_column(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ac = $ds->action_column();

        $this->assertInstanceOf(ActionColumn::class, $ac);
        $this->assertSame($ac, $ds->action_column()); // same instance
    }

    public function test_has_action_column(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $this->assertFalse($ds->has_action_column());

        $ds->action_column()->button('edit', 'Edit');
        $this->assertTrue($ds->has_action_column());
    }

    public function test_get_action_column_null_when_not_set(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $this->assertNull($ds->get_action_column());
    }

    // =========================================================================
    // Toolbar
    // =========================================================================

    public function test_toolbar(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $tb = $ds->toolbar();

        $this->assertInstanceOf(Toolbar::class, $tb);
        $this->assertSame($tb, $ds->toolbar()); // same instance
    }

    public function test_has_toolbar(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $this->assertFalse($ds->has_toolbar());

        $ds->toolbar()->button('add', 'Add', 'none');
        $this->assertTrue($ds->has_toolbar());
    }

    public function test_get_toolbar_null_when_not_set(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $this->assertNull($ds->get_toolbar());
    }

    // =========================================================================
    // Row Selection
    // =========================================================================

    public function test_selectable_default(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $this->assertFalse($ds->is_selectable());
        $this->assertFalse($ds->get_selectable());
    }

    public function test_selectable_checkbox(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->selectable(true);

        $this->assertTrue($ds->is_selectable());
        $this->assertTrue($ds->get_selectable());
    }

    public function test_selectable_highlight(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->selectable('highlight');

        $this->assertTrue($ds->is_selectable());
        $this->assertSame('highlight', $ds->get_selectable());
    }

    // =========================================================================
    // Events
    // =========================================================================

    public function test_on_event(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->on('row_click', 'onRowClick');
        $ds->on('edit', 'onEditRow');

        $events = $ds->get_events();
        $this->assertSame('onRowClick', $events['row_click']);
        $this->assertSame('onEditRow', $events['edit']);
    }

    public function test_get_event_callback(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->on('edit', 'onEdit');

        $this->assertSame('onEdit', $ds->get_event_callback('edit'));
        $this->assertNull($ds->get_event_callback('nonexistent'));
    }

    public function test_events_empty_by_default(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $this->assertSame([], $ds->get_events());
    }
}
