<?php

declare(strict_types=1);

namespace Alarm\Handler\DingTalk;

use Alarm\Contract\Record;
use Alarm\Handler\AbstractTextFormatter;

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
    public function format(Record $record): array
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
