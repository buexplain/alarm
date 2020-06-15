<?php

declare(strict_types=1);

namespace Alarm;

use Alarm\Contract\AlarmInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                AlarmInterface::class => Alarm::class
            ],
            'processes' => [
                \Alarm\Alarm::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config of alarm.',
                    'source' => __DIR__ . '/../publish/alarm.php',
                    'destination' => BASE_PATH . '/config/autoload/alarm.php',
                ],
            ],
        ];
    }
}
