<?php

declare(strict_types=1);

namespace Alarm;

use Psr\Log\LogLevel;
use DateTime;

/**
 * Class Record
 * @package Alarm
 */
class Record
{
    /**
     * 本条日志需要被什么handler处理
     * @var array
     */
    public $handlers = [];

    /**
     * 日志信息
     * @var string
     */
    public $message = '';

    /**
     * 日志上下文
     * @var array
     */
    public $context = [];

    /**
     * 日志级别
     * @var string
     */
    public $level = LogLevel::DEBUG;

    /**
     * 日志时间
     * @var DateTime
     */
    public $datetime;

    /**
     * 日志扩展信息
     * @var array
     */
    public $extra = [];
}
