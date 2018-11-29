<?php

namespace Mindy\QueryBuilder\Aggregation;

use Mindy\QueryBuilder\QueryBuilder;

class Sum extends Aggregation
{
    public function toSQL(QueryBuilder $qb = null)
    {
        return 'SUM(' . parent::toSQL($qb) . ')' . (empty($this->alias) ? '' : ' AS ' . $this->getQuotedAlias($qb));
    }
}