<?php

declare(strict_types=1);

namespace Alarm\Handler\WeChat;

use Alarm\Contract\FormatterInterface;
use Alarm\Handler\WebHook\AbstractMinuteRobot;
use Alarm\Record;
use Exception;

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
     * @throws Exception
     */
    protected function transmit(Record $record)
    {
        $response = $this->clientFactory->create()->post($this->url, [
            'headers' => [
                'Content-Type' => 'application/json;charset=utf-8'
            ],
            'body' => json_encode($this->formatter->format($record), JSON_UNESCAPED_UNICODE),
        ]);
        $contents = $response->getBody()->getContents();
        if ($response->getStatusCode() == 200) {
            $result = json_decode($contents, true);
            if (is_array($result) && isset($result['errcode']) && $result['errcode'] === 0) {
                return;
            }
        }
        throw new Exception($contents);
    }
}
