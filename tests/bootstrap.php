<?php

declare(strict_types=1);

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL);

require __DIR__ . '/../vendor/autoload.php';

\Swoole\Runtime::enableCoroutine(true);

//测试整个发送逻辑
$process = new \Swoole\Process(function () {
    //每轮测试4分钟
    (new \AlarmTest\Test(1000*60*4))->run();
});
$process->start();
$status = \Swoole\Process::wait(true);
return;

// 测试一分钟的任意2秒内启动程序，每次启动执行一定分钟数，看看是否会被限制请求
$step = -1;
loop:
$step += 2;
$current = date('s', time());
$s = 60 - $current + $step;
if($s > 0) {
    sleep($s);
}
echo date('Y-m-d H:i:s').PHP_EOL;
$process = new \Swoole\Process(function () {
    //每轮测试4分钟
    (new \AlarmTest\Test(1000*60*4))->run();
});
$process->start();
$status = \Swoole\Process::wait(true);
if($step > 60) {
    exit(0);
}
sleep(60);
goto loop;