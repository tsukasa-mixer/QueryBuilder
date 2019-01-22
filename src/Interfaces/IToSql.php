<?php

namespace Tsukasa\QueryBuilder\Interfaces;

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