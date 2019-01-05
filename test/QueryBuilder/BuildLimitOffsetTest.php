<?php

namespace Tsukasa\Tests\QueryBuilder;

class BuildLimitOffsetTest extends BaseTest
{
    public $limit = '';

    public function testLimit()
    {
        $qb = $this->getQueryBuilder();
        $qb->limit(10);
        $this->assertSql('LIMIT 10', $qb->buildLimitOffset());
    }

    public function testLimitOffset()
    {
        $qb = $this->getQueryBuilder();
        $qb->limit(10);
        $qb->offset(10);
        $this->assertSql('LIMIT 10 OFFSET 10', $qb->buildLimitOffset());
    }

    public function testPaginate()
    {
        $qb = $this->getQueryBuilder();
        $qb->paginate(4, 10);
        $this->assertSql('LIMIT 10 OFFSET 30', $qb->buildLimitOffset());
    }
}