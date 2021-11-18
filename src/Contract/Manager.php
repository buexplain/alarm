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
    protected static $process;

    /**
     * @var bool
     */
    protected static $running = true;

    public static function isRunning(): bool
    {
        return static::$running;
    }

    public static function setRunning(bool $running): void
    {
        static::$running = $running;
    }

    public static function setProcess(InterfaceProcess $process)
    {
        self::$process = $process;
    }

    public static function send(Record $record, $timeout = 0.01)
    {
        if (self::$running) {
            self::$process->send($record, $timeout);
        }
    }
}
