<?php

declare(strict_types=1);

namespace Alarm\Handler\DingTalk;

use Alarm\Handler\WebHook\AbstractTextFormatter;
use Alarm\Record;

/**
 * Class TextFormatter.
 */
class TextFormatter extends AbstractTextFormatter
{
    /**
     * @return array
     */
    public function format(Record $record)
    {
        $data = parent::format($record);
        return [
            'msgtype' => 'text',
            'text' => [
                'content' => $data['content'],
            ],
            'at' => ['atMobiles' => $data['atMobiles'], 'isAtAll' => $data['isAtAll']],
        ];
    }
}
