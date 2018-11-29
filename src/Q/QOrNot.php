<?php

namespace Tsukasa\QueryBuilder\Q;

use Tsukasa\QueryBuilder\QueryBuilder;

class QOrNot extends QOr
{
    public function toSQL(QueryBuilder $queryBuilder)
    {
        return 'NOT (' . parent::toSQL($queryBuilder) . ')';
    }
}