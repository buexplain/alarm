<?php

declare(strict_types=1);

namespace Alarm;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Socket;
use Swoole\Process as Base;

class Process extends Base
{
    /**
     * @var Channel socket保护，避免多协成同时写
     */
    protected $ch;

    /**
     * @var float
     */
    protected static $sendTimeout = 0.01;

    public function __construct(callable $callback, bool $redirect_stdin_and_stdout = null, int $pipe_type = null, bool $enable_coroutine = null)
    {
        parent::__construct($callback, $redirect_stdin_and_stdout, $pipe_type, $enable_coroutine);
    }

    public function pushCh(Record $record, $timeout)
    {
        if (is_null($this->ch)) {
            $this->ch = new Channel(100);
            $this->popCh();
        }
        $this->ch->push($record, $timeout);
    }

    protected function popCh()
    {
        \Swoole\Coroutine::create(function () {
            while (true) {
                $record = $this->ch->pop();
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
                } catch (\Throwable $throwable) {
                }
            }
        });
    }
}
