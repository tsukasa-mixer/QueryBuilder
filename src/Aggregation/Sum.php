<?php

namespace Tsukasa\QueryBuilder\Aggregation;

class Sum extends Aggregation
{
    protected function expressionTemplate()
    {
        return 'SUM({field})';
    }
}