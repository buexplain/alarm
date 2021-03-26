<?php

declare(strict_types=1);

namespace Alarm\Handler\WeChat;

use Alarm\Contract\FormatterInterface;
use Alarm\Contract\Record;
use Alarm\Exception\WaitException;
use Alarm\Handler\WebHook\AbstractMinuteRobot;
use Exception;

/**
 * Class Robot.
 */
class Robot extends AbstractMinuteRobot
{
    /**
     * Robot constructor.
     */
    public function __construct(FormatterInterface $formatter, string $url)
    {
        $this->url = $url;
        parent::__construct($formatter);
    }

    /**
     * @throws Exception
     */
    protected function transmit(Record $record)
    {
        $response = $this->clientFactory->create()->post($this->url, [
            'headers' => [
                'Content-Type' => 'application/json;charset=utf-8',
            ],
            'body' => json_encode($this->formatter->format($record), JSON_UNESCAPED_UNICODE),
        ]);
        $contents = $response->getBody()->getContents();
        if ($response->getStatusCode() == 200) {
            $result = json_decode($contents, true);
            if (is_array($result)) {
                if (isset($result['errcode']) && $result['errcode'] === 0) {
                    return;
                }
                if (isset($result['errcode']) && is_int($result['errcode']) && ($result['errcode'] == 45009 || $result['errcode'] == -1)) {
                    /*
                     * @link https://open.work.weixin.qq.com/api/doc/90000/90139/90313#%E9%94%99%E8%AF%AF%E7%A0%81%EF%BC%9A45009
                     */
                    throw new WaitException(60);
                }
            }
        }
        throw new Exception($contents);
    }
}
