<?php

declare(strict_types=1);

namespace Alarm;

use Alarm\Exception\InvalidConfigException;
use Monolog\Handler\AbstractHandler;
use Monolog\Logger;

/**
 * Class Handler.
 */
class Handler extends AbstractHandler
{
    /**
     * @var array
     */
    protected $handlers = [];

    public function __construct(array $handlers, $level = Logger::DEBUG)
    {
        //此处强制为true，因为接下来的逻辑可能会丢失日志
        parent::__construct($level, true);
        if (empty($handlers)) {
            throw new InvalidConfigException('Parameter $handlers is invalid.');
        }
        $this->handlers = $handlers;
    }

    public function handle(array $record): bool
    {
        $data = new Record();
        $data->handlers = $this->handlers;
        $data->message = $record['message'];
        $data->context = $record['context'];
        $data->level = $record['level_name'];
        $data->datetime = $record['datetime'];
        $data->extra = $record['extra'];
        \Hyperf\Utils\ApplicationContext::getContainer()->get(Alarm::class)::send($data);
        return $this->getBubble();
    }
}
