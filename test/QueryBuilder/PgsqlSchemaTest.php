<?php

namespace Tsukasa\Tests\QueryBuilder;

class PgsqlSchemaTest extends SchemaTest
{
    protected $driver = 'pgsql';

    public function testLimitOffset()
    {
        $sql = $this->getQueryBuilder()->from('profile')->offset(1)->toSQL();
        $this->assertEquals($this->quoteSql('SELECT * FROM "profile" LIMIT ALL OFFSET 1'), $sql);
    }

    public function testDistinct()
    {
        $qb = $this->getQueryBuilder();
        $this->assertSql('SELECT * FROM "profile"', $qb->from('profile')->toSQL());
        $this->assertSql('SELECT DISTINCT "description" FROM "profile"', $qb->setSelect('description')->setOptions('DISTINCT')->from('profile')->toSQL());
    }
}