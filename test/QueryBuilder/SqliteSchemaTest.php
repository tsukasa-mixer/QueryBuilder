<?php

namespace Tsukasa\Tests\QueryBuilder;

class SqliteSchemaTest extends SchemaTest
{
    protected $driver = 'sqlite';

    public function testLimitOffset()
    {
        $sql = $this->getQueryBuilder()->setFrom('profile')->setOffset(1)->toSQL();
        $this->assertEquals($this->quoteSql('SELECT * FROM `profile` LIMIT 9223372036854775807 OFFSET 1'), $sql);
    }
}