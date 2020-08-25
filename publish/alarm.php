<?php

declare(strict_types=1);

return [
    //钉钉机器人配置
    'dingTalk' => [
        'class' => \Alarm\Handler\DingTalk\DingTalk::class,
        'constructor' => [
            'formatter' => [
                'class' => \Alarm\Handler\DingTalk\TextFormatter::class,
                'constructor' => [],
            ],
            'robots' => [
                ['url' => '钉钉机器人地址', 'secret' => '钉钉机器人密钥'],
            ],
        ],
    ],
    //企业微信群机器人配置
    'weChat' => [
        'class' => \Alarm\Handler\Wechat\WeChat::class,
        'constructor' => [
            'formatter' => [
                'class' => \Alarm\Handler\Wechat\TextFormatter::class,
                'constructor' => [],
            ],
            'robots' => [
                '企业微信群机器人地址',
            ],
        ],
    ],
];
