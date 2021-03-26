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
     * @var Channel socket保护，避免多协成同时写
     */
    protected $protectorCh;

    /**
     * @var float
     */
    protected static $sendTimeout = 0.01;

    public function __construct(callable $callback, bool $redirect_stdin_and_stdout = null, int $pipe_type = null, bool $enable_coroutine = null)
    {
        parent::__construct($callback, $redirect_stdin_and_stdout, $pipe_type, $enable_coroutine);
    }

    public function send(Record $record, float $timeout = 0.01)
    {
        if (is_null($this->protectorCh)) {
            $this->init();
        }
        $this->protectorCh->push($record, $timeout);
    }

    protected function init()
    {
        $this->protectorCh = new Channel(10);
        Coroutine::create(function () {
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
                        if ($sendLen === false && $sock->errCode !== SOCKET_ETIMEDOUT) {
                            $sock->close();
                            Coroutine::sleep(3);
                        }
                    }
                } catch (Throwable $throwable) {
                }
            }
        });
    }
}
