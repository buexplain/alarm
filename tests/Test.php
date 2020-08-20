<?php

declare(strict_types=1);

namespace AlarmTest;

use Alarm\Alarm;
use Alarm\Handler;
use Alarm\Handler\HandlerFactory;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\Formatter\DefaultFormatter;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Psr\Log\LogLevel;
use Swoole\Atomic;
use Swoole\Coroutine\Http\Client;
use Swoole\Http\Server;
use Mockery;
use DateTime;
use Mockery\MockInterface;
use Mockery\LegacyMockInterface;

/**
 * Class Test
 * @package AlarmTest
 */
class Test
{
    const HOST = '127.0.0.1';
    const PORT = 9501;

    /**
     * 告警组件配置 alarm.php
     * @var array
     */
    protected $config = [
        'dingTalk'=>[
            'class' => \Alarm\Handler\DingTalk\DingTalk::class,
            'constructor'=>[
                'formatter' => [
                    'class' => \Alarm\Handler\DingTalk\TextFormatter::class,
                    'constructor' => [],
                ],
                'robots' => [
                    ['url'=>'http://'.self::HOST.':'.self::PORT.'/receive?number=dingTalk-1', 'secret'=>''],
                    ['url'=>'http://'.self::HOST.':'.self::PORT.'/receive?number=dingTalk-2', 'secret'=>''],
                    ['url'=>'https://oapi.dingtalk.com/robot/send?access_token=d606192fa6b95b3f1260594abdf0dc7ad49270803c6245c0987ca771a7dde303', 'secret'=>'SEC3b1f04dbbf93b752c55a88b824317a593f88e964295e81fb20227578b060f7f0'],
                    ['url'=>'https://oapi.dingtalk.com/robot/send?access_token=4133140c448d5c2eb9da7248218b3e6120e0e0fa1ef76e117f00edc6d3b00d7e', 'secret'=>'SECba6382e165ac8bcac742cdbc2630684b8318a1e5ff34281bd0c6459f4b2028dd'],
                ]
            ],
        ],
        'weChat'=>[
            'class' => \Alarm\Handler\Wechat\WeChat::class,
            'constructor'=>[
                'formatter' => [
                    'class' => \Alarm\Handler\Wechat\TextFormatter::class,
                    'constructor' => [],
                ],
                'robots' => [
                    'http://'.self::HOST.':'.self::PORT.'/receive?number=weChat-1',
                    'http://'.self::HOST.':'.self::PORT.'/receive?number=weChat-2',
                    'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=ded47d9e-74ed-44a9-bbd6-b7a0b4b20171',
                    'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=399454ef-ab74-44c7-875d-6d234ac8f149',
                ]
            ],
        ],
    ];

    /**
     * 测试耗时，单位（毫秒）
     * @var float|int
     */
    protected $testTimeMS;

    public function __construct($testTimeMS=1000*60*5)
    {
        $this->testTimeMS = $testTimeMS;
    }

    public function run(): void
    {
        /**
         * @var $config ConfigInterface|MockInterface|LegacyMockInterface
         */
        $config = Mockery::mock(...[ConfigInterface::class]);
        $config->shouldReceive(...['get'])->with(...['alarm', []])->andReturn(...[$this->config]);
        /**
         * @var $container ContainerInterface|MockInterface|LegacyMockInterface
         */
        $container = Mockery::mock(...[ContainerInterface::class]);
        ApplicationContext::setContainer($container);
        $container->shouldReceive(...['get'])->with(...[ConfigInterface::class])->andReturn(...[$config]);
        $container->shouldReceive(...['get'])->with(...[HandlerFactory::class])->andReturn(...[new HandlerFactory($config)]);
        $container->shouldReceive(...['make'])->with(...[[ContainerInterface::class=>$container]])->andReturn(...[new Alarm($container)]);
        $container->shouldReceive(...['get'])->with(...[Alarm::class])->andReturn(...[new Alarm($container)]);
        $container->shouldReceive(...['get'])
            ->with(...[Handler::class])
            ->andReturn(...[new Handler(make(Alarm::class, [ContainerInterface::class=>$container]), array_keys($this->config))]);
        $container->shouldReceive(...['has'])->with(...[StdoutLoggerInterface::class])->andReturnTrue();
        $container->shouldReceive(...['has'])->with(...[FormatterInterface::class])->andReturnTrue();
        $container->shouldReceive(...['get'])->with(...[StdoutLoggerInterface::class])->andReturn(...[new class {
            public function error($s) {
                echo 'error: '.$s.PHP_EOL;
            }
            public function critical($s) {
                echo 'critical: '.$s.PHP_EOL;
            }
        }]);
        $container->shouldReceive(...['get'])->with(...[FormatterInterface::class])->andReturn(...[new DefaultFormatter()]);
        $container->shouldReceive(...['get'])->with(...[ClientFactory::class])->andReturn(...[new ClientFactory($container)]);
        $atomic = new Atomic();
        $http = new Server(self::HOST, self::PORT, SWOOLE_PROCESS);
//        $http->set([
//            'max_request'=>500,
//            'worker_num'=>2,
//        ]);
        $container->get(Alarm::class)->bind($http);
        $handler = $container->get(Handler::class);
        $http->on('request', function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) use ($handler, $atomic) {
            if($request->server['path_info'] == '/alarm') {
                $record = [
                    'message' => '$container->shouldReceive('.date('H:i:s').')->with(StdoutLoggerInterface::class)->andReturnTrue();',
                    'context'=>[],
                    'extra' => [],
                    'datetime' => new DateTime(),
                    'level_name' => LogLevel::DEBUG,
                ];
                $handler->handle($record);
            }elseif($request->server['path_info'] == '/receive') {
                echo "\rreceive --> ".$atomic->add();
                $response->setHeader('content-type', 'application/json; charset=utf-8');
                $response->setStatusCode(200);
                $response->write(json_encode(['errcode'=>0]));
                $response->end();
            }
        });

        $http->on('workerStart', function (Server $server, int $workerId) {
            if($workerId == 0) {
                for($i=0; $i<10; $i++) {
                    $server->tick(100, function () {
                        $cli = new Client(self::HOST, self::PORT);
                        $cli->get('/alarm');
                    });
                }
            }
        });

        $http->after($this->testTimeMS, function () use($http) {
            $http->shutdown();
        });

        $http->start();
    }
}

