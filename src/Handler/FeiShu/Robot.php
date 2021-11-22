<?php

declare(strict_types=1);

namespace Alarm\Handler\FeiShu;

use Alarm\Contract\FormatterInterface;
use Alarm\Contract\Record;
use Alarm\Exception\WaitException;
use Alarm\Handler\AbstractRobot;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class Robot
 * @package Alarm\Handler\FeiShu
 */
class Robot extends AbstractRobot
{
    /**
     * 机器人地址
     * @var string
     */
    protected $url = '';

    /**
     * 机器人安全码
     * @var string
     */
    protected $secret = '';

    /**
     * Robot constructor.
     * @param FormatterInterface $formatter
     * @param string $url
     * @param string $secret
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(FormatterInterface $formatter, string $url, string $secret = '')
    {
        $this->url = $url;
        $this->secret = $secret;
        parent::__construct($formatter);
    }

    /**
     * @throws Exception|GuzzleException
     */
    protected function send(Record $record)
    {
        $url = $this->url;
        if (!empty($this->secret)) {
            $timestamp = $this->getMillisecond();
            $signature = $this->computeSignature($this->secret, $this->getCanonicalStringForIsv($timestamp, $this->secret));
            $query = http_build_query([
                'timestamp' => $timestamp,
                'sign' => $signature,
            ]);
            $url .= "&$query";
        }
        $response = $this->clientFactory->create()->post($url, [
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
                //客户端发送太快
                if (isset($result['errcode']) && in_array($result['errcode'] , [190005, 90217,1000004,1000005,5000,55001])) {
                    throw new WaitException(30);
                }
            }
        }
    }

    protected function getCanonicalStringForIsv($timestamp, $suiteTicket): string
    {
        $result = $timestamp;
        if ($suiteTicket != null) {
            $result .= "\n" . $suiteTicket;
        }
        return $result;
    }

    protected function computeSignature($accessSecret, $canonicalString): string
    {
        $s = hash_hmac('sha256', $canonicalString, $accessSecret, true);
        return base64_encode($s);
    }

    protected function getMillisecond(): int
    {
        return (int)(microtime(true) * 1000);
    }
}
