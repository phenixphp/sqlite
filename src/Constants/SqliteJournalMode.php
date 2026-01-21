<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Constants;

/**
 * @see https://www.sqlite.org/pragma.html#pragma_journal_mode
 */
enum SqliteJournalMode: string
{
    case Delete = 'DELETE';

    case Truncate = 'TRUNCATE';

    case Persist = 'PERSIST';

    case Memory = 'MEMORY';

    case Wal = 'WAL';

    case Off = 'OFF';

    public function toPragmaValue(): string
    {
        return $this->value;
    }
}
