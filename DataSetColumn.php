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
    private $horizontal_align = null;

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

    /**
     * Responsive hide priority.
     *
     * When the table uses a responsive layout mode, this controls the order in
     * which columns are hidden when horizontal space is insufficient.
     *
     *   null         — not set; driver uses its own default (column stays visible)
     *   false        — never hide this column (maps to priority 0 in Tabulator)
     *   int (1-N)    — hide priority; higher values are hidden first
     *
     * @var int|false|null
     */
    private $responsive_priority = null;

    /**
     * Whether to show this column in the card layout.
     *
     *   null  — inherit from is_visible() (default)
     *   true  — always show in card, even if visible(false) in the table
     *   false — never show in card, even if visible in the table
     *
     * @var bool|null
     */
    private $card_visible = null;

    /**
     * Display order within the card layout body.
     *
     * Lower values appear first. Columns without an explicit order are placed
     * after those with a card_order, in their original definition order.
     *
     * @var int|null
     */
    private $card_order = null;

    /**
     * Multi-line cell definition for compound columns.
     *
     * Each line: ['field' => string, 'style' => 'title'|'subtitle'|'plain', 'label' => string (optional)].
     * Fields referenced here do not need their own DataSetColumn entry — they just
     * need to be present in the AJAX JSON response.
     *
     * @var array|null  [{field, style, ?label}, ...]
     */
    private $cell_lines = null;

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
    public function horizontal_align(string $align): self
    {
        $this->horizontal_align = $align;
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
     * Set the responsive hide priority for this column.
     *
     * When the table uses a responsive layout mode, columns with a higher
     * priority value are hidden first. Set to false to pin the column so it
     * is never hidden regardless of available space.
     *
     * Common convention:
     *   false   — always visible (pin; e.g. primary name, action buttons)
     *   1       — hide last among deprioritised columns
     *   2       — hide before priority-1 columns
     *   3+      — hide first (e.g. internal IDs, secondary metadata)
     *
     * @param int|false $priority
     * @return self
     */
    public function responsive($priority): self
    {
        $this->responsive_priority = $priority;
        return $this;
    }

    /**
     * Set card layout visibility for this column.
     *
     * Overrides the default behaviour (which inherits from visible()).
     * Use card_visible(false) to hide a column from card view while keeping
     * it in the table, or card_visible(true) to show it in the card even if
     * it is hidden in the table (value will be raw, unformatted).
     *
     * @param bool $v
     * @return self
     */
    public function card_visible(bool $v): self
    {
        $this->card_visible = $v;
        return $this;
    }

    /**
     * Set the display order of this column within the card layout body.
     *
     * Lower values appear first. Columns without a card_order are placed
     * after explicitly ordered ones, in their original definition order.
     *
     * @param int $order
     * @return self
     */
    public function card_order(int $order): self
    {
        $this->card_order = $order;
        return $this;
    }

    /**
     * Define multi-line cell rendering for this column.
     *
     * Renders multiple data fields stacked vertically within a single cell.
     * Useful for composite columns such as "customer" (name + email + phone).
     *
     * Each line definition:
     *   'field' => string                      — data field name (must be in AJAX response)
     *   'style' => 'title'|'subtitle'|'plain'  — visual weight
     *   'label' => string  (optional)          — label shown in card expansion;
     *                                            if omitted: first line uses column label,
     *                                            subsequent lines use no label
     *
     * Note: fields referenced here do not need their own DataSetColumn — they just
     * need to be in the SQL SELECT (as aliases) and returned in the AJAX JSON.
     *
     * Note: avoid setting a fixed table height() when using cell_lines, as Tabulator
     * auto-sizes row heights by default and a fixed height will clip multi-line cells.
     *
     * @param array $lines
     * @return self
     */
    public function cell_lines(array $lines): self
    {
        $this->cell_lines = $lines;
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
    public function get_horizontal_align(): ?string
    {
        return $this->horizontal_align;
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
     * Get the responsive hide priority (null = not set).
     *
     * Untyped on purpose: an `int|false|null` union would raise this package's
     * floor to PHP 8.0 for one return type. The docblock says the same thing.
     *
     * @return int|false|null
     */
    public function get_responsive_priority()
    {
        return $this->responsive_priority;
    }

    /**
     * Get the card layout visibility override (null = inherit from visible()).
     *
     * @return bool|null
     */
    public function get_card_visible(): ?bool
    {
        return $this->card_visible;
    }

    /**
     * Get the explicit card layout display order (null = use definition order).
     *
     * @return int|null
     */
    public function get_card_order(): ?int
    {
        return $this->card_order;
    }

    /**
     * Get the multi-line cell definition, or null if not set.
     *
     * @return array|null
     */
    public function get_cell_lines(): ?array
    {
        return $this->cell_lines;
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
        if ($this->horizontal_align !== null) {
            $result['horizontal_align'] = $this->horizontal_align;
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
        if ($this->responsive_priority !== null) {
            $result['responsive_priority'] = $this->responsive_priority;
        }
        if ($this->card_visible !== null) {
            $result['card_visible'] = $this->card_visible;
        }
        if ($this->card_order !== null) {
            $result['card_order'] = $this->card_order;
        }
        if ($this->cell_lines !== null) {
            $result['cell_lines'] = $this->cell_lines;
        }
        if (!empty($this->extra)) {
            $result['extra'] = $this->extra;
        }

        return $result;
    }
}
