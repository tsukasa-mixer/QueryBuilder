<?php

namespace Tsukasa\QueryBuilder\Q;

use Tsukasa\QueryBuilder\Interfaces\IAdapter;
use Tsukasa\QueryBuilder\Interfaces\ILookupBuilder;
use Tsukasa\QueryBuilder\QueryBuilder;

abstract class Q
{
    /**
     * @var array|string|Q
     */
    protected $where;
    /**
     * @var string
     */
    protected $operator = 'AND';
    /**
     * @var ILookupBuilder
     */
    protected $lookupBuilder;
    /**
     * @var IAdapter
     */
    protected $adapter;

    /** @var QueryBuilder */
    protected $qb;
    /**
     * @var string|null
     */
    private $_tableAlias;

    public function __construct($where)
    {
        $this->where = $where;
    }

    public function setQB(QueryBuilder $queryBuilder)
    {
        $this->qb = $queryBuilder;
        return $this;
    }

    public function setTableAlias($tableAlias)
    {
        $this->_tableAlias = $tableAlias;
        return $this;
    }

    public function setLookupBuilder(ILookupBuilder $lookupBuilder)
    {
        $this->lookupBuilder = $lookupBuilder;
        return $this;
    }

    public function setAdapter(IAdapter $adapter)
    {
        $this->adapter = $adapter;
        return $this;
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
     * @return string
     * @throws Exception
     */
    protected function parsePart($part, $operator = null)
    {
        if ($operator === null) {
            $operator = $this->getOperator();
        }

        return $this->qb->parseCondition($part, $operator);
    }
}