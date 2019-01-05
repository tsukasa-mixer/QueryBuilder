<?php

namespace Tsukasa\Tests\QueryBuilder;

use Tsukasa\QueryBuilder\Expression;

class BuildUpdateTest extends BaseTest
{
    public function testSimple()
    {
        $qb = $this->getQueryBuilder();
        $qb->setTypeUpdate()->where(['id' => 1])->update('test', ['name' => 'foo']);
        $this->assertSql('UPDATE `test` SET `name`=\'foo\' WHERE (`id`=1)', $qb->toSQL());
    }

    public function testExpression()
    {
        $qb = $this->getQueryBuilder();
        $qb->setTypeUpdate()->where(['id' => 1])->update('test', ['id' => new Expression('`id`+1')]);
        $this->assertSql('UPDATE `test` SET `id`=`id`+1 WHERE (`id`=1)', $qb->toSQL());
    }

    public function testNull()
    {
        $qb = $this->getQueryBuilder();
        $qb->setTypeUpdate()->where(['id' => 1])->update('test', ['name' => null]);
        $this->assertSql('UPDATE `test` SET `name`=NULL WHERE (`id`=1)', $qb->toSQL());
    }
}