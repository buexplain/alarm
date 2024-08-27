<?php

declare(strict_types=1);

namespace Alarm\Handler\WeChat;

use Alarm\Contract\Record;
use Alarm\Handler\AbstractTextFormatter;

/**
 * Class TextFormatter
 * @package Alarm\Handler\WeChat
 */
class NewsFormatter extends AbstractTextFormatter
{
    public function format(Record $record): array
    {
        $data = parent::format($record);
        return [
            'msgtype' => 'news',
            'news' => [
                'articles' => [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'url' => $data['url'],
                    'picurl' => $data['picurl'],
                ]
            ],
        ];
    }
}
