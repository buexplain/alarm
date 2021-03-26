<?php

declare(strict_types=1);

namespace Alarm\Contract;

/**
 * Interface HandlerInterface.
 */
interface HandlerInterface
{
    public function send(Record $record);
}
