<?php

declare(strict_types=1);

namespace Alarm\Exception;

use RuntimeException;

/**
 * Class WaitException
 * @package Alarm\Exception
 */
class WaitException extends RuntimeException
{
    protected int $second = 0;

    public function __construct(int $second)
    {
        parent::__construct();
        $this->second = $second;
    }

    public function getSecond(): int
    {
        return $this->second;
    }
}
