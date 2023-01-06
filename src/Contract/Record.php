<?php

declare(strict_types=1);

namespace Alarm\Contract;

use DateTime;
use Monolog\DateTimeImmutable;
use Psr\Log\LogLevel;

/**
 * Class Record
 * @package Alarm\Contract
 */
class Record
{
    /**
     * 本条日志需要被什么handler处理.
     * @var array
     */
    public array $handlers = [];

    /**
     * 日志信息.
     * @var string
     */
    public string $message = '';

    /**
     * 日志上下文.
     * @var array
     */
    public array $context = [];

    /**
     * 日志级别.
     * @var string
     */
    public string $level = LogLevel::DEBUG;

    /**
     * 日志时间.
     * @var DateTime|DateTimeImmutable
     */
    public DateTime|DateTimeImmutable $datetime;

    /**
     * 日志扩展信息.
     * @var array
     */
    public array $extra = [];
}
