<?php

declare(strict_types=1);

namespace Phenix\Sqlite;

function connect(SqliteConfig|null $config = null): SqliteConnection
{
    $config ??= new SqliteConfig(':memory:');

    return SqliteConnection::connect($config);
}
