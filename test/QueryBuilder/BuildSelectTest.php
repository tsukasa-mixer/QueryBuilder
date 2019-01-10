<?php

namespace Tsukasa\Tests\QueryBuilder;

use Tsukasa\QueryBuilder\Aggregation\Avg;
use Tsukasa\QueryBuilder\Aggregation\Count;
use Tsukasa\QueryBuilder\Aggregation\Max;
use Tsukasa\QueryBuilder\Aggregation\Min;
use Tsukasa\QueryBuilder\Aggregation\Sum;
use Tsukasa\QueryBuilder\Callbacks\AbstractJoinCallback;
use Tsukasa\QueryBuilder\Expression;
use Tsukasa\QueryBuilder\Interfaces\ILookupBuilder;
use Tsukasa\QueryBuilder\LookupBuilder\LookupBuilder;
use Tsukasa\QueryBuilder\QueryBuilder;

class BuildSelectJoinCallback extends AbstractJoinCallback
{
    public function run(QueryBuilder $qb, ILookupBuilder $lookupBuilder, array $lookupNodes)
    {
        $column = '';
        $alias = '';
        foreach ($lookupNodes as $i => $nodeName) {
            if ($i + 1 == count($lookupNodes)) {
                $column = $nodeName;
            } else {
                switch ($nodeName) {
                    case 'user':
                        $alias = 'user1';
                        $qb->join('LEFT JOIN', $nodeName, ['user1.id' => 'customer.user_id'], $alias);
                        break;
                }
            }
        }

        if (empty($alias) || empty($column)) {
            return false;
        }

        return [$alias, $column];
    }
}

class BuildSelectTest extends BaseTest
{
    public function testSelectExpression()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect([
            'id', 'root', 'lft', 'rgt',
            new Expression('`rgt`-`lft`-1 AS `move`')
        ]);
        $this->assertSql('SELECT `id`, `root`, `lft`, `rgt`, `rgt`-`lft`-1 AS `move`', $qb->buildSelect());
    }

    public function testArray()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect(['id', 'name']);
        $this->assertSql($this->quoteSql('SELECT `id`, `name`'), $qb->buildSelect());
    }

    public function testString()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect('id, name');
        $this->assertSql('SELECT `id`, `name`', $qb->buildSelect());
    }

    public function testMultiple()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect('id');
        $this->assertSql('SELECT `id`', $qb->buildSelect());
        $qb->setSelect('name');
        $this->assertSql('SELECT `name`', $qb->buildSelect());
    }

    public function testStringWithAlias()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect('id AS foo, name AS bar');
        $this->assertSql('SELECT `id` AS `foo`, `name` AS `bar`', $qb->buildSelect());
    }

    public function testSubSelectString()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect('(SELECT `id` FROM `test`) AS `id_list`');
        $this->assertSql('SELECT (SELECT `id` FROM `test`) AS `id_list`', $qb->buildSelect());
    }

    public function testAlias()
    {
        $qb = $this->getQueryBuilder();
        $qb->setAlias('test1')->setSelect(['id'])->from('test');
        $this->assertSql('SELECT `test1`.`id`', $qb->buildSelect());
    }

    public function testAliasBackward()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect(['id'])->from('test')->setAlias('test1');
        $this->assertSql('SELECT `test1`.`id`', $qb->buildSelect());
    }

    public function testAliasFromString()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect('id')->from('test')->setAlias('test1');
        $this->assertSql('SELECT `test1`.`id`', $qb->buildSelect());
    }

    public function testSubSelect()
    {
        $qbSub = $this->getQueryBuilder();
        $qbSub->setSelect('id')->from('test');

        $qb = $this->getQueryBuilder();
        $qb->setSelect(['test' => $qbSub->toSQL()]);
        $this->assertSql(
            'SELECT (SELECT `id` FROM `test`) AS `test`',
            $qb->buildSelect()
        );
    }

    public function testSubSelectAlias()
    {
        $qbSub = $this->getQueryBuilder();
        $qbSub->setSelect('id')->from('test');

        $qb = $this->getQueryBuilder();
        $qb->setSelect(['id_list' => $qbSub->toSQL()]);
        $this->assertSql(
            'SELECT (SELECT `id` FROM `test`) AS `id_list`',
            $qb->buildSelect()
        );
    }

    public function testSelectAutoJoin()
    {
        $qb = $this->getQueryBuilder();
        $qb->getLookupBuilder()->setJoinCallback(new BuildSelectJoinCallback);
        $qb->setSelect(['user__username'])->from('customer');

        $this->assertSql(
            'SELECT `user1`.`username` FROM `customer` LEFT JOIN `user` AS `user1` ON `user1`.`id`=`customer`.`user_id`',
            $qb->toSQL()
        );
    }

    public function testCount()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect(['test' => new Count('*')]);
        $this->assertSql('SELECT COUNT(*) AS `test`', $qb->buildSelect());

        $qb = $this->getQueryBuilder();
        $qb->setSelect(new Count('*'));
        $this->assertEquals('SELECT COUNT(*)', $qb->buildSelect());
    }

    public function testAvg()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect(new Avg('*'));
        $this->assertEquals('SELECT AVG(*)', $qb->buildSelect());
    }

    public function testSum()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect(new Sum('*'));
        $this->assertEquals('SELECT SUM(*)', $qb->buildSelect());
    }

    public function testMin()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect(new Min('*'));
        $this->assertEquals('SELECT MIN(*)', $qb->buildSelect());
    }

    public function testMax()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect(new Max('*'));
        $this->assertEquals('SELECT MAX(*)', $qb->buildSelect());
    }

    public function testSelect()
    {
        $qb = $this->getQueryBuilder();
        $this->assertEquals('SELECT *', $qb->buildSelect());
    }

    public function testSelectDistinct()
    {
        $qb = $this->getQueryBuilder();
        $qb->setSelect(null)->setOptions('DISTINCT');
        $this->assertEquals('SELECT DISTINCT *', $qb->buildSelect());
    }
}