<?php

declare(strict_types=1);

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL);

\Swoole\Runtime::enableCoroutine(true);

//测试整个发送逻辑，每秒发送20个，持续五分钟
$process = new \Swoole\Process(function () {
    for($i=0; $i<20; $i++) {
        Swoole\Timer::tick(1000, function () {
            (new \Swoole\Coroutine\Http\Client('127.0.0.1', 9501))->get('/alarm');
        });
    }
    $wg = new \Swoole\Coroutine\WaitGroup();
    $wg->add();
    \Swoole\Coroutine::create(function ()  use($wg) {
        \Swoole\Coroutine::defer(function () use ($wg) {
            $wg->done();
        });
        \Swoole\Timer::after(1000*60*5, function() {
            Swoole\Timer::clearAll();
        });
    });
    $wg->wait();
});
$process->start();
\Swoole\Process::wait(true);
