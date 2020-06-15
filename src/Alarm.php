<?php

declare(strict_types=1);

namespace Alarm;

use Alarm\Contract\AlarmInterface;
use Alarm\Handler\HandlerFactory;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\Process\Exception\SocketAcceptException;
use Hyperf\Process\ProcessCollector;
use Psr\Container\ContainerInterface;
use Swoole\Process;
use Swoole\Server;
use Swoole\Timer;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine;
use Swoole\Coroutine\System;
use Swoole\Coroutine\Socket;
use Throwable;

/**
 * Class Alarm
 * @package Alarm
 */
class Alarm implements AlarmInterface
{
    /**
     * 进程名称
     * @var string
     */
    const NAME = 'alarm-process';

    public $name = self::NAME;

    /**
     * @var int
     */
    protected $recvLength = 65535;

    /**
     * @var float
     */
    protected $recvTimeout = -1;

    /**
     * @var float
     */
    protected static $sendTimeout = 0.05;

    /**
     * @var int
     */
    protected $restartInterval = 5;

    /**
     * @var Process
     */
    protected $process;


    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var HandlerFactory
     */
    protected $handlerFactory;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->handlerFactory = $this->container->get(HandlerFactory::class);
    }

    /**
     * @param Record $record
     */
    public static function send(Record $record)
    {
        $process = ProcessCollector::get(self::NAME);
        if (empty($process) || empty($record->handlers)) {
            return;
        }
        /**
         * @var $process Process
         */
        $process = $process[0];
        try {
            $data = serialize($record);
            if ($data != '') {
                $process->exportSocket()->send($data, self::$sendTimeout);
            }
        } catch (Throwable $throwable) {
            $error = new Record();
            $error->message = sprintf('%s in %s on line %d', $throwable->getMessage(), $throwable->getFile(), $throwable->getLine());
            $error->level = $record->level;
            $error->datetime = $record->datetime;
            $error->handlers = $record->handlers;
            $data = serialize($error);
            $process->exportSocket()->send($data, self::$sendTimeout);
        }
    }

    /**
     * @return bool
     */
    public function isEnable(): bool
    {
        return true;
    }

    /**
     * @param Server $server
     */
    public function bind(Server $server): void
    {
        $process = new Process(function (Process $process) {
            try {
                $this->process = $process;
                $this->handle();
            } catch (Throwable $throwable) {
                $this->logThrowable($throwable);
            } finally {
                Timer::clearAll();
                System::sleep($this->restartInterval);
            }
        }, false, 2, true);
        $server->addProcess($process);
        ProcessCollector::add($this->name, $process);
    }

    public function handle(): void
    {
        $broker = new Channel(20);

        Coroutine::create(function () use ($broker) {
            while (true) {
                try {
                    $record = $broker->pop();
                    /**
                     * @var $record Record
                     */
                    $record = unserialize((string)$record);
                    if ($record !== false && ($record instanceof Record)) {
                        foreach ($record->handlers as $name) {
                            try {
                                $handler = $this->handlerFactory->get($name);
                                $handler->send($record);
                            } catch (Throwable $throwable) {
                                $this->logThrowable($throwable);
                            }
                        }
                    }
                } catch (Throwable $throwable) {
                    $this->logThrowable($throwable);
                }
            }
        });

        while (true) {
            try {
                /**
                 * @var Socket $sock
                 */
                $sock = $this->process->exportSocket();
                $record = $sock->recv($this->recvLength, $this->recvTimeout);
                if ($record === '') {
                    throw new SocketAcceptException('Socket is closed', $sock->errCode);
                }
                if ($record === false && $sock->errCode !== SOCKET_ETIMEDOUT) {
                    throw new SocketAcceptException('Socket is closed', $sock->errCode);
                }
                if ($record && !$broker->isFull()) {
                    $broker->push($record, 0.01);
                }
            } catch (Throwable $throwable) {
                $this->logThrowable($throwable);
                if ($throwable instanceof SocketAcceptException) {
                    Coroutine::sleep(2);
                }
            }
        }
    }

    /**
     * @param Throwable $throwable
     */
    protected function logThrowable(Throwable $throwable): void
    {
        if ($this->container->has(StdoutLoggerInterface::class) && $this->container->has(FormatterInterface::class)) {
            $logger = $this->container->get(StdoutLoggerInterface::class);
            $formatter = $this->container->get(FormatterInterface::class);
            $logger->error($formatter->format($throwable));
            if ($throwable instanceof SocketAcceptException) {
                $logger->critical('Socket of process is unavailable, please restart the server');
            }
        }
    }
}
