<?php

namespace Tsukasa\QueryBuilder\Aggregation;

use Tsukasa\QueryBuilder\Expression\AbstractExpression;

abstract class Aggregation extends AbstractExpression
{
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
        return $this->expression();
    }

    public function getField()
    {
        return $this->fields;
    }

    public function __construct($field)
    {
        $this->fields = $field;
    }
}