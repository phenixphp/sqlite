<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Phenix\Sqlite\SqliteColumnDefinition;
use Phenix\Sqlite\Constants\SqliteDataType;

class SqliteColumnDefinitionTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_column_definition(): void
    {
        $columnDefinition = new SqliteColumnDefinition(
            table: 'users',
            name: 'id',
            length: 11,
            type: SqliteDataType::Integer,
            flags: 0,
            decimals: 0,
            defaults: '0',
            originalTable: 'users',
            originalName: 'id',
            declaredType: 'INTEGER',
            schema: 'main',
        );

        $this->assertInstanceOf(SqliteColumnDefinition::class, $columnDefinition);
        $this->assertSame('users', $columnDefinition->getTable());
        $this->assertSame('id', $columnDefinition->getName());
        $this->assertSame(11, $columnDefinition->getLength());
        $this->assertSame(SqliteDataType::Integer, $columnDefinition->getType());
        $this->assertSame(0, $columnDefinition->getFlags());
        $this->assertSame(0, $columnDefinition->getDecimals());
        $this->assertSame('0', $columnDefinition->getDefaults());
        $this->assertSame('users', $columnDefinition->getOriginalTable());
        $this->assertSame('id', $columnDefinition->getOriginalName());
        $this->assertSame('INTEGER', $columnDefinition->getDeclaredType());
        $this->assertSame('main', $columnDefinition->getSchema());
    }
}