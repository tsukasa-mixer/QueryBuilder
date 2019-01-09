<?php

namespace Tsukasa\QueryBuilder\Aggregation;

use Tsukasa\QueryBuilder\Expression\AbstractExpression;

abstract class Aggregation extends AbstractExpression
{
    protected $alias;

    protected $fields;

    protected $fieldsSql = '';

    public function setFieldSql($sql)
    {
        $this->fieldsSql = $sql;
        return $this;
    }


    protected function quoteColumn($column)
    {
        return $this->qb->getAdapter()->quoteColumn($column);
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

    public function toSQL()
    {
        $sql = $this->expression();

        if ($this->getAlias()) {
            $sql .= ' AS ' .  $this->quoteColumn($this->getAlias());
        }

        return $sql;
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