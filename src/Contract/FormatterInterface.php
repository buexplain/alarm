<?php

declare(strict_types=1);

namespace Alarm\Contract;

use Alarm\Record;

/**
 * Interface FormatterInterface.
 */
interface FormatterInterface
{
    public function format(Record $record);
}
