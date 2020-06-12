<?php

declare(strict_types=1);

namespace Alarm\Handler\DingTalk;

use Alarm\Handler\WebHook\AbstractTextFormatter;
use Alarm\Record;

/**
 * Class TextFormatter
 * @package Alarm\Handler\DingTalk
 */
class TextFormatter extends AbstractTextFormatter
{
    /**
     * @param Record $record
     * @return array
     */
    public function format(Record $record)
    {
        $data = parent::format($record);
        $result = [
            'msgtype'=>'text',
            'text'=>[
                'content'=>$data['content'],
            ],
            'at'=>['atMobiles'=>$data['atMobiles'], 'isAtAll'=>$data['isAtAll']],
        ];
        return $result;
    }
}
