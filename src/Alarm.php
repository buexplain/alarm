<?php

declare(strict_types=1);

namespace Alarm;

use Alarm\Contract\InterfaceProcess;
use Alarm\Contract\Manager;
use Alarm\Contract\Record;
use Alarm\Handler\HandlerFactory;
use Hyperf\Coordinator\Constants;
use Hyperf\Coordinator\CoordinatorManager;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Event\AfterProcessHandle;
use Hyperf\Process\Event\BeforeProcessHandle;
use Hyperf\Process\Exception\ServerInvalidException;
use Hyperf\Process\Exception\SocketAcceptException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Socket;
use Swoole\Process as SwooleProcess;
use Swoole\Server;
use Swoole\Timer;
use Throwable;

/**
 * 自定义日志上报进程
 * 必须在协程环境下使用
 * 不支持协程风格的服务器
 */
class Alarm extends AbstractProcess
{
    /**
     * 进程名称
     * @var string
     */
    public string $name = 'alarm';

    /**
     * @var int
     */
    protected int $recvLength = 65535;

    /**
     * @var float
     */
    protected float $recvTimeout = 5.0;

    /**
     * @var HandlerFactory
     */
    protected HandlerFactory $handlerFactory;

    /**
     * @var null|Process
     */
    protected ?SwooleProcess $process;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->handlerFactory = $this->container->get(HandlerFactory::class);
    }

    public function isEnable($server): bool
    {
        //CYGWIN环境下禁止启动
        return !str_starts_with(strtoupper(PHP_OS), 'CYGWIN');
    }

    public function handle(): void
    {
        while (Manager::isRunning()) {
            try {
                /**
                 * @var Socket $sock
                 */
                $sock = $this->process->exportSocket();

                $data = $sock->recv($this->recvLength, $this->recvTimeout);
                //SOCKET_ETIMEDOUT 110
                if ($data === false && $sock->errCode !== 110) {
                    $sock->close();
                    throw new SocketAcceptException('Socket is closed', $sock->errCode);
                }

                /**
                 * @var Record $record
                 */
                $record = unserialize((string)$data);
                if ($record instanceof Record) {
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
                if ($throwable instanceof SocketAcceptException && $throwable->isTimeout()) {
                    Coroutine::sleep(3);
                } else {
                    $this->logThrowable($throwable);
                }
            }
        }
    }

    /**
     * 重写父类方法
     * 1、不支持协程风格服务
     * @param Coroutine\Http\Server|Coroutine\Server|Server $server
     */
    public function bind($server): void
    {
        if (!$server instanceof Server) {
            throw new ServerInvalidException(sprintf('Server %s not supported.', get_class($server)));
        }
        $this->bindServer($server);
    }

    /**
     * 重写父类方法
     * 1、不支持接收worker/task的数据事件
     * 2、强制开启协程
     */
    protected function bindServer(Server $server): void
    {
        /**
         * @var $process SwooleProcess|InterfaceProcess
         */
        $process = new Process(function (SwooleProcess $process) {
            try {
                $this->event && $this->event->dispatch(new BeforeProcessHandle($this, 0));
                $this->process = $process;
                $this->handle();
            } catch (Throwable $throwable) {
                $this->logThrowable($throwable);
            } finally {
                $this->event && $this->event->dispatch(new AfterProcessHandle($this, 0));
                Timer::clearAll();
                CoordinatorManager::until(Constants::WORKER_EXIT)->resume();
                sleep($this->restartInterval);
            }
            // SOCK_DGRAM 2
        }, false, 2, true);
        $server->addProcess($process);
        Manager::setProcess($process);
    }
}
