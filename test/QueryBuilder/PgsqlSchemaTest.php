<?php

namespace Mindy\Tests\QueryBuilder;

class PgsqlSchemaTest extends SchemaTest
{
    protected $driver = 'pgsql';

    public function testLimitOffset()
    {
        $sql = $this->getQueryBuilder()->from('profile')->offset(1)->toSQL();
        $this->assertEquals($this->quoteSql('SELECT * FROM [[profile]] LIMIT ALL OFFSET 1'), $sql);
    }
}