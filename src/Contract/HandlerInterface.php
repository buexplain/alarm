<?php

declare(strict_types=1);

namespace Alarm\Contract;

/**
 * Interface HandlerInterface
 * @package Alarm\Contract
 */
interface HandlerInterface
{
    public function send(Record $record);
}
