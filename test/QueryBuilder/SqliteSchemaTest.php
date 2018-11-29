<?php

namespace Mindy\Tests\QueryBuilder;

class SqliteSchemaTest extends SchemaTest
{
    protected $driver = 'sqlite';

    public function testLimitOffset()
    {
        $sql = $this->getQueryBuilder()->from('profile')->offset(1)->toSQL();
        $this->assertEquals($this->quoteSql('SELECT * FROM [[profile]] LIMIT 9223372036854775807 OFFSET 1'), $sql);
    }
}