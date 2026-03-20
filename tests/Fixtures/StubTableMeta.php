<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests\Fixtures;

use Italix\Contracts\ColumnMeta;
use Italix\Contracts\TableMeta;

/**
 * Simple TableMeta stub for testing.
 */
class StubTableMeta implements TableMeta
{
    /** @var array<string, ColumnMeta> */
    private $columns;

    /**
     * @param array<string, ColumnMeta> $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    public function describe_columns(): iterable
    {
        return $this->columns;
    }

    public function describe_column(string $name): ?ColumnMeta
    {
        return $this->columns[$name] ?? null;
    }

    /**
     * Create a typical "users" table for testing.
     *
     * @return self
     */
    public static function users(): self
    {
        return new self([
            'id' => new StubColumnMeta('id', 'INTEGER', false, true),
            'name' => new StubColumnMeta('name', 'VARCHAR', false, false, 255),
            'email' => new StubColumnMeta('email', 'VARCHAR', false, false, 255),
            'created_at' => new StubColumnMeta('created_at', 'DATETIME', false),
        ]);
    }

    /**
     * Create a typical "categories" table with parent_id for tree testing.
     *
     * @return self
     */
    public static function categories(): self
    {
        return new self([
            'id' => new StubColumnMeta('id', 'INTEGER', false, true),
            'name' => new StubColumnMeta('name', 'VARCHAR', false, false, 255),
            'slug' => new StubColumnMeta('slug', 'VARCHAR', false, false, 255),
            'parent_id' => new StubColumnMeta('parent_id', 'INTEGER', true),
        ]);
    }
}
