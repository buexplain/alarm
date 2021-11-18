<?php

declare(strict_types=1);

namespace Alarm\Contract;

/**
 * Interface RobotInterface
 * @package Alarm\Contract
 */
interface RobotInterface
{
    public function push(Record $record): bool;
}
