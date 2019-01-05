<?php

namespace Tsukasa\Tests\QueryBuilder;

use Tsukasa\QueryBuilder\Database\Pgsql\Adapter;
use PDO;

class PgsqlQuoteTest extends BaseTest
{
    protected $driver = 'pgsql';

    public function testAutoQuoting()
    {
        $sql = 'SELECT `id`, `t.name` FROM `customer` t';
        $this->assertEquals('SELECT "id", "t"."name" FROM "customer" t', $this->getAdapter()->quoteSql($sql));
    }

    public function testQuoteValue()
    {
        $adapter = $this->getAdapter();
        $this->assertEquals(123, $adapter->quoteValue(123));
        $this->assertEquals("'string'", $adapter->quoteValue('string'));
        $this->assertEquals("'It''s interesting'", $adapter->quoteValue("It's interesting"));
    }

    public function testQuoteTableName()
    {
        $adapter = $this->getAdapter();
        $this->assertEquals('"table"', $adapter->quoteTableName('table'));
        $this->assertEquals('"table"', $adapter->quoteTableName('"table"'));
        $this->assertEquals('"schema"."table"', $adapter->quoteTableName('schema.table'));
        $this->assertEquals('"schema"."table"', $adapter->quoteTableName('schema."table"'));
        $this->assertEquals('"schema"."table"', $adapter->quoteTableName('"schema"."table"'));
        $this->assertEquals('(table)', $adapter->quoteTableName('(table)'));
    }

    public function testQuoteColumnName()
    {
        $adapter = $this->getAdapter();
        $this->assertEquals('"column"', $adapter->quoteColumn('column'));
        $this->assertEquals('"column"', $adapter->quoteColumn('"column"'));
        $this->assertEquals('"table"."column"', $adapter->quoteColumn('table.column'));
        $this->assertEquals('"table"."column"', $adapter->quoteColumn('table."column"'));
        $this->assertEquals('"table"."column"', $adapter->quoteColumn('"table"."column"'));
        $this->assertEquals('(column)', $adapter->quoteColumn('(column)'));
    }
}