<?php

namespace Tsukasa\Tests\QueryBuilder;

class BuildUnionTest extends BaseTest
{
    public function testQueryBuilder()
    {
        $qb = $this->getQueryBuilder();
        $qb->select('a, b, c')->from('test');
        $qb->union(clone $qb, true);
        $this->assertEquals($this->quoteSql('SELECT `a`, `b`, `c` FROM `test` UNION ALL (SELECT `a`, `b`, `c` FROM `test`)'), $qb->toSQL());
    }

    public function testOrder()
    {
        $qb = $this->getQueryBuilder();
        $qb->select('a, b, c')->from('test')->order(['-a']);
        $qb->union(clone $qb, true);
        $this->assertSql(
            'SELECT `a`, `b`, `c` FROM `test` UNION ALL (SELECT `a`, `b`, `c` FROM `test`) ORDER BY `a` DESC',
            $qb->toSQL()
        );
    }

    public function testString()
    {
        $qb = $this->getQueryBuilder();
        $qb->select('a, b, c')->from('test');
        $qb->union('SELECT `a`, `b`, `c` FROM `test`', true);
        $this->assertSql(
            'SELECT `a`, `b`, `c` FROM `test` UNION ALL (SELECT `a`, `b`, `c` FROM `test`)',
            $qb->toSQL()
        );
    }
}