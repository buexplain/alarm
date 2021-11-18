<?php

declare(strict_types=1);

namespace Alarm\Handler;

use Alarm\Contract\FormatterInterface;
use Alarm\Contract\Manager;
use Alarm\Contract\Record;
use Alarm\Contract\RobotInterface;
use Alarm\Exception\WaitException;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

/**
 * Class AbstractRobot
 * @package Alarm\Handler\DingTalk
 */
abstract class AbstractRobot implements RobotInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * @var FormatterInterface
     */
    protected $formatter;

    /**
     * @var Channel
     */
    protected $ch;

    /**
     * 发送间隔
     * @var float
     */
    protected $step = 3.0;

    /**
     * Robot constructor.
     * @param FormatterInterface $formatter
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(FormatterInterface $formatter)
    {
        $this->container = ApplicationContext::getContainer();
        $this->formatter = $formatter;
        $this->clientFactory = $this->container->get(ClientFactory::class);
        $this->ch = new Channel(60);
        $this->pop();
    }

    abstract protected function send(Record $record);

    public function push(Record $record): bool
    {
        return (bool) $this->ch->push($record, 0.01);
    }

    protected function pop()
    {
        Coroutine::create(function () {
            $lastSendTime = 0;
            while (Manager::isRunning()) {
                try {
                    $record = $this->ch->pop();
                    if(!$record instanceof Record) {
                        continue;
                    }
                    //每隔n秒发送一条数据，避免触发限制
                    $diff = (float)(time() - $lastSendTime);
                    if ($diff >= 0 && $diff < $this->step) {
                        $diff = $this->step - $diff;
                        //echo '休眠--'.($diff < 1 ? 1 : $diff).PHP_EOL;
                        Coroutine::sleep($diff < 1 ? 1 : $diff);
                    }
                    try {
                        retry:
                        $this->send($record);
                    }catch (WaitException $exception) {
                        Coroutine::sleep($exception->getSecond());
                        goto retry;
                    }catch (Throwable $throwable) {
                        //其它错误，不予考虑
                    }
                    $lastSendTime = time();
                } catch (Throwable $throwable) {
                    Coroutine::sleep(1);
                    continue;
                }
            }
        });
    }
}
