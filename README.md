# Alarm
这个是一个基于[hyperf](https://github.com/hyperf/hyperf "hyperf")的告警组件。

支持发送告警到钉钉群机器人和企业微信群机器人。

大概的告警流程是：
1. 创建自定义告警进程
2. worker进程与task进程通过日志组件发送日志到告警进程，该日志包含告警组件的配置文件的handler信息
3. 告警进程接收到日志后，解析其中的告警组件的Handler信息，然后循环丢给每一个Handler进行处理

## 安装

**下载包** `composer require buexplain/alarm "dev-master"`

**发布告警组件的配置** `php bin/hyperf.php vendor:publish buexplain/alarm`

**修改日志配置文件** `config/autoload/logger.php`
```php
<?php

declare(strict_types=1);

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;

return [
    'default' => [
        'handlers'=>[
            //默认的文件日志处理器
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    'filename' => BASE_PATH . '/runtime/logs/hyperf.log',
                    'level'=>Logger::ERROR,
                ],
                'formatter' => [
                    'class' => LineFormatter::class,
                    'constructor' => [
                        'format' => null,
                        'dateFormat' => null,
                        'allowInlineLineBreaks' => true,
                    ],
                ],
            ],
            //告警日志处理器
            [
                'class' => \Alarm\Handler::class,
                'constructor' => [
                    'alarm'=>function() {
                        return make(\Alarm\Contract\AlarmInterface::class, [
                            \Psr\Container\ContainerInterface::class => \Hyperf\Utils\ApplicationContext::getContainer()
                        ]);
                    },
                    //此处的handler对应的正是config/autoload/alarm.php配置的key值
                    'handlers'=>[
                        'dingTalk',
                        'weChat',
                    ],
                    //接收的日志级别
                    'level'=>\Monolog\Logger::ERROR,
                ],
            ],
        ],
    ],
];
```
> NOTE: 如果配置文件`logger.php`存在，则直接在需要告警的日志渠道上添加`告警日志处理器`，否则请先安装日志组件 

**使用**
```php
$logger = \Hyperf\Utils\ApplicationContext::getContainer()->get(\Hyperf\Logger\LoggerFactory::class);
$logger->error('Your alarm message.');
```

## License
[Apache-2.0](http://www.apache.org/licenses/LICENSE-2.0.html)
