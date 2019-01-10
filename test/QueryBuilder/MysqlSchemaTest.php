<?php

namespace Tsukasa\Tests\QueryBuilder;

class MysqlSchemaTest extends SchemaTest
{
    protected $driver = 'mysql';

    public function testLimitOffset()
    {
        $sql = $this->getQueryBuilder()->setFrom('profile')->offset(1)->toSQL();
        $this->assertEquals($this->quoteSql('SELECT * FROM `profile` LIMIT 1, 18446744073709551615'), $sql);
    }
}