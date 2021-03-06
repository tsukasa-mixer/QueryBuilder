<?php

namespace Tsukasa\Tests\QueryBuilder;

abstract class SchemaTest extends BaseTest
{
    abstract public function testLimitOffset();

    public function testRandomOrder()
    {
        $adapter = $this->getQueryBuilder()->getAdapter();
        switch ($this->getConnection()->getDriver()->getName()) {
            case 'sqlite':
            case 'pgsql':
                $this->assertEquals('RANDOM()' , $adapter->getRandomOrder());
                break;
            case 'mysql':
                $this->assertEquals('RAND()' , $adapter->getRandomOrder());
                break;
        }
    }

    public function testDistinct()
    {
        $qb = $this->getQueryBuilder();
        $this->assertSql('SELECT * FROM `profile`', $qb->setFrom('profile')->toSQL());
        $this->assertSql('SELECT DISTINCT `description` FROM `profile`', $qb->setSelect('description')->setOptions('DISTINCT')->setFrom('profile')->toSQL());
    }

    public function testGetDateTime()
    {
        $a = $this->getQueryBuilder()->getAdapter();
        $timestamp = strtotime('2016-07-22 13:54:09');
        $this->assertEquals('2016-07-22', $a->getDate($timestamp));
        $this->assertEquals('2016-07-22 13:54:09', $a->getDateTime($timestamp));

        $this->assertEquals('2016-07-22', $a->getDate((string)$timestamp));
        $this->assertEquals('2016-07-22 13:54:09', $a->getDateTime((string)$timestamp));

        $this->assertEquals('2016-07-22', $a->getDate('2016-07-22 13:54:09'));
        $this->assertEquals('2016-07-22 13:54:09', $a->getDateTime('2016-07-22 13:54:09'));
    }
}