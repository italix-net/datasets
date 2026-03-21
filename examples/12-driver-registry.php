<?php
/**
 * Example 12 — Driver Registry & Multiple Drivers
 *
 * Shows how to use the DriverRegistry to register and switch between
 * different datatable drivers. This is useful when your application
 * needs to support multiple JS libraries or custom renderers.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Italix\DataSets\DataSet;
use Italix\DataSets\Drivers\DriverInterface;
use Italix\DataSets\Drivers\DriverRegistry;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

// ============================================================================
// Register available drivers
// ============================================================================

$registry = new DriverRegistry();
$registry->register(new TabulatorDriver());

// You could register additional custom drivers:
// $registry->register(new AgGridDriver());
// $registry->register(new DataTablesDriver());

// ============================================================================
// Use by name (e.g. from a config file or user preference)
// ============================================================================

$driver_name = 'tabulator';  // Could come from config: $config['datatable_driver']

if ($registry->has($driver_name)) {
    $driver = $registry->get($driver_name);
} else {
    throw new RuntimeException("Unknown datatable driver: {$driver_name}");
}

$ds = new DataSet($usersTable);
$ds->columns(['name', 'email']);
$ds->ajax_url('/api/users');

$config = $ds->render($driver);
$json = $ds->to_json($driver);

// ============================================================================
// Implementing a custom driver
// ============================================================================

/*
 * To create your own driver, implement DriverInterface:
 *
 * class MyCustomDriver implements DriverInterface
 * {
 *     public function get_name(): string
 *     {
 *         return 'my-custom';
 *     }
 *
 *     public function render(DataSet $dataset): array
 *     {
 *         $config = [];
 *
 *         // Map DataSet configuration to your library's format
 *         foreach ($dataset->each_column() as $name => $col) {
 *             $config['fields'][] = [
 *                 'key'   => $col->get_name(),
 *                 'label' => $col->get_label(),
 *                 // ... your library's column options
 *             ];
 *         }
 *
 *         if ($dataset->get_ajax_url() !== null) {
 *             $config['dataSource'] = $dataset->get_ajax_url();
 *         }
 *
 *         return $config;
 *     }
 * }
 *
 * $registry->register(new MyCustomDriver());
 */
