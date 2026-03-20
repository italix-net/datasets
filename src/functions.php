<?php
/**
 * Italix DataSets - Helper Functions
 *
 * @package Italix\DataSets
 * @license LGPL-2.1-or-later
 */

declare(strict_types=1);

namespace Italix\DataSets;

use Italix\Contracts\TableMeta;

/**
 * Create a new DataSet from a TableMeta source.
 *
 * @param TableMeta $source
 * @return DataSet
 */
function dataset(TableMeta $source): DataSet
{
    return new DataSet($source);
}

/**
 * Create a new DataTree from a TableMeta source.
 *
 * @param TableMeta $source
 * @return DataTree
 */
function data_tree(TableMeta $source): DataTree
{
    return new DataTree($source);
}
