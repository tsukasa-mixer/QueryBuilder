<?php

namespace Tsukasa\Tests\QueryBuilder;

class BuildUnionTest extends BaseTest
{
    public function testQueryBuilder()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect('a, b, c')->from('test');
        $qb->addUnion(clone $qb, true);
        $this->assertEquals($this->quoteSql('SELECT `a`, `b`, `c` FROM `test` UNION ALL (SELECT `a`, `b`, `c` FROM `test`)'), $qb->toSQL());
    }

    public function testOrder()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect('a, b, c')->from('test')->setOrder(['-a']);
        $qb->addUnion(clone $qb, true);
        $this->assertSql(
            'SELECT `a`, `b`, `c` FROM `test` UNION ALL (SELECT `a`, `b`, `c` FROM `test`) ORDER BY `a` DESC',
            $qb->toSQL()
        );
    }

    public function testString()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect('a, b, c')->from('test');
        $qb->addUnion('SELECT `a`, `b`, `c` FROM `test`', true);
        $this->assertSql(
            'SELECT `a`, `b`, `c` FROM `test` UNION ALL (SELECT `a`, `b`, `c` FROM `test`)',
            $qb->toSQL()
        );
    }
}