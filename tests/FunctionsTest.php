<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests;

use Italix\DataSets\DataSet;
use Italix\DataSets\DataTree;
use Italix\DataSets\Tests\Fixtures\StubTableMeta;
use PHPUnit\Framework\TestCase;

use function Italix\DataSets\dataset;
use function Italix\DataSets\data_tree;

class FunctionsTest extends TestCase
{
    public function test_dataset_helper(): void
    {
        $table = StubTableMeta::users();
        $ds = dataset($table);

        $this->assertInstanceOf(DataSet::class, $ds);
        $this->assertSame($table, $ds->source());
    }

    public function test_data_tree_helper(): void
    {
        $table = StubTableMeta::categories();
        $tree = data_tree($table);

        $this->assertInstanceOf(DataTree::class, $tree);
        $this->assertTrue($tree->is_tree());
        $this->assertSame($table, $tree->source());
    }
}
