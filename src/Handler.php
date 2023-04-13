<?php

declare(strict_types=1);

namespace Alarm;

use Alarm\Contract\Manager;
use Alarm\Contract\Record;
use Alarm\Exception\InvalidConfigException;
use Monolog\Handler\AbstractHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Class Handler.
 */
class Handler extends AbstractHandler
{
    /**
     * @var array
     */
    protected array $handlers = [];

    public function __construct(array $handlers, Level $level = Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        if (empty($handlers)) {
            throw new InvalidConfigException('Parameter $handlers is invalid.');
        }
        $this->handlers = $handlers;
    }

    public function handle(LogRecord $record): bool
    {
        if ($this->isHandling($record)) {
            $data = new Record();
            $data->handlers = $this->handlers;
            $data->message = $record['message'];
            $data->context = $record['context'];
            $data->level = $record['level_name'];
            $data->datetime = $record['datetime'];
            $data->extra = $record['extra'];
            Manager::send($data);
        }
        return false === $this->bubble;
    }
}
