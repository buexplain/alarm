# 飞书告警

### logger.php的配置

```php
# logger.php
return [    
    // 这里配置一个飞书文字告警
    'alarm' => [
        'handlers' => [
            //告警日志处理器
            [
                'class' => \Alarm\Handler::class,
                'constructor' => [
                    //此处的handler对应的正是config/autoload/alarm.php配置的key值
                    'handlers'=>[
                        'feiShu', // 这里对应alarm.php配置的key
                    ],
                    //接收的日志级别
                    'level'=>\Monolog\Logger::ERROR,
                ],
            ],
        ],
        'formatter' => [
            'class'       => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format'                => null,
                'dateFormat'            => 'Y-m-d H:i:s.u',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    // 这里配置一个飞书富文本告警
    'alarm_post' => [
        'handlers' => [
            //告警日志处理器
            [
                'class'       => \Alarm\Handler::class,
                'constructor' => [
                    //此处的handler对应的正是config/autoload/alarm.php配置的key值
                    'handlers' => [
                        'feiShuPost', // 这里对应alarm.php配置的key
                    ],
                    //接收的日志级别
                    'level' => \Monolog\Logger::ERROR,
                ],
            ],
        ],
        'formatter' => [
            'class'       => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format'                => null,
                'dateFormat'            => 'Y-m-d H:i:s.u',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
];

```

## alarm.php
```php
return [
    //飞书群机器人配置 , 普通文字
    'feiShu' => [
        'class'       => \Alarm\Handler\FeiShu\FeiShu::class,
        'constructor' => [
            'formatter' => [
                'class'       => \Alarm\Handler\FeiShu\TextFormatter::class,
                'constructor' => [],
            ],
            'robots' => [
                ['url' => 'https://open.feishu.cn/open-apis/bot/v2/hook/xxx-xxxxxx', 'secret' => ''],
            ],
        ],
    ],
    // 富文本
    'feiShuPost' => [
        'class'       => \Alarm\Handler\FeiShu\FeiShu::class,
        'constructor' => [
            'formatter' => [
                'class'       => \Alarm\Handler\FeiShu\PostFormatter::class,
                'constructor' => [],
            ],
            'robots' => [
                ['url' => 'https://open.feishu.cn/open-apis/bot/v2/hook/xxxxxx', 'secret' => ''],
            ],
        ],
    ],
];
```


### 调用例子

```php
$logger = \Hyperf\Utils\ApplicationContext::getContainer()->get(\Hyperf\Logger\LoggerFactory::class);
$logger->get($name = '管理后台测试', $group = 'alarm')->error('22333');  // 普通文本告警

// 看飞书文档  https://open.feishu.cn/document/uAjLw4CM/ukTMukTMukTM/im-v1/message/create_json?lang=zh-CN#45e0953e , 格式根据文档对应一下即可
$title = '标题';
$text = '内容';
$content = [];
$content['zh_cn']['title'] = $title; // 标题
$content['zh_cn']['content'][] = [
    ['tag' => 'text', 'text' => $text],  // 第一个内容
    ['tag' => 'a', 'text' => '第一个链接', 'href' => 'https://open.feishu.cn/'], // 第二个内容 , 这是一个链接
];

$logger->get($name = '管理后台测试1', $group = 'alarm_post')->error(json_encode($content)); // 富文本告警 (建议只用标题 + 内容 + 链接的方式) , 图片类需要先接入飞书的上传接口上传完图片后才能发图片类的日志 , 不适合告警业务.
```
