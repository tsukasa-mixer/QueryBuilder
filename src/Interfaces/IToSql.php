<?php

namespace Tsukasa\QueryBuilder\Interfaces;

use Tsukasa\QueryBuilder\QueryBuilder;

interface IToSql
{
    /**
     * return SQL expression
     *
     * @return string
     */
    public function toSql();

    /**
     * @param QueryBuilder $queryBuilder
     * @return static
     */
    public function setQb(QueryBuilder $queryBuilder);
}