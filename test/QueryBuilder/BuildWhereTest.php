<?php

namespace Tsukasa\Tests\QueryBuilder;

use Tsukasa\QueryBuilder\Expression;
use Tsukasa\QueryBuilder\Q\QAnd;
use Tsukasa\QueryBuilder\Q\QAndNot;
use Tsukasa\QueryBuilder\Q\QOr;
use Tsukasa\QueryBuilder\Q\QOrNot;

class BuildWhereTest extends BaseTest
{
    public function testHardSubQuery()
    {
        $subQuery = clone $this->getQueryBuilder();
        $subQuery->setTypeSelect()->select(['parent_id'])->from('test')->addWhere([
            'parent_id' => new Expression('`id`')
        ]);
        $this->assertSql('SELECT `parent_id` FROM `test` WHERE (`parent_id`=`id`)', $subQuery->toSQL());

        $subQuery = clone $this->getQueryBuilder();
        $subQuery
            ->setTypeSelect()
            ->select(['parent_id'])
            ->from('test')
            ->setAlias('test_1')
            ->addWhere(['parent_id' => new Expression('`test_1`.`id`')]);
        $this->assertSql('SELECT `test_1`.`parent_id` FROM `test` AS `test_1` WHERE (`test_1`.`parent_id`=`test_1`.`id`)', $subQuery->toSQL());
    }

    public function testHard()
    {
        $subQuery = clone $this->getQueryBuilder();
        $subQuery
            ->setTypeSelect()
            ->select(['parent_id'])
            ->from('test')
            ->setAlias('test_1')
            ->addWhere(['parent_id' => new Expression('`test_1`.`id`')]);
        $subQuerySql = 'SELECT `test_1`.`parent_id` FROM `test` AS `test_1` WHERE (`test_1`.`parent_id`=`test_1`.`id`)';
        $this->assertSql($subQuerySql, $subQuery->toSQL());

        $query = clone $this->getQueryBuilder();
        $query->setTypeSelect()
            ->select(['id', 'root', 'lft', 'rgt', new Expression('`test_1`.`rgt`-`test_1`.`lft`-1 AS `move`')])
            ->from('test')
            ->setAlias('test_1')
            ->addWhere(new QAndNot([
                'lft' => new Expression('`test_1`.`rgt`-1'),
                'id__in' => $subQuery
            ]))
            ->setOrder(['rgt']);

        $this->assertSql('SELECT `test_1`.`id`, `test_1`.`root`, `test_1`.`lft`, `test_1`.`rgt`, `test_1`.`rgt`-`test_1`.`lft`-1 AS `move`', $query->buildSelect());
        $this->assertSql('SELECT `test_1`.`id`, `test_1`.`root`, `test_1`.`lft`, `test_1`.`rgt`, `test_1`.`rgt`-`test_1`.`lft`-1 AS `move` FROM `test` AS `test_1` WHERE (NOT ((`test_1`.`lft`=`test_1`.`rgt`-1) AND (`test_1`.`id` IN ('
            . $subQuerySql . ')))) ORDER BY `test_1`.`rgt` ASC', $query->toSQL());
    }

    public function testQAnd()
    {
        $qb = $this->getQueryBuilder();
        $qb->addWhere(new QAnd(['id' => 1, 'name' => 'foo']));
        $this->assertSql('WHERE (`id`=1 AND `name`=\'foo\')', $qb->buildWhere());
    }

    public function testQAndNotRaw()
    {
        $qb = $this->getQueryBuilder();
        $qb->addWhere(new QAndNot('`a` != 1'));
        $this->assertSql('WHERE (NOT (`a` != 1))', $qb->buildWhere());
    }

    public function testQOr()
    {
        $qb = $this->getQueryBuilder();
        $qb->addWhere(new QOr(['id' => 1, 'name' => 'foo']));
        $this->assertSql('WHERE (`id`=1 OR `name`=\'foo\')', $qb->buildWhere());

        $qb = $this->getQueryBuilder();
        $qb->addWhere(new QOr([
            ['id' => 1, 'name' => 'username'], ['id' => 2, 'name' => 'foobar']
        ]));
        $this->assertSql('WHERE ((`id`=1 OR `name`=\'username\') OR (`id`=2 OR `name`=\'foobar\'))', $qb->buildWhere());
    }

    public function testQAndNot()
    {
        $qb = $this->getQueryBuilder();
        $qb->addWhere(new QAndNot(['id' => 1, 'name' => 'foo']));
        $this->assertSql('WHERE (NOT (`id`=1 AND `name`=\'foo\'))', $qb->buildWhere());

        $qb = $this->getQueryBuilder();
        $qb->addWhere(new QAndNot([
            'id__in' => [1, 2, 3],
            'price__gte' => 100
        ]));
    }

