<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets - ActionButton
 *
 * @package Italix\DataSets
 * @license MPL-2.0
 */

declare(strict_types=1);

namespace Italix\DataSets;

/**
 * A button rendered inside an action column or toolbar.
 *
 * Each button has a name (used as the JS callback identifier), a label
 * (displayed text), and optional styling/icon configuration.
 *
 * Example:
 *
 *     $btn = new ActionButton('edit', 'Edit');
 *     $btn->css_class('btn btn-sm btn-primary')
 *         ->icon('fa fa-pencil')
 *         ->title('Edit this row');
 */
class ActionButton
{
    /** @var string Unique action name (used as JS callback key) */
    private $name;

    /** @var string Display label */
    private $label;

    /** @var string|null CSS class for the button */
    private $css_class = null;

    /** @var string|null Icon class (e.g. 'fa fa-pencil') */
    private $icon = null;

    /** @var string|null Tooltip / title attribute */
    private $title = null;

    /** @var string|null Confirmation message (shown before executing) */
    private $confirm = null;

    /** @var array Extra attributes */
    private $extra = [];

    /**
     * @param string $name Unique action name
     * @param string $label Display label
     */
    public function __construct(string $name, string $label)
    {
        $this->name = $name;
        $this->label = $label;
    }

    // =========================================================================
    // Fluent Setters
    // =========================================================================

    /**
     * @param string $css_class
     * @return self
     */
    public function css_class(string $css_class): self
    {
        $this->css_class = $css_class;
        return $this;
    }

    /**
     * @param string $icon Icon class, e.g. 'fa fa-pencil'
     * @return self
     */
    public function icon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @param string $title Tooltip text
     * @return self
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set a confirmation message shown before the action fires.
     *
     * @param string $message
     * @return self
     */
    public function confirm(string $message): self
    {
        $this->confirm = $message;
        return $this;
    }

    /**
     * Set extra options (driver-specific).
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

    /** @return string */
    public function get_name(): string
    {
        return $this->name;
    }

    /** @return string */
    public function get_label(): string
    {
        return $this->label;
    }

    /** @return string|null */
    public function get_css_class(): ?string
    {
        return $this->css_class;
    }

    /** @return string|null */
    public function get_icon(): ?string
    {
        return $this->icon;
    }

    /** @return string|null */
    public function get_title(): ?string
    {
        return $this->title;
    }

    /** @return string|null */
    public function get_confirm(): ?string
    {
        return $this->confirm;
    }

    /** @return array */
    public function get_extra(): array
    {
        return $this->extra;
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
            'name' => $this->name,
            'label' => $this->label,
        ];

        if ($this->css_class !== null) {
            $result['css_class'] = $this->css_class;
        }
        if ($this->icon !== null) {
            $result['icon'] = $this->icon;
        }
        if ($this->title !== null) {
            $result['title'] = $this->title;
        }
        if ($this->confirm !== null) {
            $result['confirm'] = $this->confirm;
        }
        if (!empty($this->extra)) {
            $result['extra'] = $this->extra;
        }

        return $result;
    }
}
