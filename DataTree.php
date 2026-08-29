<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets - DataTree
 *
 * @package Italix\DataSets
 * @license MPL-2.0
 */

declare(strict_types=1);

namespace Italix\DataSets;

use Italix\Contracts\TableMeta;

/**
 * Dataset configured for hierarchical (tree) data display.
 *
 * Extends DataSet with tree-specific configuration for parent-child
 * relationships, expand/collapse behavior, and depth control.
 *
 * Example:
 *
 *     $tree = new DataTree($categories_table);
 *     $tree->columns(['id', 'name', 'slug', 'parent_id']);
 *     $tree->tree_config()
 *          ->parent_column('parent_id')
 *          ->id_column('id')
 *          ->start_open(false);
 *     $tree->ajax_url('/api/categories');
 *
 *     $config = $driver->render($tree);
 */
class DataTree extends DataSet
{
    /** @var TreeConfig */
    private $tree;

    public function __construct(TableMeta $source)
    {
        parent::__construct($source);
        $this->tree = new TreeConfig();
    }

    /**
     * Get or configure the tree configuration.
     *
     * @return TreeConfig
     */
    public function tree_config(): TreeConfig
    {
        return $this->tree;
    }

    /**
     * {@inheritdoc}
     */
    public function is_tree(): bool
    {
        return true;
    }

    /**
     * Get the tree configuration instance.
     *
     * @return TreeConfig
     */
    public function get_tree_config(): TreeConfig
    {
        return $this->tree;
    }
}
