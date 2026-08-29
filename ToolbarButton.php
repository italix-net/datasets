<?php
/**
 * Italix DataSets - ToolbarButton
 *
 * @package Italix\DataSets
 * @license LGPL-2.1-or-later
 */

declare(strict_types=1);

namespace Italix\DataSets;

/**
 * A button in the toolbar that acts on table data.
 *
 * Extends ActionButton with a scope that determines what data
 * the JS callback receives when the button is clicked.
 */
class ToolbarButton extends ActionButton
{
    /** @var string What data this button acts on: 'selected', 'all', or 'none' */
    private $scope;

    /**
     * @param string $name Action name
     * @param string $label Display label
     * @param string $scope 'selected', 'all', or 'none'
     */
    public function __construct(string $name, string $label, string $scope = 'none')
    {
        parent::__construct($name, $label);
        $this->scope = $scope;
    }

    /**
     * Get the button scope.
     *
     * @return string 'selected', 'all', or 'none'
     */
    public function get_scope(): string
    {
        return $this->scope;
    }

    /**
     * @return array
     */
    public function to_array(): array
    {
        $result = parent::to_array();
        $result['scope'] = $this->scope;
        return $result;
    }
}
