<?php

declare(strict_types=1);

namespace Alarm\Contract;

use Alarm\Record;

/**
 * Interface HandlerInterface.
 */
interface HandlerInterface
{
    public function send(Record $record);
}
