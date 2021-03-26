<?php

declare(strict_types=1);

namespace Alarm\Handler\WeChat;

use Alarm\Contract\Record;
use Alarm\Handler\WebHook\AbstractTextFormatter;

/**
 * Class TextFormatter.
 */
class TextFormatter extends AbstractTextFormatter
{
    public function format(Record $record)
    {
        $data = parent::format($record);
        return [
            'msgtype' => 'text',
            'text' => [
                'content' => $data['content'],
                'mentioned_mobile_list' => $data['isAtAll'] ? ['@all'] : $data['atMobiles'],
            ],
        ];
    }
}
