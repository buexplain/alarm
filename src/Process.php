<?php

declare(strict_types=1);

namespace Alarm;

use Alarm\Contract\InterfaceProcess;
use Alarm\Contract\Manager;
use Alarm\Contract\Record;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Socket;
use Swoole\Process as Base;
use Throwable;

class Process extends Base implements InterfaceProcess
{
    /**
     * @var Channel|null socket保护，避免多协程同时写，导致崩溃
     */
    protected ?Channel $protectorCh = null;

    /**
     * @var float
     */
    protected static float $sendTimeout = 0.01;

    public function __construct(callable $callback, bool $redirect_stdin_and_stdout = null, int $pipe_type = null, bool $enable_coroutine = null)
    {
        parent::__construct($callback, $redirect_stdin_and_stdout, $pipe_type, $enable_coroutine);
    }

    public function send(Record $record, float $timeout = 0.01): void
    {
        if (is_null($this->protectorCh)) {
            $this->init();
        }
        $this->protectorCh->push($record, $timeout);
    }

    protected function init(): void
    {
        $this->protectorCh = new Channel(20);
        Coroutine::create(function () {
            defer(function () {
                $this->protectorCh->close();
                $this->protectorCh = null;
            });
            while (Manager::isRunning()) {
                $record = $this->protectorCh->pop();
                try {
                    $data = serialize($record);
                    if ($data != '') {
                        /**
                         * @var Socket $sock
                         */
                        $sock = $this->exportSocket();
                        $sendLen = $sock->send($data, self::$sendTimeout);
                        // SOCKET_ETIMEDOUT 110
                        if ($sendLen === false && $sock->errCode !== 110) {
                            $sock->close();
                            Coroutine::sleep(3);
                        }
                    }
                } catch (Throwable) {
                }
            }
        });
    }
}
