<?php

declare(strict_types=1);

namespace Alarm\Contract;

/**
 * Interface FormatterInterface.
 */
interface FormatterInterface
{
    public function format(Record $record);
}
