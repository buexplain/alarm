<?php

declare(strict_types=1);

namespace Alarm\Contract;

use Alarm\Record;

/**
 * Interface FormatterInterface
 * @package Alarm\Contract
 */
interface FormatterInterface
{
    public function format(Record $record);
}
