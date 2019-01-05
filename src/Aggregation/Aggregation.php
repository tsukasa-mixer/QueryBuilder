<?php

namespace Tsukasa\QueryBuilder\Aggregation;

use Tsukasa\QueryBuilder\QueryBuilder;

abstract class Aggregation
{
    protected $alias;

    protected $tableAlias;

    protected $fields;

    protected $fieldsSql = '';

    /** @var QueryBuilder */
    protected $qb;

    public function setQb(QueryBuilder $qb)
    {
        $this->qb = $qb;

        return $this;
    }

    public function getQb()
    {
        return $this->qb;
    }

    public function setFieldSql($sql)
    {
        $this->fieldsSql = $sql;
        return $this;
    }

    public function setTableAlias($alias)
    {
        $this->tableAlias = $alias;
        return $this;
    }

    protected function quoteColumn($column)
    {
        return $this->getQb()->getAdapter()->quoteColumn($column);
    }

    /**
     * @return string
     */
    abstract protected function expressionTemplate();

    public function expression()
    {
        return strtr($this->expressionTemplate(), [
            '{field}' => $this->quoteColumn($this->fieldsSql)
        ]);
    }

    public function toSQL(QueryBuilder $qb = null)
    {
        if ($qb) { $this->qb = $qb; }

        return $this->expression() . (empty($this->alias) ? '' : ' AS ' .  $this->quoteColumn($this->getAlias()));
    }

    public function getField()
    {
        return $this->fields;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function __construct($field, $alias = '')
    {
        $this->fields = $field;
        $this->alias = $alias;
    }
}