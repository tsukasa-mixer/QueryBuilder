<?php

namespace Tsukasa\QueryBuilder\Aggregation;

use Tsukasa\QueryBuilder\QueryBuilder;

class Count extends Aggregation
{
    public function toSQL(QueryBuilder $qb = null)
    {
        return 'COUNT(' . parent::toSQL($qb) . ')' . (empty($this->alias) ? '' : ' AS ' . $this->getQuotedAlias($qb) );
    }
}