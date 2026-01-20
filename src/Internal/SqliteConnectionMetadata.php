<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal;

class SqliteConnectionMetadata
{
    public int $affectedRows = 0;
    public int $insertId = 0;
    public int $statusFlags = 0;
    public null|int $warnings = null;
    public null|string $statusInfo = null;
    public array $sessionState = [];
    public null|string $errorMsg = null;
    public null|int $errorCode = null;
    public null|string $errorState = null; // begins with "#"

    public string $sqliteVersion = '';

    public int $charset = 0;
}
