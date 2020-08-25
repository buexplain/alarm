<?php

declare(strict_types=1);

namespace Alarm\Handler\WebHook;

use Alarm\Alarm;
use Alarm\Contract\FormatterInterface;
use Alarm\Contract\HandlerInterface;
use Alarm\Exception\WaitException;
use Alarm\Record;
use GuzzleHttp\Exception\ConnectException;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

/**
 * Class AbstractIntervalRobot.
 */
abstract class AbstractIntervalRobot implements HandlerInterface
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
     * 发送条件.
     * @var array
     */
    protected $sendCondition = [
        //限制周期内发送次数
        'limit' => 18,
        //当前已经发送的次数
        'number' => 0,
        //发送间隔周期，单位秒
        'interval' => 60,
        //最后一次的发送的时间
        'time' => 0,
    ];

    /**
     * @var Channel
     */
    private $chan;

    /**
     * @var float
     */
    private $sendTimeout = 0.01;

    /**
     * AbstractIntervalRobot constructor.
     */
    public function __construct(FormatterInterface $formatter)
    {
        $this->container = ApplicationContext::getContainer();
        $this->formatter = $formatter;
        $this->clientFactory = $this->container->get(ClientFactory::class);
        $this->consume();
    }

    final public function send(Record $record)
    {
        $this->chan->push($record, $this->sendTimeout);
    }

    protected function logThrowable(Throwable $throwable): void
    {
        if ($this->container->has(\Hyperf\Contract\StdoutLoggerInterface::class) && $this->container->has(\Hyperf\ExceptionHandler\Formatter\FormatterInterface::class)) {
            $logger = $this->container->get(\Hyperf\Contract\StdoutLoggerInterface::class);
            $formatter = $this->container->get(\Hyperf\ExceptionHandler\Formatter\FormatterInterface::class);
            $logger->error($formatter->format($throwable));
        }
    }

    abstract protected function transmit(Record $record);

    /**
     * 检查是否满足发送条件.
     * @return int|mixed
     */
    private function checkSendCondition()
    {
        $t = time();
        $diff = $this->sendCondition['interval'] - ($t - $this->sendCondition['time']);
        //优先检查时间阈值，如果满足，则视为新的周期
        if ($diff <= 0) {
            $this->sendCondition['time'] = $t;
            $this->sendCondition['number'] = 1;
            return 0;
        }
        //检查发送次数是否超过次数阈值
        if ($this->sendCondition['number'] >= $this->sendCondition['limit']) {
            return $diff;
        }
        $this->sendCondition['time'] = $t;
        ++$this->sendCondition['number'];
        return 0;
    }

    /**
     * 消费日志.
     */
    private function consume()
    {
        $this->chan = new Channel($this->sendCondition['limit'] * 2);
        Coroutine::create(function () {
            while (Alarm::$running) {
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
                    //检查是否满足发送条件
                    $sleep = $this->checkSendCondition();
                    if ($sleep == 0) {
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
                        //不满足发送条件，需要休眠一定的秒数，等待条件满足
                        Coroutine::sleep($sleep + 1);
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
}
