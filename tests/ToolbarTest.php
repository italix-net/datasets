<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests;

use Italix\DataSets\Toolbar;
use Italix\DataSets\ToolbarButton;
use PHPUnit\Framework\TestCase;

class ToolbarTest extends TestCase
{
    public function test_button_returns_toolbar_button(): void
    {
        $toolbar = new Toolbar();
        $btn = $toolbar->button('add', 'Add New', 'none');

        $this->assertInstanceOf(ToolbarButton::class, $btn);
        $this->assertSame('add', $btn->get_name());
        $this->assertSame('Add New', $btn->get_label());
        $this->assertSame('none', $btn->get_scope());
    }

    public function test_default_scope_is_none(): void
    {
        $toolbar = new Toolbar();
        $btn = $toolbar->button('add', 'Add New');

        $this->assertSame('none', $btn->get_scope());
    }

    public function test_multiple_buttons(): void
    {
        $toolbar = new Toolbar();
        $toolbar->button('add', 'Add', 'none');
        $toolbar->button('delete', 'Delete Selected', 'selected');
        $toolbar->button('export', 'Export', 'all');

        $this->assertCount(3, $toolbar->get_buttons());
    }

    public function test_defaults(): void
    {
        $toolbar = new Toolbar();

        $this->assertSame('top', $toolbar->get_position());
        $this->assertNull($toolbar->get_css_class());
    }

    public function test_fluent_setters(): void
    {
        $toolbar = new Toolbar();
        $result = $toolbar->position('bottom')
                          ->css_class('my-toolbar');

        $this->assertSame($toolbar, $result);
        $this->assertSame('bottom', $toolbar->get_position());
        $this->assertSame('my-toolbar', $toolbar->get_css_class());
    }

    public function test_requires_selection(): void
    {
        $toolbar = new Toolbar();
        $this->assertFalse($toolbar->requires_selection());

        $toolbar->button('add', 'Add', 'none');
        $this->assertFalse($toolbar->requires_selection());

        $toolbar->button('delete', 'Delete', 'selected');
        $this->assertTrue($toolbar->requires_selection());
    }

    public function test_toolbar_button_inherits_action_button(): void
    {
        $btn = new ToolbarButton('bulk', 'Bulk Op', 'selected');
        $btn->css_class('btn btn-warning')
            ->icon('fa fa-cogs')
            ->confirm('Process all selected?');

        $this->assertSame('btn btn-warning', $btn->get_css_class());
        $this->assertSame('fa fa-cogs', $btn->get_icon());
        $this->assertSame('Process all selected?', $btn->get_confirm());
        $this->assertSame('selected', $btn->get_scope());
    }

    public function test_toolbar_button_to_array(): void
    {
        $btn = new ToolbarButton('export', 'Export CSV', 'all');
        $btn->css_class('btn');

        $arr = $btn->to_array();

        $this->assertSame('export', $arr['name']);
        $this->assertSame('Export CSV', $arr['label']);
        $this->assertSame('all', $arr['scope']);
        $this->assertSame('btn', $arr['css_class']);
    }

    public function test_to_array(): void
    {
        $toolbar = new Toolbar();
        $toolbar->position('both')->css_class('toolbar');
        $toolbar->button('add', 'Add', 'none');
        $toolbar->button('delete', 'Delete', 'selected');

        $arr = $toolbar->to_array();

        $this->assertSame('both', $arr['position']);
        $this->assertSame('toolbar', $arr['css_class']);
        $this->assertCount(2, $arr['buttons']);
        $this->assertSame('none', $arr['buttons'][0]['scope']);
        $this->assertSame('selected', $arr['buttons'][1]['scope']);
    }
}
