<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets - Helper Functions
 *
 * @package Italix\DataSets
 * @license MPL-2.0
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
