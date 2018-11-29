<?php

namespace Tsukasa\QueryBuilder;

class Expression
{
    private $expression = '';

    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    public function toSQL(QueryBuilder $qb = null)
    {
        return $this->expression;
    }
}