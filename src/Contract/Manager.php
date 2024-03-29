<?php

declare(strict_types=1);

namespace Alarm\Contract;

/**
 * Class Manager
 * @package Alarm\Contract
 */
class Manager
{
    /**
     * @var null|InterfaceProcess
     */
    protected static ?InterfaceProcess $process;

    /**
     * @var bool
     */
    protected static bool $running = true;

    public static function isRunning(): bool
    {
        return static::$running;
    }

    public static function setRunning(bool $running): void
    {
        static::$running = $running;
    }

    public static function setProcess(InterfaceProcess $process): void
    {
        self::$process = $process;
    }

    public static function send(Record $record, $timeout = 0.01): void
    {
        if (self::$running) {
            self::$process->send($record, $timeout);
        }
    }
}
