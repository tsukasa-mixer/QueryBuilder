<?php

namespace Tsukasa\Tests\QueryBuilder;

class BuildFromTest extends BaseTest
{
    public function testAlias()
    {
        $qb = $this->getQueryBuilder();
        $qb->setFrom(['test' => 'foo', 'bar']);
        $this->assertSql('FROM `foo` AS `test`, `bar`', $qb->buildFrom());

        $qb = $this->getQueryBuilder();
        $qb->setAlias('test')->setFrom('foo');
        $this->assertSql('FROM `foo` AS `test`', $qb->buildFrom());
    }

    public function testArray()
    {
        $qb = $this->getQueryBuilder();
        $qb->setFrom(['foo', 'bar']);
        $this->assertSql('FROM `foo`, `bar`', $qb->buildFrom());
    }

    public function testSimple()
    {
        $qb = $this->getQueryBuilder();
        $qb->setFrom('test');
        $this->assertSql('FROM `test`', $qb->buildFrom());
    }

    public function testSubSelectString()
    {
        $result = "FROM (SELECT `user_id` FROM `comment` WHERE (`name`='foo')) AS `t`";

        $qbSub = $this->getQueryBuilder();
        $qbSub->setFrom(['comment'])->setSelect('user_id')->addWhere(['name' => 'foo']);

        $qb = $this->getQueryBuilder()->setFrom(['t' => $qbSub->toSQL()]);
        $this->assertSql($result, $qb->buildFrom());
    }

    public function testSubSelect()
    {
        $result = "FROM (SELECT `user_id` FROM `comment` WHERE (`name`='foo')) AS `t`";

        $qbSub = $this->getQueryBuilder();
        $qbSub->setFrom(['comment'])->setSelect('user_id')->addWhere(['name' => 'foo']);

        $qb = $this->getQueryBuilder()->setFrom(['t' => $qbSub]);
        $this->assertSql($result, $qb->buildFrom());
    }
}
