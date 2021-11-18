<?php

declare(strict_types=1);

namespace Alarm\Handler;

use Alarm\Contract\FormatterInterface;
use Alarm\Contract\Record;

/**
 * Class AbstractTextFormatter
 * @package Alarm\Handler
 */
abstract class AbstractTextFormatter implements FormatterInterface
{
    /**
     * @param Record $record
     * @return array
     */
    public function format(Record $record): array
    {
        $result = [
            'atMobiles' => [],
            'isAtAll' => false,
            'content' => '',
        ];

        $context = $record->context;

        //提取@信息
        if (isset($context['@'])) {
            $list = (array) $context['@'];
            unset($context['@']);
            foreach ($list as $v) {
                if (is_numeric($v)) {
                    //手机号
                    $result['atMobiles'][] = $v;
                } elseif (strtolower($v) == 'all') {
                    //全部人
                    $result['isAtAll'] = true;
                    $result['atMobiles'] = [];
                    break;
                }
            }
        }

        //格式化内容成文本
        $content = sprintf('[%s] %s: %s', $record->datetime->format('Y-m-d H:i:s'), $record->level, $record->message);
        if (count($context)) {
            foreach ($context as $key => $value) {
                $content .= sprintf("\n%s: %s", $key, self::toString($value));
            }
        }
        if (count($record->extra)) {
            foreach ($record->extra as $key => $value) {
                $content .= sprintf("\n%s: %s", $key, self::toString($value));
            }
        }
        $result['content'] = $content;

        return $result;
    }

    protected static function toString($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_scalar($value)) {
            return sprintf('%s', $value);
        }
        return (string) var_export($value, true);
    }
}
