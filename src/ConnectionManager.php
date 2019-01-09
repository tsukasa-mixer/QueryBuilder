<?php

namespace Tsukasa\QueryBuilder;

use Doctrine\DBAL\DriverManager;

/**
 * Class ConnectionManager
 * @package Tsukasa\QueryBuilder
 */
class ConnectionManager
{
    /**
     * @var string
     */
    protected $defaultConnection = 'default';

    /**
     * @var array|\Doctrine\DBAL\Connection[]
     */
    protected $connections = [];
    protected $configuration;
    protected $eventManager;

    /**
     * ConnectionManager constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->configure($config);
    }

    /**
     * @param array $connections
     * @throws \Doctrine\DBAL\DBALException
     */
    public function setConnections(array $connections)
    {
        foreach ($connections as $name => $config) {
            $this->connections[$name] = DriverManager::getConnection($config, $this->configuration, $this->eventManager);
        }
    }

    /**
     * @param array $config
     */
    protected function configure(array $config)
    {
        foreach ($config as $key => $value) {
            if (method_exists($this, 'set' . ucfirst($key))) {
                $this->{'set' . ucfirst($key)}($value);
            } else {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setDefaultConnection($name)
    {
        $this->defaultConnection = $name;
        return $this;
    }

    /**
     * @param null $name
     * @return \Doctrine\DBAL\Connection|null
     */
    public function getConnection($name = null)
    {
        if (empty($name)) {
            $name = $this->defaultConnection;
        }
        return $this->connections[$name];
    }
}