<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests\PHPUnit;

use Italix\DataSets\DataTree;
use Italix\DataSets\TreeConfig;
use Italix\DataSets\Tests\PHPUnit\Fixtures\StubTableMeta;
use PHPUnit\Framework\TestCase;

class DataTreeTest extends TestCase
{
    public function test_is_tree(): void
    {
        $tree = new DataTree(StubTableMeta::categories());
        $this->assertTrue($tree->is_tree());
    }

    public function test_tree_config_returns_tree_config(): void
    {
        $tree = new DataTree(StubTableMeta::categories());
        $this->assertInstanceOf(TreeConfig::class, $tree->tree_config());
    }

    public function test_tree_config_is_same_instance(): void
    {
        $tree = new DataTree(StubTableMeta::categories());
        $this->assertSame($tree->tree_config(), $tree->get_tree_config());
    }

    public function test_tree_config_fluent(): void
    {
        $tree = new DataTree(StubTableMeta::categories());
        $tree->tree_config()
             ->parent_column('parent_category_id')
             ->id_column('category_id')
             ->start_open(true)
             ->max_depth(3)
             ->toggle_column('name');

        $tc = $tree->get_tree_config();
        $this->assertSame('parent_category_id', $tc->get_parent_column());
        $this->assertSame('category_id', $tc->get_id_column());
        $this->assertTrue($tc->is_start_open());
        $this->assertSame(3, $tc->get_max_depth());
        $this->assertSame('name', $tc->get_toggle_column());
    }

    public function test_tree_inherits_dataset_features(): void
    {
        $tree = new DataTree(StubTableMeta::categories());
        $tree->columns(['id', 'name', 'parent_id'])
             ->ajax_url('/api/categories')
             ->per_page(50);

        $this->assertSame(['id', 'name', 'parent_id'], $tree->get_visible_columns());
        $this->assertSame('/api/categories', $tree->get_ajax_url());
        $this->assertSame(50, $tree->get_per_page());
    }
}
