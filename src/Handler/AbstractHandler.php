<?php

declare(strict_types=1);

namespace Alarm\Handler;

use Alarm\Contract\HandlerInterface;
use Alarm\Contract\Record;
use Alarm\Contract\RobotInterface;
use SplQueue;

/**
 * Class AbstractHandler
 * @package Alarm\Handler
 */
abstract class AbstractHandler implements HandlerInterface
{
    /**
     * @var SplQueue
     */
    protected SplQueue $queue;

    protected int $count = 0;

    public function __construct()
    {
        $this->queue = new SplQueue();
    }

    public function send(Record $record)
    {
        $i = $this->count;
        while ($i--) {
            /**
             * @var $robot RobotInterface
             */
            $robot = $this->queue->shift();
            if ($robot->push($record)) {
                $this->queue->push($robot);
                break;
            }
            $this->queue->push($robot);
        }
    }

    final protected function enqueue(RobotInterface $handler)
    {
        $this->queue->push($handler);
        $this->count += 1;
    }
}
