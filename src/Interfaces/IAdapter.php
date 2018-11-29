<?php

namespace Tsukasa\QueryBuilder\Interfaces;

interface IAdapter
{
    /**
     * @param $column
     * @return string
     */
    public function quoteColumn($column);

    /**
     * @param $value
     * @return string
     */
    public function quoteValue($value);

    /**
     * @param $tableName
     * @return string
     */
    public function quoteTableName($tableName);
}