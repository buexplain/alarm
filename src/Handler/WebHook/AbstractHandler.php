<?php

declare(strict_types=1);

namespace Alarm\Handler\WebHook;

use Alarm\Contract\HandlerInterface;
use Alarm\Record;
use SplQueue;

/**
 * Class AbstractHandler
 * @package Alarm\Handler\WebHook
 */
abstract class AbstractHandler implements HandlerInterface
{
    /**
     * @var SplQueue
     */
    private $queue;

    public function __construct()
    {
        $this->queue = new SplQueue();
    }

    /**
     * @param HandlerInterface $handler
     */
    final protected function enqueue(HandlerInterface $handler)
    {
        $this->queue->push($handler);
    }

    public function send(Record $record)
    {
        /**
         * @var $robot HandlerInterface
         */
        $robot = $this->queue->shift();
        $this->queue->push($robot);
        $robot->send($record);
    }
}
