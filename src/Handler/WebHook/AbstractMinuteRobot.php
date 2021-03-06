<?php

declare(strict_types=1);

namespace Alarm\Handler\WebHook;

use Alarm\Contract\FormatterInterface;
use Alarm\Contract\HandlerInterface;
use Alarm\Contract\Manager;
use Alarm\Contract\Record;
use Alarm\Exception\WaitException;
use GuzzleHttp\Exception\ConnectException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use SplDoublyLinkedList;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Timer;
use Throwable;

/**
 * Class AbstractMinuteRobot.
 */
abstract class AbstractMinuteRobot implements HandlerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * 机器人地址
     * @var string
     */
    protected $url = '';

    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * @var FormatterInterface
     */
    protected $formatter;

    /**
     * 一分钟内的机会次数.
     * @var int
     */
    protected $limit = 18;

    /**
     * @var SplDoublyLinkedList
     */
    private $chance;

    /**
     * @var Channel
     */
    private $chan;

    /**
     * @var float
     */
    private $sendTimeout = 0.01;

    /**
     * 空档秒数.
     * @var int
     */
    private $neutral = 5;

    /**
     * AbstractMinuteRobot constructor.
     */
    public function __construct(FormatterInterface $formatter)
    {
        $this->container = ApplicationContext::getContainer();
        $this->formatter = $formatter;
        $this->clientFactory = $this->container->get(ClientFactory::class);
        $this->chance = new SplDoublyLinkedList();
        $this->tickChance();
        $this->consume();
    }

    final public function send(Record $record)
    {
        $this->chan->push($record, $this->sendTimeout);
    }

    protected function logThrowable(Throwable $throwable): void
    {
        if ($this->container->has(StdoutLoggerInterface::class) && $this->container->has(\Hyperf\ExceptionHandler\Formatter\FormatterInterface::class)) {
            $logger = $this->container->get(StdoutLoggerInterface::class);
            $formatter = $this->container->get(\Hyperf\ExceptionHandler\Formatter\FormatterInterface::class);
            $logger->error($formatter->format($throwable));
        }
    }

    abstract protected function transmit(Record $record);

    /**
     * 消费日志.
     */
    private function consume()
    {
        $this->chan = new Channel($this->limit * 2);
        Coroutine::create(function () {
            while (Manager::isRunning()) {
                try {
                    /**
                     * 弹出一条日志.
                     * @var Record; $record
                     */
                    $record = $this->chan->pop();
                    if (! $record instanceof Record) {
                        continue;
                    }
                    loop:
                    //检查是否有机会
                    if ($this->chance->count()) {
                        //耗费掉一次机会
                        $this->chance->pop();
                        $retry = 0;
                        retryLoop:
                        try {
                            //发送日志
                            $this->transmit($record);
                        } catch (Throwable $throwable) {
                            //发生连接异常，休眠一定时间再次尝试
                            if ($throwable instanceof ConnectException) {
                                if ($retry < 1) {
                                    ++$retry;
                                    Coroutine::sleep(1.5);
                                    goto retryLoop;
                                }
                                $this->logThrowable($throwable);
                            } elseif ($throwable instanceof WaitException) {
                                if ($retry < 1) {
                                    ++$retry;
                                    Coroutine::sleep($throwable->getSecond() + 1);
                                    goto retryLoop;
                                }
                                $this->logThrowable($throwable);
                            } else {
                                $this->logThrowable($throwable);
                            }
                        }
                    } else {
                        //没有机会，休眠到下一分钟填充机会之后
                        $current = date('s', time());
                        $sleep = 60 - $current + $this->neutral + 1;
                        if ($sleep <= 0) {
                            $sleep = $this->neutral;
                        }
                        Coroutine::sleep($sleep);
                        //再次发送日志
                        goto loop;
                    }
                    Coroutine::sleep(0.2);
                } catch (Throwable $throwable) {
                    $this->logThrowable($throwable);
                    Coroutine::sleep(1);
                    continue;
                }
            }
        });
    }

    /**
     * 填充机会.
     */
    private function fillChance()
    {
        for ($i = 0; $i < $this->limit; ++$i) {
            $this->chance->push($i);
        }
    }

    /**
     * 清空机会.
     */
    private function clearChance()
    {
        $c = $this->chance->count();
        for ($i = 0; $i < $c; ++$i) {
            $this->chance->pop();
        }
    }

    /**
     * 投递机会.
     */
    private function tickChance()
    {
        //计算距离下一分钟的秒数
        $current = date('s', time());
        $sleep = 60 - $current;
        if ($sleep > 0) {
            //距离下一分钟需要休眠，填充机会
            $this->fillChance();
            //设置休眠时间
            Timer::after($sleep * 1000, function () {
                //当前时间为一分钟的开始时间，清空机会
                $this->clearChance();
                //设置每一分钟的开始时间都清空机会的定时任务
                Timer::tick(1000 * 60, function () {
                    $this->clearChance();
                });
                //休眠一定秒数，再设置一波填充机会的定时任务
                Timer::after($this->neutral * 1000, function () {
                    //填充机会
                    $this->fillChance();
                    //设置填充机会的定时任务
                    Timer::tick(1000 * 60, function () {
                        $this->fillChance();
                    });
                });
            });
        } else {
            //当前为一分钟的开始时间，清空机会
            $this->clearChance();
            //设置每一分钟的开始时间就清空机会的定时任务
            Timer::tick(1000 * 60, function () {
                $this->clearChance();
            });
            //休眠一定秒数，再设置一波填充机会的定时任务
            Timer::after($this->neutral * 1000, function () {
                //填充机会
                $this->fillChance();
                //设置填充机会的定时任务
                Timer::tick(1000 * 60, function () {
                    $this->fillChance();
                });
            });
        }
    }
}
