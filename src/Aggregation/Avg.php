<?php

namespace Tsukasa\QueryBuilder\Aggregation;

class Avg extends Aggregation
{
    protected function expressionTemplate()
    {
        return 'AVG({field})';
    }
}