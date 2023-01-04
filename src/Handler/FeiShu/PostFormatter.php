<?php

declare(strict_types=1);

namespace Alarm\Handler\FeiShu;

use Alarm\Contract\Record;
use Alarm\Handler\AbstractTextFormatter;

/**
 * 富文本
 * @see https://open.feishu.cn/document/uAjLw4CM/ukTMukTMukTM/im-v1/message/create_json
 * @package Alarm\Handler\FeiShu
 */
class PostFormatter extends AbstractTextFormatter
{
    /**
     * @param Record $record
     * @return array
     */
    public function format(Record $record): array
    {
        $result = [
            'msg_type' => 'post',
            'content' => [],
        ];
        $message_arr = $record->message ? json_decode($record->message, true) : [];
        $is_return = false;
        if (isset($message_arr['zh_cn']['title']) && ($message_arr['zh_cn']['content'] ?? [])) {
            $result['content']['post']['zh_cn']['title'] = $message_arr['zh_cn']['title'];
            $result['content']['post']['zh_cn']['content'] = $message_arr['zh_cn']['content'];
            $is_return = true;
        }
        if (isset($message_arr['en_us']['title']) && ($message_arr['en_us']['content'] ?? [])) {
            $result['content']['post']['en_us']['title'] = $message_arr['en_us']['title'];
            $result['content']['post']['en_us']['content'] = $message_arr['en_us']['content'];
            $is_return = true;
        }
        return $is_return ? $result : [];
    }
}
