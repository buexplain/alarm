<?php

declare(strict_types=1);

namespace Alarm\Handler;

use Alarm\Contract\HandlerInterface;
use Alarm\Exception\InvalidConfigException;
use Hyperf\Contract\ConfigInterface;

/**
 * Class HandlerFactory.
 */
class HandlerFactory
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var array
     */
    protected $pool = [];

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @param $name
     */
    public function get($name): HandlerInterface
    {
        $class = isset($this->pool[$name]) ? $this->pool[$name] : null;
        if (! $class) {
            $config = (array) $this->config->get('alarm', []);
            if (! isset($config[$name])) {
                throw new InvalidConfigException(sprintf('Alarm config[\'%s\'] is not defined.', $name));
            }
            if (! isset($config[$name]['class'])) {
                throw new InvalidConfigException(sprintf('Alarm config[\'%s\'][\'class\'] is not defined.', $name));
            }
            $class = make($config[$name]['class'], isset($config[$name]['constructor']) ? $config[$name]['constructor'] : []);
            if (! ($class instanceof HandlerInterface)) {
                throw new InvalidConfigException(sprintf('Alarm config[\'%s\'][\'class\'] is invalid.', $name));
            }
            $this->pool[$name] = $class;
        }
        return $class;
    }
}
