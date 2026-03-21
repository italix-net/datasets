<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests;

use Italix\DataSets\DataSetColumn;
use Italix\DataSets\Tests\Fixtures\StubColumnMeta;
use PHPUnit\Framework\TestCase;

class DataSetColumnTest extends TestCase
{
    private function make_column(string $name = 'email', string $type = 'VARCHAR'): DataSetColumn
    {
        return new DataSetColumn(new StubColumnMeta($name, $type));
    }

    public function test_get_name(): void
    {
        $col = $this->make_column('email');
        $this->assertSame('email', $col->get_name());
    }

    public function test_auto_label(): void
    {
        $col = $this->make_column('created_at');
        $this->assertSame('Created At', $col->get_label());
    }

    public function test_custom_label(): void
    {
        $col = $this->make_column('email');
        $col->label('Email Address');
        $this->assertSame('Email Address', $col->get_label());
    }

    public function test_fluent_setters(): void
    {
        $col = $this->make_column();
        $result = $col->sortable(true)
                      ->searchable(true)
                      ->visible(false)
                      ->width('200px')
                      ->min_width('100px')
                      ->formatter('money', ['precision' => 2])
                      ->h_align('right')
                      ->header_align('center')
                      ->frozen(true)
                      ->css_class('highlight')
                      ->order(5)
                      ->header_filter('input');

        $this->assertSame($col, $result);
        $this->assertTrue($col->is_sortable());
        $this->assertTrue($col->is_searchable());
        $this->assertFalse($col->is_visible());
        $this->assertSame('200px', $col->get_width());
        $this->assertSame('100px', $col->get_min_width());
        $this->assertSame('money', $col->get_formatter());
        $this->assertSame(['precision' => 2], $col->get_formatter_params());
        $this->assertSame('right', $col->get_h_align());
        $this->assertSame('center', $col->get_header_align());
        $this->assertTrue($col->is_frozen());
        $this->assertSame('highlight', $col->get_css_class());
        $this->assertSame(5, $col->get_order());
        $this->assertSame('input', $col->get_header_filter());
    }

    public function test_defaults(): void
    {
        $col = $this->make_column();

        $this->assertFalse($col->is_sortable());
        $this->assertFalse($col->is_searchable());
        $this->assertTrue($col->is_visible());
        $this->assertNull($col->get_width());
        $this->assertNull($col->get_formatter());
        $this->assertSame([], $col->get_formatter_params());
        $this->assertFalse($col->is_frozen());
        $this->assertNull($col->get_order());
    }

    public function test_extra_options(): void
    {
        $col = $this->make_column();
        $col->extra(['editor' => 'input']);
        $col->extra(['validator' => 'required']);

        $this->assertSame(['editor' => 'input', 'validator' => 'required'], $col->get_extra());
    }

    public function test_to_array(): void
    {
        $col = $this->make_column('name');
        $col->label('Full Name')
            ->sortable(true)
            ->width('200px')
            ->formatter('plaintext');

        $arr = $col->to_array();

        $this->assertSame('name', $arr['name']);
        $this->assertSame('Full Name', $arr['label']);
        $this->assertTrue($arr['sortable']);
        $this->assertSame('200px', $arr['width']);
        $this->assertSame('plaintext', $arr['formatter']);
    }

    public function test_to_array_minimal(): void
    {
        $col = $this->make_column('id', 'INTEGER');
        $arr = $col->to_array();

        $this->assertSame('id', $arr['name']);
        $this->assertSame('Id', $arr['label']);
        $this->assertFalse($arr['sortable']);
        $this->assertFalse($arr['searchable']);
        $this->assertTrue($arr['visible']);
        $this->assertArrayNotHasKey('width', $arr);
        $this->assertArrayNotHasKey('formatter', $arr);
        $this->assertArrayNotHasKey('frozen', $arr);
    }

    public function test_column_accessor(): void
    {
        $meta = new StubColumnMeta('price', 'DECIMAL');
        $col = new DataSetColumn($meta);

        $this->assertSame($meta, $col->column());
    }
}
