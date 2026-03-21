<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests;

use Italix\DataSets\DataSet;
use Italix\DataSets\DataSetColumn;
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

    public function test_default_sort(): void
    {
        $ds = new DataSet(StubTableMeta::users());
        $ds->default_sort('name', 'desc');

        $this->assertSame('name', $ds->get_default_sort_column());
        $this->assertSame('desc', $ds->get_default_sort_direction());
    }

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
}
