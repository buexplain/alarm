<?php

declare(strict_types=1);

namespace Alarm\Handler\FeiShu;

use Alarm\Contract\Record;
use Alarm\Handler\AbstractTextFormatter;

/**
 * 卡片
 * @see https://open.feishu.cn/document/ukTMukTMukTM/ucTM5YjL3ETO24yNxkjN#4996824a
 * @package Alarm\Handler\FeiShu
 */
class InteractiveFormatter extends AbstractTextFormatter
{
    /**
     * @param Record $record
     * @return array
     */
    public function format(Record $record): array
    {
        $data = parent::format($record);
        return [
            'msg_type' => 'interactive',
            'card' => $data,
        ];

    }
}
