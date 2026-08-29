<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets - DataSetColumn
 *
 * @package Italix\DataSets
 * @license MPL-2.0
 */

declare(strict_types=1);

namespace Italix\DataSets;

use Italix\Contracts\ColumnMeta;

/**
 * Column configuration for a dataset.
 *
 * Wraps a ColumnMeta and adds display-specific settings such as
 * labels, sorting, searching, formatting, and width.
 *
 * Example:
 *
 *     $col = new DataSetColumn($column_meta);
 *     $col->label('Full Name')
 *         ->sortable(true)
 *         ->searchable(true)
 *         ->width('200px')
 *         ->formatter('plaintext');
 */
class DataSetColumn
{
    /** @var ColumnMeta */
    private $column;

    /** @var string|null */
    private $label = null;

    /** @var bool */
    private $sortable = false;

    /** @var bool */
    private $searchable = false;

    /** @var bool */
    private $visible = true;

    /** @var string|null */
    private $width = null;

    /** @var string|null */
    private $min_width = null;

    /** @var string|null Custom formatter name (driver-specific, e.g. 'datetime', 'money', 'link') */
    private $formatter = null;

    /** @var array Formatter parameters (driver-specific) */
    private $formatter_params = [];

    /** @var string|null Horizontal alignment: 'left', 'center', 'right' */
    private $h_align = null;

    /** @var string|null Header alignment: 'left', 'center', 'right' */
    private $header_align = null;

    /** @var bool Whether the column is frozen (sticky) */
    private $frozen = false;

    /** @var string|null CSS class for the column cells */
    private $css_class = null;

    /** @var int|null Display order (lower = first) */
    private $order = null;

    /** @var string|null Header filter type (e.g. 'input', 'select', 'number') */
    private $header_filter = null;

    /** @var array Extra driver-specific options */
    private $extra = [];

    public function __construct(ColumnMeta $column)
    {
        $this->column = $column;
    }

    // =========================================================================
    // Fluent Setters
    // =========================================================================

