<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Constants;

use function is_float;
use function is_int;
use function str_contains;
use function strtoupper;
use function trim;

/**
 * SQLite data types based on storage classes (type affinity).
 *
 * @see https://www.sqlite.org/datatype3.html
 */
enum SqliteDataType: int
{
    // SQLite storage classes
    case Null = 0x00;
    case Integer = 0x01;
    case Real = 0x02;
    case Text = 0x03;
    case Blob = 0x04;

    /**
     * Map declared type names to SQLite storage classes.
     *
     * @see https://www.sqlite.org/datatype3.html#type_affinity
     */
    public static function fromDeclaredType(string $declaredType): self
    {
        $declaredType = strtoupper(trim($declaredType));

        // NULL type affinity
        if ($declaredType === '') {
            return self::Blob;
        }

        // INTEGER type affinity
        if (str_contains($declaredType, 'INT')) {
            return self::Integer;
        }

        // TEXT type affinity
        if (str_contains($declaredType, 'CHAR') ||
            str_contains($declaredType, 'CLOB') ||
            str_contains($declaredType, 'TEXT')) {
            return self::Text;
        }

        // BLOB type affinity
        if ($declaredType === 'BLOB') {
            return self::Blob;
        }

        // REAL type affinity
        if (str_contains($declaredType, 'REAL') ||
            str_contains($declaredType, 'FLOA') ||
            str_contains($declaredType, 'DOUB')) {
            return self::Real;
        }

        // NUMERIC type affinity (maps to REAL for simplification)
        return self::Real;
    }

    public function decode(mixed $value): int|float|string|null
    {
        return match ($this) {
            self::Null => null,
            self::Integer => (int) $value,
            self::Real => (float) $value,
            self::Text => (string) $value,
            self::Blob => (string) $value,
        };
    }

    public function encode(mixed $value): int|float|string|null
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::Null => null,
            self::Integer => is_int($value) ? $value : (int) $value,
            self::Real => is_float($value) ? $value : (float) $value,
            self::Text => (string) $value,
            self::Blob => (string) $value,
        };
    }

    public function getPhpType(): string
    {
        return match ($this) {
            self::Null => 'null',
            self::Integer => 'int',
            self::Real => 'float',
            self::Text => 'string',
            self::Blob => 'string',
        };
    }
}
