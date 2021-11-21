<?php

declare(strict_types=1);

namespace Alarm\Handler\FeiShu;

use Alarm\Exception\InvalidConfigException;
use Alarm\Handler\AbstractHandler;

/**
 * Class FeiShu
 * @package Alarm\Handler\FeiShu
 */
class FeiShu extends AbstractHandler
{
    /**
     * FeiShu constructor.
     */
    public function __construct(array $formatter, array $robots)
    {
        if (!isset($formatter['class'])) {
            throw new InvalidConfigException('Parameter $formatter[\'class\'] is not defined.');
        }
        if (!isset($formatter['constructor'])) {
            $formatter['constructor'] = [];
        }
        parent::__construct();
        foreach ($robots as $key => $robot) {
            if (!isset($robot['url']) || empty($robot['url'])) {
                throw new InvalidConfigException(sprintf('Parameter $robots[%d][\'url\'] is invalid.', $key));
            }
            if (!isset($robot['secret'])) {
                throw new InvalidConfigException(sprintf('Parameter $robots[%d][\'secret\'] is invalid.', $key));
            }
            $parameter = [
                'formatter' => make($formatter['class'], $formatter['constructor']),
                'url' => $robot['url'],
                'secret' => $robot['secret'],
            ];
            $this->enqueue(make(Robot::class, $parameter));
        }
    }
}
