<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 25/07/16
 * Time: 16:56
 */

namespace Tsukasa\Tests\QueryBuilder;

use Tsukasa\QueryBuilder\Callbacks\AbstractJoinCallback;
use Tsukasa\QueryBuilder\Aggregation\Max;
use Tsukasa\QueryBuilder\Aggregation\Min;
use Tsukasa\QueryBuilder\Expression\Expression;
use Tsukasa\QueryBuilder\Interfaces\ILookupBuilder;
use Tsukasa\QueryBuilder\Interfaces\QueryBuilderInterface;

class BuildJoinCallback extends AbstractJoinCallback
{
    public function run(QueryBuilderInterface $queryBuilder, ILookupBuilder $lookupBuilder, array $lookupNodes)
    {
        $column = '';
        $alias = '';
        foreach ($lookupNodes as $i => $nodeName) {
            if ($i + 1 == count($lookupNodes)) {
                $column = $nodeName;
            } else {
                switch ($nodeName) {
                    case 'user':
                        $alias = 'user_1';
                        $queryBuilder->join('LEFT JOIN', $nodeName, ['user_1.id' => 'customer.user_id'], $alias);
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

class BuildJoinTest extends BaseTest
{
    protected $joinCallback;

    public function setUp()
    {
        parent::setUp();
        $this->joinCallback = new BuildJoinCallback();
    }

    public function testAlias()
    {
        $qb = $this->getQueryBuilder();
        $qb->join('LEFT JOIN', 'test', ['main.user_id' => 'test_user.id'], 'test_user');
        $this->assertSql('LEFT JOIN `test` AS `test_user` ON `main`.`user_id`=`test_user`.`id`', $qb->buildJoin());
        $this->assertTrue($qb->hasJoin('test_user'));
    }

    public function testSimple()
    {
        $qb = $this->getQueryBuilder();
        $qb->join('LEFT JOIN', 'test', ['id' => 'user_id']);
        $this->assertSql('LEFT JOIN `test` ON `id`=`user_id`', $qb->buildJoin());
    }

    public function testSimpleClone()
    {
        $qb = $this->getQueryBuilder();
        $qb->join('LEFT JOIN', 'test', ['id' => 'user_id']);

        $clone = clone $qb;
        $this->assertSql('LEFT JOIN `test` ON `id`=`user_id`', $clone->buildJoin());
    }

    public function testMultiple()
    {
        $qb = $this->getQueryBuilder();
        $qb->join('LEFT JOIN', 'test', ['id' => 'user_id']);
        $qb->join('INNER JOIN', 'user', ['parent_id' => 'id']);
        $this->assertSql('LEFT JOIN `test` ON `id`=`user_id` INNER JOIN `user` ON `parent_id`=`id`', $qb->buildJoin());
    }

    public function testRaw()
    {
        $qb = $this->getQueryBuilder();
        $qb->joinRaw('LEFT JOIN `test` ON `id`=`user_id`');
        $this->assertSql('LEFT JOIN `test` ON `id`=`user_id`', $qb->buildJoin());
    }

    public function testExpression()
    {
        $qb = $this->getQueryBuilder();
        $qb->join('LEFT JOIN', 'test', ['user_id' => new Expression('1')]);
        $this->assertSql('LEFT JOIN `test` ON `user_id`=1', $qb->buildJoin());
    }

    public function testJoinSubSelectString()
    {
        $qbSub = $this->getQueryBuilder();
        $qbSub->setFrom('user')->setSelect('id');

        $qb = $this->getQueryBuilder();
        $this->assertSql(
            'SELECT `c`.* FROM `comment` AS `c` INNER JOIN (SELECT `id` FROM `user`) AS `u` ON `u`.`id`=`c`.`user_id`',
            $qb->setSelect(['c.*'])->setFrom(['c' => 'comment'])
                ->join('INNER JOIN', $qbSub->toSQL(), ['u.id' => 'c.user_id'], 'u')->toSQL()
        );
    }

    public function testJoinSubSelect()
    {
        $qbSub = $this->getQueryBuilder();
        $qbSub->setFrom('user')->setSelect('id');

        $qb = $this->getQueryBuilder();
        $this->assertSql(
            'SELECT `c`.* FROM `comment` AS `c` INNER JOIN (SELECT `id` FROM `user`) AS `u` ON `u`.`id`=`c`.`user_id`',
            $qb->setSelect(['c.*'])->setFrom(['c' => 'comment'])
                ->join('INNER JOIN', $qbSub, ['u.id' => 'c.user_id'], 'u')->toSQL()
        );
    }

    public function testSelectAutoJoin()
    {
        $qb = $this->getQueryBuilder();
        $qb->getLookupBuilder()->setJoinCallback($this->joinCallback);
        $qb->setFrom('customer')->setSelect(['user__id']);
        $this->assertSql('LEFT JOIN `user` AS `user_1` ON `user_1`.`id`=`customer`.`user_id`', $qb->buildJoin());
    }

    public function testSelectAutoJoinAggregation()
    {
        $qb = $this->getQueryBuilder();
        $qb->getLookupBuilder()->setJoinCallback($this->joinCallback);
        $qb->setFrom('customer')->setSelect([
            'id_min' => new Min('user__id'),
            'id_max' => new Max('user__id'),
        ]);
        $this->assertSql('', $qb->buildJoin());
        $this->assertSql('SELECT MIN(`user_1`.`id`) AS `id_min`, MAX(`user_1`.`id`) AS `id_max` FROM `customer` LEFT JOIN `user` AS `user_1` ON `user_1`.`id`=`customer`.`user_id`', $qb->toSQL());
        $this->assertSql('LEFT JOIN `user` AS `user_1` ON `user_1`.`id`=`customer`.`user_id`', $qb->buildJoin());
    }
}