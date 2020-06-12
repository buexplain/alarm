<?php

declare(strict_types=1);

namespace Alarm\Contract;

use Hyperf\Contract\ProcessInterface;
use Alarm\Record;

/**
 * Interface AlarmInterface
 * @package Alarm\Contract
 */
interface AlarmInterface extends ProcessInterface
{
    public static function send(Record $record);
}
