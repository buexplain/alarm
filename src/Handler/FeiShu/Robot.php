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
    protected string $url = '';

    /**
     * 机器人安全码
     * @var string
     */
    protected string $secret = '';

    protected array $limit_error_code = [
        5000 => '内部错误，减少调用频率，稍后再试',
        55001 => '服务内部错误，减少调用频率，稍后再试',
        90217 => '请求太频繁，请降低请求调用频率',
        190005 => '应用被限流，稍后再试，适当减小请求频率',
        1000004 => '接口请求过快，超出频率限制，降低请求频率',
        1000005 => '应用被限流，降低请求频率',
    ];

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
    protected function send(Record $record): void
    {
        $url = $this->url;
        if (!empty($this->secret)) {
            $timestamp = $this->getTimestamp();
            $signature = $this->computeSignature($this->getCanonicalStringForIsv($timestamp, $this->secret));
            $query = http_build_query([
                'timestamp' => $timestamp,
                'sign' => $signature,
            ]);
            $url .= (!str_contains($url, '?') ? "?" : "&") . $query;
        }
        $body = $this->formatter->format($record);
        if (empty($body)) {
            return;
        }
        $response = $this->clientFactory->create()->post($url, [
            'headers' => [
                'Content-Type' => 'application/json;charset=utf-8',
            ],
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
        ]);
        $contents = $response->getBody()->getContents();
        if ($response->getStatusCode() == 200) {
            $result = json_decode($contents, true);
            if (isset($result['code'])) {
                //客户端发送太快
                if (isset($this->limit_error_code[$result['code']])) {
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

    protected function computeSignature($canonicalString): string
    {
        $s = hash_hmac('sha256', '', $canonicalString, true);
        return base64_encode($s);
    }

    protected function getTimestamp(): int
    {
        return time();
    }
}
