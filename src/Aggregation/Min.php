<?php

namespace Tsukasa\QueryBuilder\Aggregation;

class Min extends Aggregation
{
    protected function expressionTemplate()
    {
        return 'MIN({field})';
    }
}