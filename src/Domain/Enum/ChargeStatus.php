<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Domain\Enum;

enum ChargeStatus: string
{
    case Pending   = 'pending';
    case Succeeded = 'succeeded';
    case Failed    = 'failed';
}
