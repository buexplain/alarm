<?php

declare(strict_types=1);

namespace Alarm\Handler\FeiShu;

use Alarm\Contract\Record;
use Alarm\Handler\AbstractTextFormatter;

/**
 * 富文本
 * @see https://open.feishu.cn/document/uAjLw4CM/ukTMukTMukTM/reference/im-v1/message/create
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
        $data = parent::format($record);
        return [
            'msg_type' => 'post',
            'content' => [
                'text' => $data['content'],
            ],
        ];
//        https://open.feishu.cn/document/uAjLw4CM/ukTMukTMukTM/reference/im-v1/message/create
//       curl --location --request POST 'https://open.feishu.cn/open-apis/im/v1/messages?receive_id_type=chat_id' \
//--header 'Authorization: Bearer t-XXX' \
//--header 'Content-Type: application/json; charset=utf-8' \
//--data-raw '{
//    "receive_id": "oc_84983ff6516d731e5b5f68d4ea2e1da5",
//    "content": "{\"text\":\"<at user_id=\\\"ou_155184d1e73cbfb8973e5a9e698e74f2\\\">Tom</at>  d \"}",
//    "msg_type": "text"
//}'
    }
}
