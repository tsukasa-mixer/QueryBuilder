<?php

namespace Tsukasa\QueryBuilder\Expression;

class Expression extends AbstractExpression
{
    private $expression;

    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    public function toSQL()
    {
        return $this->expression ?: '';
    }
}