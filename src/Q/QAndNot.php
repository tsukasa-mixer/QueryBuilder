<?php

namespace Tsukasa\QueryBuilder\Q;

class QAndNot extends QAnd
{
    public function toSQL()
    {
        return 'NOT (' . parent::toSQL() . ')';
    }
}