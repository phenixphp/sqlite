<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Constants;

/**
 * @see https://www.sqlite.org/pragma.html#pragma_synchronous
 */
enum SqliteSynchronous: string
{
    case Off = 'OFF';

    case Normal = 'NORMAL';

    case Full = 'FULL';

    case Extra = 'EXTRA';

    public function toPragmaValue(): string
    {
        return $this->value;
    }
}
