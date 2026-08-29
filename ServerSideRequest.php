<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets - ServerSideRequest
 *
 * @package Italix\DataSets
 * @license MPL-2.0
 */

declare(strict_types=1);

namespace Italix\DataSets;

use Italix\Contracts\DataContainer;

/**
 * Parses server-side datatable request parameters.
 *
 * Extracts pagination, sorting, searching, and filtering info from
 * the HTTP request (typically $_GET or $_POST).
 *
 * Example:
 *
 *     // From superglobals
 *     $request = ServerSideRequest::from_globals();
 *
 *     // From a custom array
 *     $request = new ServerSideRequest($params);
 *
 *     // Use with your query builder
 *     $query->order_by($request->sort_column(), $request->sort_direction())
 *           ->limit($request->per_page())
 *           ->offset($request->offset());
 */
class ServerSideRequest
{
    /** @var array */
    private $params;

    /**
     * @param array $params The request parameters
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Create from PHP superglobals ($_GET for GET, $_POST for POST).
     *
     * @return self
     */
    public static function from_globals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $params = strtoupper($method) === 'POST' ? $_POST : $_GET;

        return new self($params);
    }

    /**
     * Create from a custom array (useful for testing or framework integration).
     *
     * @param array $params
     * @return self
     */
    public static function from_array(array $params): self
    {
        return new self($params);
    }

    /**
     * Create from a DataContainer (e.g. from RequestInput::get()).
     *
     * @param DataContainer $data
     * @return self
     */
    public static function from(DataContainer $data): self
    {
        return new self($data->to_array());
    }

    // =========================================================================
    // Pagination
    // =========================================================================

    /**
     * Get the requested page number (1-based).
     *
     * @return int
     */
    public function page(): int
    {
        $page = (int)($this->params['page'] ?? 1);
        return max(1, $page);
    }

    /**
     * Get the number of rows per page.
     *
     * @param int $default
     * @return int
     */
    public function per_page(int $default = 25): int
    {
        $size = (int)($this->params['per_page'] ?? $this->params['size'] ?? $default);
        return max(1, $size);
    }

    /**
     * Get the calculated offset for SQL OFFSET.
     *
     * @param int $default_per_page
     * @return int
     */
    public function offset(int $default_per_page = 25): int
    {
        return ($this->page() - 1) * $this->per_page($default_per_page);
    }

    // =========================================================================
    // Sorting
    // =========================================================================

    /**
     * Get the primary sort column name.
     *
     * For multi-column sorting, use sorts() instead.
     *
     * @param string|null $default
     * @return string|null
     */
    public function sort_column(?string $default = null): ?string
    {
        // Multi-sort format: sort[0][field]=name&sort[0][dir]=asc
        $sorts = $this->sorts();
        if (!empty($sorts)) {
            return $sorts[0]['column'];
        }

        return $this->params['sort'] ?? $this->params['sort_column'] ?? $default;
    }

    /**
     * Get the primary sort direction.
     *
     * For multi-column sorting, use sorts() instead.
     *
     * @param string $default 'asc' or 'desc'
     * @return string
     */
    public function sort_direction(string $default = 'asc'): string
    {
        $sorts = $this->sorts();
        if (!empty($sorts)) {
            return $sorts[0]['direction'];
        }

        $dir = self::direction_code($this->params['sort_dir'] ?? $this->params['sort_direction'] ?? $default, $default);

        return $dir;
    }

    /**
     * Normalise anything at all into `asc` or `desc`.
     *
     * The input is whatever was in the query string, and a query string can
     * hold an array: `?sort_dir[]=asc` is a URL anybody can type, and it
     * reached `strtolower()` as an array and threw a TypeError — a 500 on
     * demand. Non-scalars are refused here rather than cast, because casting an
     * array to string yields the literal word "Array", which is not a
     * direction either but fails much later and much less clearly.
     *
     * @param mixed $value
     */
    private static function direction_code($value, string $default): string
    {
        if (!is_scalar($value)) {
            return $default;
        }

        $dir = strtolower((string) $value);

        return in_array($dir, ['asc', 'desc'], true) ? $dir : $default;
    }

    /**
     * Get all sort columns (for multi-column sorting).
     *
     * Returns an array of ['column' => string, 'direction' => string] pairs.
     * The JS bootstrap sends these as sort[0][field]=name&sort[0][dir]=asc.
     *
     * @return array<array{column: string, direction: string}>
     */
    public function sorts(): array
    {
        $sorts_param = $this->params['sorts'] ?? null;
        if (!is_array($sorts_param) || empty($sorts_param)) {
            return [];
        }

        $result = [];
        foreach ($sorts_param as $sort) {
            if (!is_array($sort) || !isset($sort['field'])) {
                continue;
            }
            // A field sent as `sort[0][field][]=x` arrives as an array, and
            // casting one to string is a warning plus the word "Array".
            if (!is_scalar($sort['field'])) {
                continue;
            }

            $result[] = [
                'column' => (string) $sort['field'],
                'direction' => self::direction_code($sort['dir'] ?? 'asc', 'asc'),
            ];
        }

        return $result;
    }

    // =========================================================================
    // Searching
    // =========================================================================

    /**
     * Get the global search query.
     *
     * @return string|null
     */
    public function search(): ?string
    {
        $q = $this->params['search'] ?? $this->params['q'] ?? null;
        if ($q === null || $q === '') {
            return null;
        }
        return (string)$q;
    }

    /**
     * Get the columns to apply the global search to.
     *
     * The JS bootstrap sends these as search_columns[]=name&search_columns[]=email.
     * If not provided, the backend should search all searchable columns.
     *
     * @return string[]
     */
    public function search_columns(): array
    {
        $columns = $this->params['search_columns'] ?? [];
        if (!is_array($columns)) {
            return [];
        }
        return array_values(array_filter($columns, 'is_string'));
    }

    // =========================================================================
    // Filtering
    // =========================================================================

    /**
     * Get column-level filters.
     *
     * Expects filters as an associative array: filters[column]=value
     *
     * @return array<string, string>
     */
    public function filters(): array
    {
        $filters = $this->params['filters'] ?? [];
        if (!is_array($filters)) {
            return [];
        }

        // Remove empty filter values
        return array_filter($filters, function ($value) {
            return $value !== '' && $value !== null;
        });
    }

    /**
     * Get a specific filter value.
     *
     * @param string $column
     * @return string|null
     */
    public function filter(string $column): ?string
    {
        $filters = $this->filters();
        return isset($filters[$column]) ? (string)$filters[$column] : null;
    }

    // =========================================================================
    // Raw Access
    // =========================================================================

    /**
     * Get any raw parameter by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Get all raw parameters.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->params;
    }
}
