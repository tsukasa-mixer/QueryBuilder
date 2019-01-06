<?php

namespace Tsukasa\QueryBuilder\Q;

class QOrNot extends QOr
{
    public function toSQL()
    {
        return 'NOT (' . parent::toSQL() . ')';
    }
}