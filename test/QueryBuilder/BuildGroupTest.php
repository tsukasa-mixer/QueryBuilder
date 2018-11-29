<?php

namespace Mindy\Tests\QueryBuilder;

class BuildGroupTest extends BaseTest
{
    public function testSimple()
    {
        $qb = $this->getQueryBuilder();
        $qb->group(['id', 'name']);
        $this->assertSql('GROUP BY `id`, `name`', $qb->buildGroup());
    }

    public function testString()
    {
        $qb = $this->getQueryBuilder();
        $qb->group('id, name');
        $this->assertSql('GROUP BY `id`, `name`', $qb->buildGroup());
    }

    public function testOrderEmpty()
    {
        $qb = $this->getQueryBuilder();
        $this->assertSql('', $qb->buildOrder());
    }
}
