<?php

declare(strict_types=1);

namespace Alarm\Signal;

use Alarm\Alarm;
use Hyperf\Signal\SignalHandlerInterface;

class AlarmStopHandler implements SignalHandlerInterface
{
    public function listen(): array
    {
        return [
            [self::PROCESS, SIGTERM],
        ];
    }

    public function handle(int $signal): void
    {
        Alarm::$running = false;
    }
}
