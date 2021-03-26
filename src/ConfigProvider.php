<?php

declare(strict_types=1);

namespace Alarm;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'processes' => [
                Alarm::class,
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
