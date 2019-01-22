<?php

namespace Tsukasa\Tests\QueryBuilder;

use Tsukasa\QueryBuilder\LookupBuilder\LookupBuilder;
use Tsukasa\QueryBuilder\Database\Sqlite\LookupCollection;

class LookupBuilderTest extends BaseTest
{
    protected $driver = 'mysql';

    /**
     * @return array
     */
    public function lookupProvider()
    {
        return [
            [['id' => 1], '`id`=1'],
            [['id__exact' => 1], '`id`=1'],
            [['id__gte' => 1], '`id`>=1'],
            [['id__lte' => 1], '`id`<=1'],
            [['id__gt' => 1], '`id`>1'],
            [['id__lt' => 1], '`id`<1'],
            [['id__isnt' => 1], '`id`!=1'],
            [['id__range' => [1, 2]], '`id` BETWEEN 1 AND 2'],
            [['id__isnull' => true], '`id` IS NULL'],
            [['id__isnull' => false], '`id` IS NOT NULL'],
            [['id__contains' => 'FOO'], '`id` LIKE \'%FOO%\''],
            [['id__icontains' => 'FOO'], 'LOWER(`id`) LIKE \'%foo%\''],
            [['id__startswith' => 'FOO'], '`id` LIKE \'FOO%\''],
            [['id__istartswith' => 'FOO'], 'LOWER(`id`) LIKE \'foo%\''],
            [['id__endswith' => 'FOO'], '`id` LIKE \'%FOO\''],
            [['id__iendswith' => 'FOO'], 'LOWER(`id`) LIKE \'%foo\''],
            [['id__in' => [1, 2, 'test']], '`id` IN (1, 2, \'test\')'],
            [['id__raw' => "?? `qwe`"], "`id` ?? `qwe`"],
        ];
    }

    /**
     * @dataProvider lookupProvider
     */
    public function testLookups($where, $whereSql)
    {
        $builder = new LookupBuilder();
        $builder->addLookupCollection(new LookupCollection());
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();

        list($lookup, $column, $value) = current($builder->parse($qb, $where));
        $this->assertEquals(str_replace('@', "'", $adapter->quoteSql($whereSql)), $builder->runLookup($adapter, $lookup, $column, $value));
    }
}