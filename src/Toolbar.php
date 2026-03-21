<?php
/**
 * Italix DataSets - Toolbar
 *
 * @package Italix\DataSets
 * @license LGPL-2.1-or-later
 */

declare(strict_types=1);

namespace Italix\DataSets;

/**
 * Toolbar with buttons rendered above (or below) the datatable.
 *
 * Toolbar buttons can act on:
 * - 'selected' — receives the array of currently selected rows
 * - 'all'      — receives all data (useful for export)
 * - 'none'     — standalone action (e.g. "Add New")
 *
 * Each button triggers a named JS callback.
 *
 * Example:
 *
 *     $toolbar = new Toolbar();
 *     $toolbar->button('add', 'Add New', 'none')
 *             ->css_class('btn btn-primary');
 *     $toolbar->button('bulk_delete', 'Delete Selected', 'selected')
 *             ->css_class('btn btn-danger')
 *             ->confirm('Delete all selected rows?');
 *     $toolbar->button('export', 'Export CSV', 'all')
 *             ->css_class('btn btn-secondary');
 *     $toolbar->position('top');
 */
class Toolbar
{
    /** @var ToolbarButton[] */
    private $buttons = [];

    /** @var string Position: 'top', 'bottom', or 'both' */
    private $position = 'top';

    /** @var string|null CSS class for the toolbar container */
    private $css_class = null;

    // =========================================================================
    // Button Management
    // =========================================================================

    /**
     * Add a toolbar button.
     *
     * @param string $name Action name (JS callback key)
     * @param string $label Display label
     * @param string $scope What data the button receives: 'selected', 'all', or 'none'
     * @return ToolbarButton
     */
    public function button(string $name, string $label, string $scope = 'none'): ToolbarButton
    {
        $btn = new ToolbarButton($name, $label, $scope);
        $this->buttons[] = $btn;
        return $btn;
    }

    /**
     * Get all toolbar buttons.
     *
     * @return ToolbarButton[]
     */
    public function get_buttons(): array
    {
        return $this->buttons;
    }

    // =========================================================================
    // Fluent Setters
    // =========================================================================

    /**
     * Set the toolbar position.
     *
     * @param string $position 'top', 'bottom', or 'both'
     * @return self
     */
    public function position(string $position): self
    {
        $this->position = $position;
        return $this;
    }

    /**
     * Set CSS class for the toolbar container.
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
    public function get_position(): string
    {
        return $this->position;
    }

    /** @return string|null */
    public function get_css_class(): ?string
    {
        return $this->css_class;
    }

    /**
     * Check if any button requires row selection.
     *
     * @return bool
     */
    public function requires_selection(): bool
    {
        foreach ($this->buttons as $btn) {
            if ($btn->get_scope() === 'selected') {
                return true;
            }
        }
        return false;
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
            'position' => $this->position,
            'buttons' => array_map(function (ToolbarButton $btn) {
                return $btn->to_array();
            }, $this->buttons),
        ];

        if ($this->css_class !== null) {
            $result['css_class'] = $this->css_class;
        }

        return $result;
    }
}
