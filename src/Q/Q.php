<?php

namespace Tsukasa\QueryBuilder\Q;

use Tsukasa\QueryBuilder\Expression\AbstractExpression;

abstract class Q extends AbstractExpression
{
    /**
     * @var array|string|Q
     */
    protected $where;
    /**
     * @var string
     */
    protected $operator = 'AND';

    public function __construct($where)
    {
        $this->where = $where;
    }

    public function getWhere()
    {
        return $this->where;
    }

    public function addWhere($where)
    {
        $this->where[] = $where;
        return $this;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return string
     */
    public function toSQL()
    {
        return $this->parseConditions($this->where);
    }

    /**
     * @param array $where
     * @return string
     */
    protected function parseConditions($where)
    {
        if (empty($where)) {
            return '';
        }

        return $this->qb->parseCondition($where, $this->getOperator());
    }

    /**
     * @param $part
     * @param null|string $operator
     * @return string
     */
    protected function parsePart($part, $operator = null)
    {
        if ($operator === null) {
            $operator = $this->getOperator();
        }

        return $this->qb->parseCondition($part, $operator);
    }
}