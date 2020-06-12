<?php

declare(strict_types=1);

namespace Alarm\Handler\WeChat;

use Alarm\Exception\InvalidConfigException;
use Alarm\Handler\WebHook\AbstractHandler;

/**
 * Class WeChat
 * @package Alarm\Handler\WeChat
 */
class WeChat extends AbstractHandler
{
    public function __construct(array $formatter, array $robots)
    {
        if (empty($robots)) {
            throw new InvalidConfigException(sprintf('Parameter $robots of %s is invalid.', __CLASS__.'::'.__FUNCTION__));
        }
        parent::__construct();
        foreach ($robots as $key=>$url) {
            if (empty($url)) {
                throw new InvalidConfigException(sprintf('Parameter $robots[%d] of %s is invalid.', __CLASS__.'::'.__FUNCTION__, $key));
            }
            $parameter = [
                'formatter'=>make($formatter['class'], $formatter['constructor']),
                'url'=>$url,
            ];
            $this->enqueue(make(Robot::class, $parameter));
        }
    }
}
