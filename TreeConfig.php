<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets - TreeConfig
 *
 * @package Italix\DataSets
 * @license MPL-2.0
 */

declare(strict_types=1);

namespace Italix\DataSets;

/**
 * Configuration for hierarchical (tree) data display.
 *
 * Defines the parent-child relationship and tree rendering options.
 *
 * Example:
 *
 *     $config = new TreeConfig();
 *     $config->parent_column('parent_id')
 *            ->id_column('id')
 *            ->start_open(false);
 */
class TreeConfig
{
    /** @var string The column containing the parent reference */
    private $parent_column = 'parent_id';

    /** @var string The column containing the row identifier */
    private $id_column = 'id';

    /** @var bool Whether tree nodes start expanded */
    private $start_open = false;

    /** @var bool Whether to show expand/collapse toggle controls */
    private $show_toggle = true;

    /** @var int|null Maximum depth to display (null = unlimited) */
    private $max_depth = null;

    /** @var string|null The column used to display the tree expand/collapse control */
    private $toggle_column = null;

    // =========================================================================
    // Fluent Setters
    // =========================================================================

    /**
     * Set the column that holds the parent row reference.
     *
     * @param string $column E.g. 'parent_id', 'parent_category_id'
     * @return self
     */
    public function parent_column(string $column): self
    {
        $this->parent_column = $column;
        return $this;
    }

    /**
     * Set the column that holds the row identifier.
     *
     * @param string $column E.g. 'id', 'uuid'
     * @return self
     */
    public function id_column(string $column): self
    {
        $this->id_column = $column;
        return $this;
    }

    /**
     * Set whether tree nodes start expanded.
     *
     * @param bool $open
     * @return self
     */
    public function start_open(bool $open = true): self
    {
        $this->start_open = $open;
        return $this;
    }

    /**
     * Set whether to show the expand/collapse toggle control.
     *
     * @param bool $show
     * @return self
     */
    public function show_toggle(bool $show = true): self
    {
        $this->show_toggle = $show;
        return $this;
    }

    /**
     * Set the maximum tree depth to display.
     *
     * @param int|null $depth null for unlimited
     * @return self
     */
    public function max_depth(?int $depth): self
    {
        $this->max_depth = $depth;
        return $this;
    }

    /**
     * Set which column shows the tree toggle (expand/collapse icon).
     *
     * If not set, the first visible column is used.
     *
     * @param string $column
     * @return self
     */
    public function toggle_column(string $column): self
    {
        $this->toggle_column = $column;
        return $this;
    }

    // =========================================================================
    // Getters
    // =========================================================================

    /** @return string */
    public function get_parent_column(): string
    {
        return $this->parent_column;
    }

    /** @return string */
    public function get_id_column(): string
    {
        return $this->id_column;
    }

    /** @return bool */
    public function is_start_open(): bool
    {
        return $this->start_open;
    }

    /** @return bool */
    public function is_show_toggle(): bool
    {
        return $this->show_toggle;
    }

    /** @return int|null */
    public function get_max_depth(): ?int
    {
        return $this->max_depth;
    }

    /** @return string|null */
    public function get_toggle_column(): ?string
    {
        return $this->toggle_column;
    }

    // =========================================================================
    // Export
    // =========================================================================

    /**
     * Export tree configuration as an array.
     *
     * @return array
     */
    public function to_array(): array
    {
        $result = [
            'parent_column' => $this->parent_column,
            'id_column' => $this->id_column,
            'start_open' => $this->start_open,
            'show_toggle' => $this->show_toggle,
        ];

        if ($this->max_depth !== null) {
            $result['max_depth'] = $this->max_depth;
        }
        if ($this->toggle_column !== null) {
            $result['toggle_column'] = $this->toggle_column;
        }

        return $result;
    }
}
