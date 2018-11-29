<?php

namespace Tsukasa\QueryBuilder\Aggregation;

use Tsukasa\QueryBuilder\QueryBuilder;

class Avg extends Aggregation
{
    public function toSQL(QueryBuilder $qb = null)
    {
        return 'AVG(' . parent::toSQL($qb) . ')' . (empty($this->alias) ? '' : ' AS ' . $this->getQuotedAlias($qb));
    }
}