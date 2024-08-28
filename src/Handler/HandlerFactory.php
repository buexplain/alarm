<?php

declare(strict_types=1);

namespace Alarm\Handler;

use Alarm\Contract\HandlerInterface;
use Alarm\Exception\InvalidConfigException;
use Hyperf\Contract\ConfigInterface;
use function Hyperf\Support\make;

/**
 * Class HandlerFactory
 * @package Alarm\Handler
 */
class HandlerFactory
{
    /**
     * @var ConfigInterface
     */
    protected ConfigInterface $config;

    /**
     * @var array
     */
    protected array $pool = [];

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @param $name
     * @return HandlerInterface
     */
    public function get($name): HandlerInterface
    {
        $class = $this->pool[$name] ?? null;
        if (! $class) {
            $config = (array) $this->config->get('alarm', []);
            if (! isset($config[$name])) {
                throw new InvalidConfigException(sprintf('Alarm config[\'%s\'] is not defined.', $name));
            }
            if (! isset($config[$name]['class'])) {
                throw new InvalidConfigException(sprintf('Alarm config[\'%s\'][\'class\'] is not defined.', $name));
            }
            $class = make($config[$name]['class'], $config[$name]['constructor'] ?? []);
            if (! ($class instanceof HandlerInterface)) {
                throw new InvalidConfigException(sprintf('Alarm config[\'%s\'][\'class\'] is invalid.', $name));
            }
            $this->pool[$name] = $class;
        }
        return $class;
    }
}
