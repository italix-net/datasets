<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets - DriverInterface
 *
 * @package Italix\DataSets
 * @license MPL-2.0
 */

declare(strict_types=1);

namespace Italix\DataSets\Drivers;

use Italix\DataSets\DataSet;

/**
 * Interface for datatable rendering drivers.
 *
 * Each driver translates a DataSet (or DataTree) configuration into
 * the JSON config format expected by a specific JS datatable library.
 *
 * Drivers produce a plain PHP array (not HTML, not JSON strings).
 * The consumer is responsible for json_encode() and embedding in templates.
 *
 * Example:
 *
 *     class TabulatorDriver implements DriverInterface
 *     {
 *         public function get_name(): string { return 'tabulator'; }
 *
 *         public function render(DataSet $dataset): array
 *         {
 *             // Build Tabulator-specific config array
 *             return ['columns' => [...], 'ajaxURL' => '...', ...];
 *         }
 *     }
 */
interface DriverInterface
{
    /**
     * Get the driver name.
     *
     * Used for registration and identification.
     *
     * @return string E.g. 'tabulator', 'datatables', 'ag-grid'
     */
    public function get_name(): string;

    /**
     * Render a DataSet into the driver-specific configuration array.
     *
     * The returned array should be ready for json_encode() and direct
     * use by the JS library's constructor/initialization.
     *
     * @param DataSet $dataset The dataset to render (may be a DataTree)
     * @return array The JS library configuration
     */
    public function render(DataSet $dataset): array;
}
