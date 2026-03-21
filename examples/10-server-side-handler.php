<?php
/**
 * Example 10 — Server-Side Request Handler
 *
 * Shows how to handle the AJAX requests sent by the DataSet table
 * on the server side, using ServerSideRequest and ServerSideResponse.
 *
 * This would typically live in your API controller or route handler.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Italix\DataSets\ServerSideRequest;
use Italix\DataSets\ServerSideResponse;

// ============================================================================
// Parse the incoming request
// ============================================================================

// From PHP superglobals (auto-detects GET vs POST):
$request = ServerSideRequest::from_globals();

// Or from a framework's request object:
// $request = ServerSideRequest::from_array($frameworkRequest->all());

// ============================================================================
// Build your database query using the parsed parameters
// ============================================================================

// --- Pagination ---
$page     = $request->page();          // int, 1-based
$per_page = $request->per_page();      // int, default 25
$offset   = $request->offset();        // int, calculated

// --- Sorting ---

// Simple single-column sort:
$sort_col = $request->sort_column('created_at');  // string or null
$sort_dir = $request->sort_direction('desc');      // 'asc' or 'desc'

// Multi-column sort (when user shift-clicks multiple headers):
$sorts = $request->sorts();
// Returns: [
//   ['column' => 'status', 'direction' => 'asc'],
//   ['column' => 'created_at', 'direction' => 'desc'],
// ]

// --- Global search ---
$search_query   = $request->search();          // string or null
$search_columns = $request->search_columns();  // e.g. ['name', 'email']

// --- Column filters (from header filters) ---
$filters = $request->filters();  // e.g. ['role' => 'admin', 'status' => 'active']
$role_filter = $request->filter('role');  // 'admin' or null

// --- Raw access ---
$tenant = $request->get('tenant');  // any custom param from ajax_params()


// ============================================================================
// Example: PDO query builder
// ============================================================================

$where_clauses = [];
$bind_params = [];

// Global search: OR across searchable columns
if ($search_query !== null && !empty($search_columns)) {
    $search_or = [];
    foreach ($search_columns as $i => $col) {
        // IMPORTANT: validate $col against allowed column names!
        $allowed = ['name', 'email', 'role'];
        if (in_array($col, $allowed, true)) {
            $param = ':search_' . $i;
            $search_or[] = "{$col} LIKE {$param}";
            $bind_params[$param] = '%' . $search_query . '%';
        }
    }
    if (!empty($search_or)) {
        $where_clauses[] = '(' . implode(' OR ', $search_or) . ')';
    }
}

// Column filters: AND
foreach ($filters as $col => $value) {
    $allowed = ['role', 'status'];
    if (in_array($col, $allowed, true)) {
        $param = ':filter_' . $col;
        $where_clauses[] = "{$col} = {$param}";
        $bind_params[$param] = $value;
    }
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Sorting (validate column names!)
$order_parts = [];
$allowed_sort = ['name', 'email', 'role', 'created_at', 'status'];

if (!empty($sorts)) {
    // Multi-column sort
    foreach ($sorts as $sort) {
        if (in_array($sort['column'], $allowed_sort, true)) {
            $dir = $sort['direction'] === 'desc' ? 'DESC' : 'ASC';
            $order_parts[] = "{$sort['column']} {$dir}";
        }
    }
} elseif ($sort_col !== null && in_array($sort_col, $allowed_sort, true)) {
    // Single-column sort fallback
    $dir = $sort_dir === 'desc' ? 'DESC' : 'ASC';
    $order_parts[] = "{$sort_col} {$dir}";
}

$order_sql = !empty($order_parts) ? 'ORDER BY ' . implode(', ', $order_parts) : '';

// Count total
$count_sql = "SELECT COUNT(*) FROM users {$where_sql}";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($bind_params);
$total = (int)$count_stmt->fetchColumn();

// Fetch the page
$data_sql = "SELECT id, name, email, role, created_at FROM users {$where_sql} {$order_sql} LIMIT :limit OFFSET :offset";
$data_stmt = $pdo->prepare($data_sql);
foreach ($bind_params as $k => $v) {
    $data_stmt->bindValue($k, $v);
}
$data_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$data_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$data_stmt->execute();
$rows = $data_stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================================
// Build and send the response
// ============================================================================

// Option A: Build manually
$response = new ServerSideResponse($rows, $total, $page, $per_page);

// Option B: Build from the request (auto-extracts page and per_page)
$response = ServerSideResponse::from_request($rows, $total, $request);

// Inspect before sending:
// $response->to_array() returns:
// [
//     'data'      => [...rows...],
//     'total'     => 150,
//     'page'      => 3,
//     'per_page'  => 25,
//     'last_page' => 6,
// ]

// Send as JSON HTTP response (sets Content-Type header and exits):
$response->send();
