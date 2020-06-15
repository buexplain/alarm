<?php

declare(strict_types=1);

namespace Alarm\Handler\WeChat;

use Alarm\Contract\FormatterInterface;
use Alarm\Handler\WebHook\AbstractMinuteRobot;
use Alarm\Record;

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
        $this->clientFactory->create()->post($this->url, [
            'headers' => [
                'Content-Type' => 'application/json;charset=utf-8'
            ],
            'body' => json_encode($this->formatter->format($record), JSON_UNESCAPED_UNICODE),
        ]);
    }
}
