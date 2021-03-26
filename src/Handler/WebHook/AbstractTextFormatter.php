<?php

declare(strict_types=1);

namespace Alarm\Handler\WebHook;

use Alarm\Contract\FormatterInterface;
use Alarm\Contract\Record;

/**
 * Class AbstractTextFormatter.
 */
abstract class AbstractTextFormatter implements FormatterInterface
{
    /**
     * @return array
     */
    public function format(Record $record)
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

    private static function toString($value)
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_scalar($value)) {
            return sprintf('%s', $value);
        }
        return var_export($value, true);
    }
}
