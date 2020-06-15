<?php

declare(strict_types=1);

namespace Alarm;

use Alarm\Contract\AlarmInterface;
use Alarm\Exception\InvalidConfigException;
use Monolog\Handler\AbstractHandler;
use Monolog\Logger;

/**
 * Class Handler
 * @package Alarm
 */
class Handler extends AbstractHandler
{
    /**
     * @var AlarmInterface
     */
    protected $alarm;

    /**
     * @var array
     */
    protected $handlers = [];

    /**
     * Handler constructor.
     * @param AlarmInterface $alarm
     * @param array $handlers
     * @param int $level
     */
    public function __construct(AlarmInterface $alarm, array $handlers, $level=Logger::DEBUG)
    {
        //此处强制为true，因为接下来的逻辑可能会丢失日志
        parent::__construct($level, true);
        if (empty($handlers)) {
            throw new InvalidConfigException(sprintf('Parameter $handlers of %s is invalid.', __CLASS__.'::'.__FUNCTION__));
        }
        $this->alarm = $alarm;
        $this->handlers = $handlers;
    }

    /**
     * @param array $record
     * @return bool
     */
    public function handle(array $record)
    {
        $data = new Record();
        $data->handlers = $this->handlers;
        $data->message = $record['message'];
        $data->context = $record['context'];
        $data->level = $record['level_name'];
        $data->datetime = $record['datetime'];
        $data->extra = $record['extra'];
        $this->alarm::send($data);
        return $this->getBubble();
    }
}
