<?php

namespace Mindy\QueryBuilder\Aggregation;

use Mindy\QueryBuilder\QueryBuilder;

class Max extends Aggregation
{
    public function toSQL(QueryBuilder $qb = null)
    {
        return 'MAX(' . parent::toSQL($qb) . ')' . (empty($this->alias) ? '' : ' AS ' . $this->getQuotedAlias($qb) );
    }
}