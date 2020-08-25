<?php

declare(strict_types=1);

namespace Alarm\Handler\DingTalk;

use Alarm\Contract\FormatterInterface;
use Alarm\Exception\WaitException;
use Alarm\Handler\WebHook\AbstractIntervalRobot;
use Alarm\Record;
use Exception;

/**
 * Class Robot.
 */
class Robot extends AbstractIntervalRobot
{
    /**
     * 机器人安全码
     * @var string
     */
    protected $secret = '';

    /**
     * Robot constructor.
     * @param string $secret
     */
    public function __construct(FormatterInterface $formatter, string $url, $secret = '')
    {
        $this->url = $url;
        $this->secret = $secret;
        parent::__construct($formatter);
    }

    /**
     * @throws Exception
     */
    protected function transmit(Record $record)
    {
        $url = $this->url;
        if (! empty($this->secret)) {
            $timestamp = $this->getMillisecond();
            $signature = $this->computeSignature($this->secret, $this->getCanonicalStringForIsv($timestamp, $this->secret));
            $query = http_build_query([
                'timestamp' => $timestamp,
                'sign' => $signature,
            ]);
            $url .= "&{$query}";
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
                if (isset($result['status']) && is_int($result['status']) && $result['status'] == 1111 && isset($result['wait']) && is_int($result['wait']) && $result['wait'] < 60) {
                    throw new WaitException($result['wait']);
                }
            }
        }
        throw new Exception($contents);
    }

    protected function getCanonicalStringForIsv($timestamp, $suiteTicket)
    {
        $result = $timestamp;
        if ($suiteTicket != null) {
            $result .= "\n" . $suiteTicket;
        }
        return $result;
    }

    protected function computeSignature($accessSecret, $canonicalString)
    {
        $s = hash_hmac('sha256', $canonicalString, $accessSecret, true);
        return base64_encode($s);
    }

    protected function getMillisecond()
    {
        [$s1, $s2] = explode(' ', microtime());
        return (float) sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }
}
