<?php

declare(strict_types=1);

namespace Alarm\Handler\WeChat;

use Alarm\Contract\Record;
use Alarm\Handler\AbstractTextFormatter;

/**
 * Class TextFormatter
 * @package Alarm\Handler\WeChat
 */
class TextCardFormatter extends AbstractTextFormatter
{
    public function format(Record $record): array
    {
        $data = parent::format($record);
        return [
            'msgtype' => 'textcard',
            'textcard' => [
                'title' => $data['title'],
                'description' => $data['description'],
                'url' => $data['url'],
                'btntxt' => $data['btntxt'] ?? '详情',
            ],
        ];
    }
}