    public function testQOrNot()
    {
        $qb = $this->getQueryBuilder();
        $qb->addWhere(new QOrNot(['id' => 1, 'name' => 'foo']));
        $this->assertSql('WHERE (NOT (`id`=1 OR `name`=\'foo\'))', $qb->buildWhere());

        $qb = $this->getQueryBuilder();
        $qb->addWhere(new QOrNot([
            ['id' => 1], ['id' => 2]
        ]));
        $this->assertSql('WHERE (NOT ((`id`=1) OR (`id`=2)))', $qb->buildWhere());

        $qb = $this->getQueryBuilder();
        $qb->addWhere(new QOrNot([
            'id__in' => [1, 2, 3],
            'price__gte' => 100
        ]));
        $this->assertEquals($this->quoteSql('SELECT * WHERE (NOT (`id` IN (1, 2, 3) OR `price`>=100))'), $qb->toSQL());
    }

    public function testMixed()
    {
        $qb = $this->getQueryBuilder();
        $qb->addWhere('`a` != 1')->addWhere(['id__in' => [1, 2, 3]])->addOrWhere(new QAndNot(['id' => 1]));
        $this->assertSql('SELECT * WHERE (((`a` != 1)) AND ((`id` IN (1, 2, 3)))) OR ((NOT (`id`=1)))', $qb->toSQL());
    }

    public function testWhereMore()
    {
        $qb = $this->getQueryBuilder();
        $qb->addOrWhere(['col1' => 1, 'col2' => 2])->addOrWhere(['col3' => 3, 'col4' => 4])->addWhere(['id' => 1]);
        $this->assertSql(
            'WHERE (((`id`=1)) OR (((`col1`=1) AND (`col2`=2)))) OR (((`col3`=3) AND (`col4`=4)))',
            $qb->buildWhere()
        );

        $qb = $this->getQueryBuilder();
        $qb->from('test')->addWhere(['id' => 2])->addOrWhere(['id' => 1])->addWhere(['id__isnt' => 3]);
        $this->assertSql('WHERE (((`id`=2)) AND ((`id`!=3))) OR ((`id`=1))', $qb->buildWhere());
    }

    public function testEmpty()
    {
        $qb = $this->getQueryBuilder();
        $this->assertSql('', $qb->buildWhere());
    }

    public function testDataTree()
    {
        $qb = $this->getQueryBuilder();
        $data = [
            'or',
            ['and', ['id' => 1]],
            ['and', ['id' => 2]]
        ];

        $this->assertSql('((`id`=1)) OR ((`id`=2))', $qb->buildCondition($data));
    }

    public function testSimple()
    {
        $data = [
            'or',
            ['and', ['id' => 1]],
            ['and', ['id' => 2]]
        ];

        $qb = $this->getQueryBuilder();
        $qb->addWhere(['id' => 1]);
        $qb->addOrWhere(['id' => 2]);
        $this->assertEquals($data, $qb->buildWhereTree());
        $this->assertSql('WHERE ((`id`=1)) OR ((`id`=2))', $qb->buildWhere());

        $qb = $this->getQueryBuilder();
        $qb->addOrWhere(['id' => 2]);
        $qb->addWhere(['id' => 1, 'name' => 2]);
        // TODO $this->assertEquals('((id=:qp0)) OR ((id=:qp1))', $qb->buildWhere());
        $this->assertSql('WHERE (((`id`=1) AND (`name`=2))) OR ((`id`=2))', $qb->buildWhere());
    }

    public function testWhere()
    {
        $qb = $this->getQueryBuilder();
        $qb->addOrWhere(new QOr(['id' => 2]));
        $this->assertSql('WHERE (`id`=2)', $qb->buildWhere());

        $qb = $this->getQueryBuilder();
        $qb->addOrWhere(new QOr([
            ['id' => 2],
            ['id' => 1]
        ]));
        $this->assertSql('WHERE ((`id`=2) OR (`id`=1))', $qb->buildWhere());

        $qb = $this->getQueryBuilder();
        $qb
            ->addWhere(new QOr([
                ['id' => 2],
                ['id' => 1]
            ]))
            ->addWhere(['id' => 3]);
        $this->assertSql('WHERE (((`id`=2) OR (`id`=1))) AND ((`id`=3))', $qb->buildWhere());

        $qb = $this->getQueryBuilder();
        $qb
            ->addWhere(['id' => 1, 'name' => 'foo'])
            ->addWhere(['id' => 3]);
        $this->assertSql("WHERE (((`id`=1) AND (`name`='foo'))) AND ((`id`=3))", $qb->buildWhere());
    }

    public function testSubQueryString()
    {
        $qb = $this->getQueryBuilder();
        $qb->addWhere(['id' => 'SELECT `id` FROM `test`']);
        $this->assertSql("WHERE (`id`=(SELECT `id` FROM `test`))", $qb->buildWhere());
    }

    public function testSubQuery()
    {
        $qb = $this->getQueryBuilder();
        $qb->addWhere([
            'id' => $this->getQueryBuilder()->select('id')->from('test')
        ]);
        $this->assertSql("WHERE (`id`=(SELECT `id` FROM `test`))", $qb->buildWhere());
    }

    public function testString()
    {
        $qb = $this->getQueryBuilder();
        $qb->addWhere('`id`=1');
        $this->assertSql("WHERE (`id`=1)", $qb->buildWhere());
    }

    public function testExpression()
    {
        $qb = $this->getQueryBuilder();
        $qb->addWhere(new Expression('id=1'));
        $this->assertSql("WHERE (id=1)", $qb->buildWhere());
    }
}