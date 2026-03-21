<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests;

use Italix\DataSets\ActionButton;
use Italix\DataSets\ActionColumn;
use PHPUnit\Framework\TestCase;

class ActionColumnTest extends TestCase
{
    public function test_button_returns_action_button(): void
    {
        $col = new ActionColumn();
        $btn = $col->button('edit', 'Edit');

        $this->assertInstanceOf(ActionButton::class, $btn);
        $this->assertSame('edit', $btn->get_name());
        $this->assertSame('Edit', $btn->get_label());
    }

    public function test_multiple_buttons(): void
    {
        $col = new ActionColumn();
        $col->button('edit', 'Edit');
        $col->button('delete', 'Delete');

        $this->assertCount(2, $col->get_buttons());
    }

    public function test_defaults(): void
    {
        $col = new ActionColumn();

        $this->assertSame('Actions', $col->get_label());
        $this->assertNull($col->get_width());
        $this->assertSame('end', $col->get_position());
        $this->assertFalse($col->is_frozen());
        $this->assertNull($col->get_css_class());
    }

    public function test_fluent_setters(): void
    {
        $col = new ActionColumn();
        $result = $col->label('Operations')
                      ->width('150px')
                      ->position('start')
                      ->frozen(true)
                      ->css_class('actions-cell');

        $this->assertSame($col, $result);
        $this->assertSame('Operations', $col->get_label());
        $this->assertSame('150px', $col->get_width());
        $this->assertSame('start', $col->get_position());
        $this->assertTrue($col->is_frozen());
        $this->assertSame('actions-cell', $col->get_css_class());
    }

    public function test_button_fluent_config(): void
    {
        $col = new ActionColumn();
        $btn = $col->button('delete', 'Delete');
        $btn->css_class('btn btn-danger')
            ->icon('fa fa-trash')
            ->title('Delete this row')
            ->confirm('Are you sure?')
            ->extra(['data-type' => 'danger']);

        $this->assertSame('btn btn-danger', $btn->get_css_class());
        $this->assertSame('fa fa-trash', $btn->get_icon());
        $this->assertSame('Delete this row', $btn->get_title());
        $this->assertSame('Are you sure?', $btn->get_confirm());
        $this->assertSame(['data-type' => 'danger'], $btn->get_extra());
    }

    public function test_to_array(): void
    {
        $col = new ActionColumn();
        $col->label('Ops')->width('100px')->position('start');
        $col->button('edit', 'Edit')->css_class('btn');
        $col->button('delete', 'Delete')->confirm('Sure?');

        $arr = $col->to_array();

        $this->assertSame('Ops', $arr['label']);
        $this->assertSame('start', $arr['position']);
        $this->assertSame('100px', $arr['width']);
        $this->assertCount(2, $arr['buttons']);
        $this->assertSame('edit', $arr['buttons'][0]['name']);
        $this->assertSame('btn', $arr['buttons'][0]['css_class']);
        $this->assertSame('delete', $arr['buttons'][1]['name']);
        $this->assertSame('Sure?', $arr['buttons'][1]['confirm']);
    }

    public function test_button_to_array_minimal(): void
    {
        $btn = new ActionButton('view', 'View');
        $arr = $btn->to_array();

        $this->assertSame('view', $arr['name']);
        $this->assertSame('View', $arr['label']);
        $this->assertArrayNotHasKey('css_class', $arr);
        $this->assertArrayNotHasKey('icon', $arr);
        $this->assertArrayNotHasKey('confirm', $arr);
    }
}
