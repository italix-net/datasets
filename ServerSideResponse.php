<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets - ServerSideResponse
 *
 * @package Italix\DataSets
 * @license MPL-2.0
 */

declare(strict_types=1);

namespace Italix\DataSets;

use RuntimeException;

/**
 * Server-side response wrapper for datatable AJAX endpoints.
 *
 * Wraps the data rows and pagination metadata into the format
 * expected by the JS datatable library.
 *
 * Example:
 *
 *     $rows = $query->all();      // your data
 *     $total = $query->count();   // total row count (before pagination)
 *
 *     $response = new ServerSideResponse($rows, $total, $request->page(), $request->per_page());
 *     header('Content-Type: application/json');
 *     echo $response->to_json();
 */
class ServerSideResponse
{
    /** @var array */
    private $data;

    /** @var int Total number of rows (before pagination) */
    private $total;

    /** @var int Current page (1-based) */
    private $page;

    /** @var int Rows per page */
    private $per_page;

    /**
     * @param array $data The rows for the current page
     * @param int $total Total number of rows (before pagination)
     * @param int $page Current page number (1-based)
     * @param int $per_page Rows per page
     */
    public function __construct(array $data, int $total, int $page = 1, int $per_page = 25)
    {
        $this->data = $data;
        $this->total = $total;
        $this->page = max(1, $page);
        $this->per_page = max(1, $per_page);
    }

    /**
     * Create from a ServerSideRequest (extracts page and per_page).
     *
     * @param array $data
     * @param int $total
     * @param ServerSideRequest $request
     * @return self
     */
    public static function from_request(array $data, int $total, ServerSideRequest $request): self
    {
        return new self($data, $total, $request->page(), $request->per_page());
    }

    /**
     * Get the last page number.
     *
     * @return int
     */
    public function last_page(): int
    {
        return max(1, (int)ceil($this->total / $this->per_page));
    }

    /**
     * Export as an array in the standard format.
     *
     * The format is compatible with Tabulator's progressive ajax
     * pagination and other common datatable libraries.
     *
     * @return array
     */
    public function to_array(): array
    {
        return [
            'data' => $this->data,
            'last_page' => $this->last_page(),
            'page' => $this->page,
            'per_page' => $this->per_page,
            'total' => $this->total,
        ];
    }

    /**
     * Export as JSON string.
     *
     * @param int $flags JSON encoding flags
     * @return string
     */
    public function to_json(int $flags = 0): string
    {
        $json = json_encode($this->to_array(), $flags);
        if ($json === false) {
            throw new RuntimeException(
                'Failed to encode response to JSON: ' . json_last_error_msg()
            );
        }
        return $json;
    }

    /**
     * Send as an HTTP JSON response and exit.
     *
     * Sets the Content-Type header and outputs the JSON body.
     *
     * @param int $flags JSON encoding flags
     * @return void
     */
    public function send(int $flags = 0): void
    {
        header('Content-Type: application/json');
        echo $this->to_json($flags);
    }

    // =========================================================================
    // Getters
    // =========================================================================

    /** @return array */
    public function get_data(): array
    {
        return $this->data;
    }

    /** @return int */
    public function get_total(): int
    {
        return $this->total;
    }

    /** @return int */
    public function get_page(): int
    {
        return $this->page;
    }

    /** @return int */
    public function get_per_page(): int
    {
        return $this->per_page;
    }
}
