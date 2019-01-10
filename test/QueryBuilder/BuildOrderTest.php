<?php

namespace Tsukasa\Tests\QueryBuilder;

class BuildOrderTest extends BaseTest
{
    public function testOrder()
    {
        $qb = $this->getQueryBuilder();
        $qb->setOrder(['id', '-name']);
        $this->assertSql('ORDER BY `id` ASC, `name` DESC', $qb->buildOrder());
    }

    public function testString()
    {
        $qb = $this->getQueryBuilder();
        $qb->setOrder('id ASC, name DESC');
        $this->assertSql('ORDER BY `id` ASC, `name` DESC', $qb->buildOrder());

        $qb = $this->getQueryBuilder();
        $qb->setOrder('id, name');
        $this->assertSql('ORDER BY `id` ASC, `name` ASC', $qb->buildOrder());
    }

    public function testOrderEmpty()
    {
        $qb = $this->getQueryBuilder();
        $this->assertSql('', $qb->buildOrder());
    }
}