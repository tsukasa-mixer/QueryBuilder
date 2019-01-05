<?php

namespace Tsukasa\QueryBuilder\Aggregation;

class Max extends Aggregation
{
    protected function expressionTemplate()
    {
        return 'MAX({field})';
    }
}