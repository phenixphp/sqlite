<?php declare(strict_types=1);

namespace Phenix\Sqlite;

use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Phenix\Sqlite\Constants\SqliteDataType;

class SqliteColumnDefinition
{
    use ForbidCloning;
    use ForbidSerialization;

    public function __construct(
        private readonly string $table,
        private readonly string $name,
        private readonly int $length,
        private readonly SqliteDataType $type,
        private readonly int $flags,
        private readonly int $decimals,
        private readonly string $defaults = '',
        private readonly string|null $originalTable = null,
        private readonly string|null $originalName = null,
        private readonly string|null $declaredType = null,
        private readonly string|null $schema = null,
    ) {
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getType(): SqliteDataType
    {
        return $this->type;
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    public function getDefaults(): string
    {
        return $this->defaults;
    }

    public function getOriginalTable(): string|null
    {
        return $this->originalTable;
    }

    public function getOriginalName(): string|null
    {
        return $this->originalName;
    }

    public function getDeclaredType(): string|null
    {
        return $this->declaredType;
    }

    public function getSchema(): string|null
    {
        return $this->schema;
    }
}
