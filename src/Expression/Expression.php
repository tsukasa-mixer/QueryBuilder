<?php

namespace Tsukasa\QueryBuilder\Expression;

class Expression extends AbstractExpression
{
    protected $expression;

    public function __construct($expression, array $params = [])
    {
        $this->expression = $expression;
    }

    public function toSQL()
    {
        if ($this->expression) {
            return $this->generateJoin() ?: '';
        }

        return '';
    }

    protected function generateJoin()
    {

        if (strpos($this->expression, '{') !== false)
        {
            $tableAlias = $this->qb->getAlias();

            return preg_replace_callback('~\{([^\}]+)\}~', function($match) use ($tableAlias) {
                $rawColumn = $match[1];

                list(, $column) = $this->qb->getLookupBuilder()->parseLookup($this->qb, $rawColumn, null);
                $column = $this->qb->getLookupBuilder()->fetchColumnName($column);

                if ($tableAlias !== null && strpos($column, '.') === false) {
                    $column = $tableAlias . '.' . $column;
                }

                return $this->qb->getAdapter()->buildColumns($column);

            }, $this->expression);
        }

        return $this->expression;
    }
}