    /**
     * Set the column display label.
     *
     * @param string $label
     * @return self
     */
    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Set whether the column is sortable.
     *
     * @param bool $sortable
     * @return self
     */
    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;
        return $this;
    }

    /**
     * Set whether the column is searchable.
     *
     * @param bool $searchable
     * @return self
     */
    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;
        return $this;
    }

    /**
     * Set whether the column is visible.
     *
     * @param bool $visible
     * @return self
     */
    public function visible(bool $visible = true): self
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * Set the column width (CSS value).
     *
     * @param string $width E.g. '200px', '20%'
     * @return self
     */
    public function width(string $width): self
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Set the column minimum width (CSS value).
     *
     * @param string $min_width
     * @return self
     */
    public function min_width(string $min_width): self
    {
        $this->min_width = $min_width;
        return $this;
    }

    /**
     * Set the column formatter.
     *
     * Formatters are driver-specific. Common names: 'plaintext', 'html',
     * 'money', 'datetime', 'link', 'image', 'progress', 'tickCross'.
     *
     * @param string $formatter
     * @param array $params Optional formatter parameters
     * @return self
     */
    public function formatter(string $formatter, array $params = []): self
    {
        $this->formatter = $formatter;
        $this->formatter_params = $params;
        return $this;
    }

    /**
     * Set the horizontal alignment for cell content.
     *
     * @param string $align 'left', 'center', or 'right'
     * @return self
     */
    public function h_align(string $align): self
    {
        $this->h_align = $align;
        return $this;
    }

    /**
     * Set the header text alignment.
     *
     * @param string $align 'left', 'center', or 'right'
     * @return self
     */
    public function header_align(string $align): self
    {
        $this->header_align = $align;
        return $this;
    }

    /**
     * Set whether the column is frozen (sticky on horizontal scroll).
     *
     * @param bool $frozen
     * @return self
     */
    public function frozen(bool $frozen = true): self
    {
        $this->frozen = $frozen;
        return $this;
    }

    /**
     * Set a CSS class for the column cells.
     *
     * @param string $css_class
     * @return self
     */
    public function css_class(string $css_class): self
    {
        $this->css_class = $css_class;
        return $this;
    }

    /**
     * Set the display order.
     *
     * @param int $order
     * @return self
     */
    public function order(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Set a header filter type for column-level filtering.
     *
     * @param string $type E.g. 'input', 'select', 'number', 'list'
     * @return self
     */
    public function header_filter(string $type): self
    {
        $this->header_filter = $type;
        return $this;
    }

    /**
     * Set extra driver-specific options.
     *
     * These are merged into the column definition as-is by the driver.
     *
     * @param array $options
     * @return self
     */
    public function extra(array $options): self
    {
        $this->extra = array_merge($this->extra, $options);
        return $this;
    }

    // =========================================================================
    // Getters
    // =========================================================================

    /**
     * Get the underlying column metadata.
     *
     * @return ColumnMeta
     */
    public function column(): ColumnMeta
    {
        return $this->column;
    }

    /**
     * Get the column field name.
     *
     * @return string
     */
    public function get_name(): string
    {
        return $this->column->get_name();
    }

    /**
     * Get the display label (auto-generated from name if not set).
     *
     * @return string
     */
    public function get_label(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        return ucwords(str_replace('_', ' ', $this->column->get_name()));
    }

    /**
     * @return bool
     */
    public function is_sortable(): bool
    {
        return $this->sortable;
    }

    /**
     * @return bool
     */
    public function is_searchable(): bool
    {
        return $this->searchable;
    }

    /**
     * @return bool
     */
    public function is_visible(): bool
    {
        return $this->visible;
    }

    /**
     * @return string|null
     */
    public function get_width(): ?string
    {
        return $this->width;
    }

    /**
     * @return string|null
     */
    public function get_min_width(): ?string
    {
        return $this->min_width;
    }

    /**
     * @return string|null
     */
    public function get_formatter(): ?string
    {
        return $this->formatter;
    }

    /**
     * @return array
     */
    public function get_formatter_params(): array
    {
        return $this->formatter_params;
    }

    /**
     * @return string|null
     */
    public function get_h_align(): ?string
    {
        return $this->h_align;
    }

    /**
     * @return string|null
     */
    public function get_header_align(): ?string
    {
        return $this->header_align;
    }

    /**
     * @return bool
     */
    public function is_frozen(): bool
    {
        return $this->frozen;
    }

    /**
     * @return string|null
     */
    public function get_css_class(): ?string
    {
        return $this->css_class;
    }

    /**
     * @return int|null
     */
    public function get_order(): ?int
    {
        return $this->order;
    }

    /**
     * @return string|null
     */
    public function get_header_filter(): ?string
    {
        return $this->header_filter;
    }

    /**
     * @return array
     */
    public function get_extra(): array
    {
        return $this->extra;
    }

    // =========================================================================
    // Export
    // =========================================================================

    /**
     * Export column configuration as an array.
     *
     * @return array
     */
    public function to_array(): array
    {
        $result = [
            'name' => $this->get_name(),
            'label' => $this->get_label(),
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
            'visible' => $this->visible,
        ];

        if ($this->width !== null) {
            $result['width'] = $this->width;
        }
        if ($this->min_width !== null) {
            $result['min_width'] = $this->min_width;
        }
        if ($this->formatter !== null) {
            $result['formatter'] = $this->formatter;
            if (!empty($this->formatter_params)) {
                $result['formatter_params'] = $this->formatter_params;
            }
        }
        if ($this->h_align !== null) {
            $result['h_align'] = $this->h_align;
        }
        if ($this->header_align !== null) {
            $result['header_align'] = $this->header_align;
        }
        if ($this->frozen) {
            $result['frozen'] = true;
        }
        if ($this->css_class !== null) {
            $result['css_class'] = $this->css_class;
        }
        if ($this->order !== null) {
            $result['order'] = $this->order;
        }
        if ($this->header_filter !== null) {
            $result['header_filter'] = $this->header_filter;
        }
        if (!empty($this->extra)) {
            $result['extra'] = $this->extra;
        }

        return $result;
    }
}
