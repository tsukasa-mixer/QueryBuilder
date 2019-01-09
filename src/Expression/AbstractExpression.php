<?php

namespace Tsukasa\QueryBuilder\Expression;

use Tsukasa\QueryBuilder\Interfaces\IToSql;
use Tsukasa\QueryBuilder\QueryBuilder;

abstract class AbstractExpression implements IToSql
{
    /** @var QueryBuilder */
    protected $qb;
    /** @var string */
    protected $_tableAlias;

    public function setQB(QueryBuilder $queryBuilder)
    {
        $this->qb = $queryBuilder;
        return $this;
    }

    public function getTableAlias()
    {
        return $this->qb->getAlias();
    }

    /**
    /**
     * @return string
     */
    abstract public function toSQL();

}