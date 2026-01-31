<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Phenix\Sqlite\Constants\SqliteDataType;

class SqliteDataTypeTest extends TestCase
{
    /** @test */
    public function it_from_declared_type_affinity(): void
    {
        $this->assertSame(SqliteDataType::Integer, SqliteDataType::fromDeclaredType('INTEGER'));
        $this->assertSame(SqliteDataType::Integer, SqliteDataType::fromDeclaredType('int'));
        $this->assertSame(SqliteDataType::Text, SqliteDataType::fromDeclaredType('text'));
        $this->assertSame(SqliteDataType::Text, SqliteDataType::fromDeclaredType('varchar'));
        $this->assertSame(SqliteDataType::Text, SqliteDataType::fromDeclaredType('clob'));
        $this->assertSame(SqliteDataType::Blob, SqliteDataType::fromDeclaredType('blob'));
        $this->assertSame(SqliteDataType::Real, SqliteDataType::fromDeclaredType('real'));
        $this->assertSame(SqliteDataType::Real, SqliteDataType::fromDeclaredType('float'));
        $this->assertSame(SqliteDataType::Real, SqliteDataType::fromDeclaredType('double'));
        $this->assertSame(SqliteDataType::Blob, SqliteDataType::fromDeclaredType(''));
        $this->assertSame(SqliteDataType::Real, SqliteDataType::fromDeclaredType('numeric'));
    }

    /** @test */
    public function it_decodes_values(): void
    {
        $this->assertNull(SqliteDataType::Null->decode('any'));
        $this->assertSame(42, SqliteDataType::Integer->decode('42'));
        $this->assertSame(3.14, SqliteDataType::Real->decode('3.14'));
        $this->assertSame('foo', SqliteDataType::Text->decode('foo'));
        $this->assertSame('bar', SqliteDataType::Blob->decode('bar'));
    }

    /** @test */
    public function it_encodes_values(): void
    {
        $this->assertNull(SqliteDataType::Null->encode('any'));
        $this->assertSame(42, SqliteDataType::Integer->encode(42));
        $this->assertSame(42, SqliteDataType::Integer->encode('42'));
        $this->assertSame(3.14, SqliteDataType::Real->encode(3.14));
        $this->assertSame(3.14, SqliteDataType::Real->encode('3.14'));
        $this->assertSame('foo', SqliteDataType::Text->encode('foo'));
        $this->assertSame('bar', SqliteDataType::Blob->encode('bar'));
        $this->assertNull(SqliteDataType::Text->encode(null));
    }

    /** @test */
    public function it_returns_php_type(): void
    {
        $this->assertSame('null', SqliteDataType::Null->getPhpType());
        $this->assertSame('int', SqliteDataType::Integer->getPhpType());
        $this->assertSame('float', SqliteDataType::Real->getPhpType());
        $this->assertSame('string', SqliteDataType::Text->getPhpType());
        $this->assertSame('string', SqliteDataType::Blob->getPhpType());
    }
}
