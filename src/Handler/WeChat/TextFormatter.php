<?php

declare(strict_types=1);

namespace Alarm\Handler\WeChat;

use Alarm\Handler\WebHook\AbstractTextFormatter;
use Alarm\Record;

/**
 * Class TextFormatter
 * @package Alarm\Handler\WeChat
 */
class TextFormatter extends AbstractTextFormatter
{
    public function format(Record $record)
    {
        $data = parent::format($record);
        $result = [
            'msgtype'=>'text',
            'text'=>[
                'content'=>$data['content'],
                'mentioned_mobile_list'=>$data['isAtAll'] ? ['@all'] : $data['atMobiles'],
            ],
        ];
        return $result;
    }
}
