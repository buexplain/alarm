<?php

declare(strict_types=1);

namespace Alarm\Handler\WeChat;

use Alarm\Contract\Record;
use Alarm\Handler\AbstractTextFormatter;

/**
 * Class TextFormatter
 * @package Alarm\Handler\WeChat
 */
class MarkdownFormatter extends AbstractTextFormatter
{
    /**
     * @link https://developer.work.weixin.qq.com/document/path/90236#%E6%94%AF%E6%8C%81%E7%9A%84markdown%E8%AF%AD%E6%B3%95
     * @param Record $record
     * @return array
     */
    public function format(Record $record): array
    {
        $data = parent::format($record);
        return [
            'msgtype' => 'markdown',
            'markdown' => [
                'content' => $data['content'],
            ],
        ];
    }
}
