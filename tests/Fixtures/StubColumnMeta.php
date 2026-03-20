<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests\Fixtures;

use Italix\Contracts\ColumnMeta;

/**
 * Simple ColumnMeta stub for testing.
 */
class StubColumnMeta implements ColumnMeta
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var bool */
    private $nullable;

    /** @var bool */
    private $primary_key;

    /** @var int|null */
    private $length;

    /** @var mixed */
    private $default;

    /** @var bool */
    private $has_default;

    /**
     * @param string $name
     * @param string $type
     * @param bool $nullable
     * @param bool $primary_key
     * @param int|null $length
     * @param mixed $default
     * @param bool $has_default
     */
    public function __construct(
        string $name,
        string $type = 'VARCHAR',
        bool $nullable = false,
        bool $primary_key = false,
        ?int $length = null,
        $default = null,
        bool $has_default = false
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->primary_key = $primary_key;
        $this->length = $length;
        $this->default = $default;
        $this->has_default = $has_default;
    }

    public function get_name(): string
    {
        return $this->name;
    }

    public function get_type(): string
    {
        return $this->type;
    }

    public function is_nullable(): bool
    {
        return $this->nullable;
    }

    public function is_primary_key(): bool
    {
        return $this->primary_key;
    }

    public function get_length(): ?int
    {
        return $this->length;
    }

    public function get_default()
    {
        return $this->default;
    }

    public function has_default(): bool
    {
        return $this->has_default;
    }
}
