<?php

declare(strict_types=1);

namespace Alarm;

use Alarm\Contract\AlarmInterface;
use Alarm\Handler\HandlerFactory;
use Hyperf\Process\Exception\SocketAcceptException;
use Hyperf\Process\ProcessCollector;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine\Server as CoServer;
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
        $process->pushCh($record, self::$sendTimeout);
    }

    /**
     * @param CoServer|Server $server
     * @return bool
     */
    public function isEnable($server): bool
    {
        return true;
    }

    /**
     * @param CoServer|Server $server
     */
    public function bind($server): void
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
                if ($record === false && $sock->errCode !== SOCKET_ETIMEDOUT) {
                    $sock->close();
                    throw new SocketAcceptException('Socket is closed', $sock->errCode);
                }
                if ($record && !$broker->isFull()) {
                    $broker->push($record, 0.01);
                }
            } catch (Throwable $throwable) {
                if ($throwable instanceof SocketAcceptException) {
                    Coroutine::sleep(3);
                } else {
                    $this->logThrowable($throwable);
                }
            }
        }
    }

    /**
     * @param Throwable $throwable
     */
    protected function logThrowable(Throwable $throwable): void
    {
        if ($this->container->has(\Hyperf\Contract\StdoutLoggerInterface::class) && $this->container->has(\Hyperf\ExceptionHandler\Formatter\FormatterInterface::class)) {
            $logger = $this->container->get(\Hyperf\Contract\StdoutLoggerInterface::class);
            $formatter = $this->container->get(\Hyperf\ExceptionHandler\Formatter\FormatterInterface::class);
            $logger->error($formatter->format($throwable));
            if ($throwable instanceof SocketAcceptException) {
                $logger->critical('Socket of process is unavailable, please restart the server');
            }
        }
    }
}
