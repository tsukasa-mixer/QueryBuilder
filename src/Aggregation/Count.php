<?php

namespace Tsukasa\QueryBuilder\Aggregation;

class Count extends Aggregation
{
    protected function expressionTemplate()
    {
        return 'COUNT({field})';
    }
}