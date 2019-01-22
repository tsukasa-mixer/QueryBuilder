<?php

namespace Tsukasa\Tests\QueryBuilder;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Tsukasa\QueryBuilder\Interfaces\ISQLGenerator;
use Tsukasa\QueryBuilder\Interfaces\QueryBuilderInterface;
use Tsukasa\Tests\QueryBuilder\fixtures\TestQueryBuilder;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $driver = 'sqlite';
    /**
     * @var Connection
     */
    protected $connection;

    protected function setUp()
    {
        parent::setUp();
        if (!extension_loaded('pdo_' . $this->driver)) {
            $this->markTestSkipped('Missing extension for ' . $this->driver . ' driver');
        }

        $config = require __DIR__ . '/config/' . (@getenv('TRAVIS') ? 'config_travis.php' : 'config.php');

        $driverConfig = [];

        if (isset($config[$this->driver])) {
            $driverConfig = $config[$this->driver];
        } else {
            $this->markTestSkipped('Missing config for ' . $this->driver . ' driver');
        }

        $fixtures = $driverConfig['fixture'];
        unset($driverConfig['fixture']);

        $this->connection = DriverManager::getConnection($driverConfig);

        $this->loadFixtures($this->connection, $fixtures);
    }

    protected function getConnection()
    {
        return $this->connection;
    }

    protected function loadFixtures(Connection $connection, $fixtures)
    {
        $sql = file_get_contents($fixtures);
        if (empty($sql)) {
            return;
        }

        /** @var \PDOStatement $stmt */
        if ($connection instanceof \Doctrine\DBAL\Driver\PDOConnection) {
            // PDO Drivers
            try {
                $lines = 0;
                $stmt = $connection->prepare($sql);
                $stmt->execute();
                do {
                    // Required due to "MySQL has gone away!" issue
                    $stmt->fetch();
                    $stmt->closeCursor();
                    $lines++;
                } while ($stmt->nextRowset());
            } catch (\PDOException $e) {
                throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
            }
        } else {
            // Non-PDO Drivers (ie. OCI8 driver)
            $stmt = $connection->prepare($sql);
            $rs = $stmt->execute();
            if (!$rs) {
                $error = $stmt->errorInfo();
                throw new \RuntimeException($error[2], $error[0]);
            }
            $stmt->closeCursor();
        }
    }

    /**
     * @return ISQLGenerator
     */
    protected function getAdapter()
    {
        return $this->getQueryBuilder()->getAdapter();
    }

    /**
     * @return QueryBuilderInterface|TestQueryBuilder
     */
    protected function getQueryBuilder()
    {
        return  TestQueryBuilder::getInstance($this->getConnection());
    }

    /**
     * @param $sql
     * @return string
     */
    protected function quoteSql($sql)
    {
        return $this->getAdapter()->quoteSql($sql);
    }

    protected function assertSql($sql, $actual)
    {
        $this->assertEquals($this->quoteSql($sql), trim($actual));
    }
}