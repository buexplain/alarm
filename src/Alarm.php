<?php

declare(strict_types=1);

namespace Alarm;

use Alarm\Handler\HandlerFactory;
use Hyperf\Process\AbstractProcess as Base;
use Hyperf\Process\Event\AfterProcessHandle;
use Hyperf\Process\Event\BeforeProcessHandle;
use Hyperf\Process\Exception\SocketAcceptException;
use Hyperf\Process\ProcessCollector;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Socket;
use Swoole\Server;
use Swoole\Timer;
use Throwable;

class Alarm extends Base
{
    /**
     * 进程名称.
     * @var string
     */
    const NAME = 'alarm';

    /**
     * @var bool
     */
    public static $running = true;

    public $name = self::NAME;

    /**
     * @var int
     */
    public $nums = 1;

    /**
     * @var bool
     */
    public $redirectStdinStdout = false;

    /**
     * @var int
     */
    public $pipeType = SOCK_DGRAM;

    /**
     * @var bool
     */
    public $enableCoroutine = true;

    /**
     * @var float
     */
    protected static $sendTimeout = 0.01;

    /**
     * @var HandlerFactory
     */
    protected $handlerFactory;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->handlerFactory = $this->container->get(HandlerFactory::class);
    }

    public static function send(Record $record)
    {
        $process = ProcessCollector::get(self::NAME);
        if (empty($process) || empty($record->handlers)) {
            return;
        }
        /**
         * @var Process $process
         */
        $process = $process[0];
        $process->pushCh($record, self::$sendTimeout);
    }

    public function handle(): void
    {
        while (self::$running) {
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

                /**
                 * @var Record $record
                 */
                $record = unserialize((string) $record);
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
                if ($throwable instanceof SocketAcceptException) {
                    Coroutine::sleep(3);
                } else {
                    $this->logThrowable($throwable);
                }
            }
        }
    }

    protected function bindServer(Server $server): void
    {
        $num = $this->nums;
        for ($i = 0; $i < $num; ++$i) {
            $process = new Process(function (Process $process) use ($i) {
                try {
                    $this->event && $this->event->dispatch(new BeforeProcessHandle($this, $i));

                    $this->process = $process;

                    $this->handle();

                    $this->event && $this->event->dispatch(new AfterProcessHandle($this, $i));
                } catch (\Throwable $throwable) {
                    $this->logThrowable($throwable);
                } finally {
                    Timer::clearAll();
                    sleep($this->restartInterval);
                }
            }, $this->redirectStdinStdout, $this->pipeType, $this->enableCoroutine);
            $server->addProcess($process);

            if ($this->enableCoroutine) {
                ProcessCollector::add($this->name, $process);
            }
        }
    }
}
