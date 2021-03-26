<?php

declare(strict_types=1);

namespace Alarm\Contract;

interface InterfaceProcess
{
    public function send(Record $record, float $timeout);
}
