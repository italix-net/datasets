<?php
/**
 * Italix DataSets - ActionColumn
 *
 * @package Italix\DataSets
 * @license LGPL-2.1-or-later
 */

declare(strict_types=1);

namespace Italix\DataSets;

/**
 * Defines a column of per-row action buttons (edit, delete, view, etc.).
 *
 * The action column is appended to the table as the last column (or first,
 * if configured). Each button triggers a named JS callback with the row data.
 *
 * Example:
 *
 *     $actions = new ActionColumn();
 *     $actions->button('edit', 'Edit')
 *             ->css_class('btn btn-sm btn-primary');
 *     $actions->button('delete', 'Delete')
 *             ->css_class('btn btn-sm btn-danger')
 *             ->confirm('Are you sure?');
 *     $actions->label('Actions')
 *             ->width('120px')
 *             ->position('end');
 */
class ActionColumn
{
    /** @var ActionButton[] */
    private $buttons = [];

    /** @var string Column header label */
    private $label = 'Actions';

    /** @var string|null Column width */
    private $width = null;

    /** @var string Position: 'start' or 'end' */
    private $position = 'end';

    /** @var bool Whether the column is frozen (sticky) */
    private $frozen = false;

    /** @var string|null CSS class for the action cell */
    private $css_class = null;

    // =========================================================================
    // Button Management
    // =========================================================================

    /**
     * Add an action button.
     *
     * Returns the ActionButton so you can configure it fluently.
     *
     * @param string $name Unique action name (used as JS callback key)
     * @param string $label Display label
     * @return ActionButton
     */
    public function button(string $name, string $label): ActionButton
    {
        $btn = new ActionButton($name, $label);
        $this->buttons[] = $btn;
        return $btn;
    }

    /**
     * Get all registered buttons.
     *
     * @return ActionButton[]
     */
    public function get_buttons(): array
    {
        return $this->buttons;
    }

    // =========================================================================
    // Fluent Setters
    // =========================================================================

    /**
     * Set the column header label.
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
     * Set the column width.
     *
     * @param string $width CSS value, e.g. '120px'
     * @return self
     */
    public function width(string $width): self
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Set the column position: 'start' (first column) or 'end' (last column).
     *
     * @param string $position
     * @return self
     */
    public function position(string $position): self
    {
        $this->position = $position;
        return $this;
    }

    /**
     * Set whether the column is frozen (sticky on scroll).
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
     * Set CSS class for the action cell.
     *
     * @param string $css_class
     * @return self
     */
    public function css_class(string $css_class): self
    {
        $this->css_class = $css_class;
        return $this;
    }

    // =========================================================================
    // Getters
    // =========================================================================

    /** @return string */
    public function get_label(): string
    {
        return $this->label;
    }

    /** @return string|null */
    public function get_width(): ?string
    {
        return $this->width;
    }

    /** @return string */
    public function get_position(): string
    {
        return $this->position;
    }

    /** @return bool */
    public function is_frozen(): bool
    {
        return $this->frozen;
    }

    /** @return string|null */
    public function get_css_class(): ?string
    {
        return $this->css_class;
    }

    // =========================================================================
    // Export
    // =========================================================================

    /**
     * @return array
     */
    public function to_array(): array
    {
        $result = [
            'label' => $this->label,
            'position' => $this->position,
            'buttons' => array_map(function (ActionButton $btn) {
                return $btn->to_array();
            }, $this->buttons),
        ];

        if ($this->width !== null) {
            $result['width'] = $this->width;
        }
        if ($this->frozen) {
            $result['frozen'] = true;
        }
        if ($this->css_class !== null) {
            $result['css_class'] = $this->css_class;
        }

        return $result;
    }
}
