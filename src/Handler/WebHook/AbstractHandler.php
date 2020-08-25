<?php

declare(strict_types=1);

namespace Alarm\Handler\WebHook;

use Alarm\Contract\HandlerInterface;
use Alarm\Record;
use SplQueue;

/**
 * Class AbstractHandler.
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

    public function send(Record $record)
    {
        /**
         * @var HandlerInterface $robot
         */
        $robot = $this->queue->shift();
        $robot->send($record);
        $this->queue->push($robot);
    }

    final protected function enqueue(HandlerInterface $handler)
    {
        $this->queue->push($handler);
    }
}
