<?php

declare(strict_types=1);

namespace Alarm\Handler\WeChat;

use Alarm\Contract\FormatterInterface;
use Alarm\Handler\WebHook\AbstractMinuteRobot;
use Alarm\Record;
use GuzzleHttp\Exception\ConnectException;

/**
 * Class Robot
 * @package Alarm\Handler\WeChat
 */
class Robot extends AbstractMinuteRobot
{
    /**
     * Robot constructor.
     * @param FormatterInterface $formatter
     * @param string $url
     */
    public function __construct(FormatterInterface $formatter, string $url)
    {
        $this->url = $url;
        parent::__construct($formatter);
    }

    /**
     * @param Record $record
     */
    protected function transmit(Record $record)
    {
        $retry = 0;
        retryLoop:
        try {
            $response = $this->clientFactory->create()->post($this->url, [
                'headers' => [
                    'Content-Type' => 'application/json;charset=utf-8'
                ],
                'body' => json_encode($this->formatter->format($record), JSON_UNESCAPED_UNICODE),
            ]);
        } catch (ConnectException $exception) {
            if ($retry < 1) {
                $retry++;
                goto retryLoop;
            }
        }

        if ($response->getStatusCode() == 200) {
            $contents = $response->getBody()->getContents();
            $result = json_decode($contents, true);
            if (is_array($result)) {
                if (!isset($result['errcode']) || $result['errcode'] != 0) {
                    echo substr($this->url, -1).'-限流-'.$result['errcode'].'--'.$result['errmsg'].PHP_EOL;
                }
            } else {
                echo substr($this->url, -1).'--限流'.date('Y-m-d H:i:s').PHP_EOL;
            }
        }
    }
}
