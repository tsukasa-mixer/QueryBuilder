<?php

namespace Tsukasa\QueryBuilder\Q;

use Exception;
use Tsukasa\QueryBuilder\Expression;
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
    protected $operator;
    /**
     * @var ILookupBuilder
     */
    protected $lookupBuilder;
    /**
     * @var IAdapter
     */
    protected $adapter;
    /**
     * @var string|null
     */
    private $_tableAlias;

    public function __construct($where)
    {
        $this->where = $where;
    }

    public function setTableAlias($tableAlias)
    {
        $this->_tableAlias = $tableAlias;
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
    public function toSQL(QueryBuilder $queryBuilder)
    {
        return $this->parseWhere($queryBuilder);
    }

    /**
     * @return string
     */
    protected function parseWhere(QueryBuilder $queryBuilder)
    {
        return $this->parseConditions($queryBuilder, $this->where);
    }

    private function isWherePart($where)
    {
        return is_array($where) &&
        array_key_exists('___operator', $where) &&
        array_key_exists('___where', $where) &&
        array_key_exists('___condition', $where);
    }

    /**
     * @param array $where
     * @return string
     */
    protected function parseConditions(QueryBuilder $queryBuilder, $where)
    {
        if (empty($where)) {
            return '';
        }

        $sql = '';
        if ($this->isWherePart($where)) {
            $operator = $where['___operator'];
            $childWhere = $where['___where'];
            $condition = $where['___condition'];
            if ($this->isWherePart($childWhere)) {
                $whereSql = $this->parseConditions($queryBuilder, $childWhere);
                $sql .= '(' . $whereSql . ') ' . strtoupper($operator) . ' (' . $this->parsePart($queryBuilder, $condition, $operator) . ')';
            } else {
                $sql .= $this->parsePart($queryBuilder, $childWhere, $operator);
            }
        } else {
            $sql .= $this->parsePart($queryBuilder, $where);
        }

        if (empty($sql)) {
            return '';
        }

        return $sql;
    }

    /**
     * @param $part
     * @return string
     * @throws Exception
     */
    protected function parsePart(QueryBuilder $queryBuilder, $part, $operator = null)
    {
        if ($operator === null) {
            $operator = $this->getOperator();
        }

        if (is_string($part)) {
            return $part;
        }

        if (is_array($part)) {
            $sql = [];
            foreach ($part as $key => $value) {
                if ($part instanceof QueryBuilder) {
                    $sql[] = $part->toSQL();
                } else if ($value instanceof self) {
                    $sql[] = '(' . $this->parsePart($queryBuilder, $value) . ')';
                } else if (is_numeric($key) && is_array($value)) {
                    $sql[] = '(' . $this->parsePart($queryBuilder, $value) . ')';
                } else {
                    list($lookup, $column, $lookupValue) = $this->lookupBuilder->parseLookup($queryBuilder, $key, $value);
                    if (empty($this->_tableAlias) === false && strpos($column, '.') === false) {
                        $column = $this->_tableAlias . '.' . $column;
                    }
                    $sql[] = $this->lookupBuilder->runLookup($this->adapter, $lookup, $column, $lookupValue);
                }
            }
            return implode(' ' . $operator . ' ', $sql);
        }

        if ($part instanceof Expression) {
            return $this->adapter->quoteSql($part->toSQL());
        }

        if ($part instanceof self) {
            $part->setLookupBuilder($this->lookupBuilder);
            $part->setAdapter($this->adapter);
            $part->setTableAlias($this->_tableAlias);
            return $part->toSQL($queryBuilder);
        }

        if ($part instanceof QueryBuilder) {
            return $part->toSQL();
        }

        throw new \Exception("Unknown sql part type");
    }
}