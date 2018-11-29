<?php

namespace Mindy\QueryBuilder\Aggregation;

use Mindy\QueryBuilder\Expression;
use Mindy\QueryBuilder\QueryBuilder;

class Aggregation extends Expression
{
    protected $alias;

    protected $tableAlias;

    protected $fields;

    protected $fieldsSql = '';

    public function setFieldsSql($sql)
    {
        $this->fieldsSql = $sql;
        return $this;
    }

    public function setTableAlias($alias)
    {
        $this->tableAlias = $alias;
        return $this;
    }

    public function quoteColumn(QueryBuilder $qb)
    {
        $adapter = $qb->getAdapter();

        return [$adapter->quoteColumn($this->tableAlias), $adapter->quoteColumn($this->alias)];
    }

    public function toSQL(QueryBuilder $qb = null)
    {
        $sql = '';

        if ($this->tableAlias) {
            $ta = $this->tableAlias;

            if ($qb) {
                list($ta) = $this->quoteColumn($qb);
            }

            $sql = $ta . '.';
        }

        return $sql . $this->fieldsSql;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getAlias()
    {
        return $this->alias;
    }
    
    public function getQuotedAlias(QueryBuilder $qb = null)
    {
        if ($qb) {
            return $qb->getAdapter()->quoteColumn($this->alias);
        }

        return $this->alias;
    }

    public function __construct($field, $alias = '')
    {
        $this->fields = $field;
        $this->alias = $alias;
    }
}