<?php

declare(strict_types=1);

namespace Alarm\Handler\FeiShu;

use Alarm\Contract\Record;
use Alarm\Handler\AbstractTextFormatter;

/**
 * Class TextFormatter
 * @package Alarm\Handler\FeiShu
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
            'msg_type' => 'text',
            'content' => [
                'text' => $data['content'],
            ],
        ];
    }
}
