<?php

namespace Tsukasa\QueryBuilder\Q;

use Tsukasa\QueryBuilder\QueryBuilder;

class QAndNot extends QAnd
{
    public function toSQL(QueryBuilder $queryBuilder)
    {
        return 'NOT (' . parent::toSQL($queryBuilder) . ')';
    }
}