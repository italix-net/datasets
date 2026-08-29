<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests\PHPUnit;

use Italix\DataSets\TreeConfig;
use PHPUnit\Framework\TestCase;

class TreeConfigTest extends TestCase
{
    public function test_defaults(): void
    {
        $tc = new TreeConfig();

        $this->assertSame('parent_id', $tc->get_parent_column());
        $this->assertSame('id', $tc->get_id_column());
        $this->assertFalse($tc->is_start_open());
        $this->assertTrue($tc->is_show_toggle());
        $this->assertNull($tc->get_max_depth());
        $this->assertNull($tc->get_toggle_column());
    }

    public function test_fluent_setters(): void
    {
        $tc = new TreeConfig();
        $result = $tc->parent_column('parent_cat_id')
                     ->id_column('cat_id')
                     ->start_open(true)
                     ->show_toggle(false)
                     ->max_depth(5)
                     ->toggle_column('label');

        $this->assertSame($tc, $result);
        $this->assertSame('parent_cat_id', $tc->get_parent_column());
        $this->assertSame('cat_id', $tc->get_id_column());
        $this->assertTrue($tc->is_start_open());
        $this->assertFalse($tc->is_show_toggle());
        $this->assertSame(5, $tc->get_max_depth());
        $this->assertSame('label', $tc->get_toggle_column());
    }

    public function test_to_array(): void
    {
        $tc = new TreeConfig();
        $tc->parent_column('pid')->max_depth(3)->toggle_column('name');

        $arr = $tc->to_array();

        $this->assertSame('pid', $arr['parent_column']);
        $this->assertSame('id', $arr['id_column']);
        $this->assertFalse($arr['start_open']);
        $this->assertTrue($arr['show_toggle']);
        $this->assertSame(3, $arr['max_depth']);
        $this->assertSame('name', $arr['toggle_column']);
    }

    public function test_to_array_minimal(): void
    {
        $tc = new TreeConfig();
        $arr = $tc->to_array();

        $this->assertArrayNotHasKey('max_depth', $arr);
        $this->assertArrayNotHasKey('toggle_column', $arr);
    }
}
