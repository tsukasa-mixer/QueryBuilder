<?php

namespace Tsukasa\QueryBuilder\Interfaces;

use Tsukasa\QueryBuilder\QueryBuilder;
use Tsukasa\QueryBuilder\QueryBuilderInterface;

interface IToSql
{
    /**
     * return SQL expression
     *
     * @return string
     */
    public function toSql();

    /**
     * @param QueryBuilderInterface $queryBuilder
     * @return static
     */
    public function setQb(QueryBuilderInterface $queryBuilder);
}