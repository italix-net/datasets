<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests;

use Italix\DataSets\Drivers\DriverRegistry;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DriverRegistryTest extends TestCase
{
    public function test_register_and_get(): void
    {
        $registry = new DriverRegistry();
        $driver = new TabulatorDriver();
        $registry->register($driver);

        $this->assertSame($driver, $registry->get('tabulator'));
    }

    public function test_has(): void
    {
        $registry = new DriverRegistry();
        $this->assertFalse($registry->has('tabulator'));

        $registry->register(new TabulatorDriver());
        $this->assertTrue($registry->has('tabulator'));
    }

    public function test_get_throws_on_missing(): void
    {
        $registry = new DriverRegistry();

        $this->expectException(InvalidArgumentException::class);
        $registry->get('nonexistent');
    }

    public function test_all(): void
    {
        $registry = new DriverRegistry();
        $driver = new TabulatorDriver();
        $registry->register($driver);

        $all = $registry->all();
        $this->assertCount(1, $all);
        $this->assertSame($driver, $all['tabulator']);
    }
}
