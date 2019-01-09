<?php

namespace Tsukasa\QueryBuilder\Interfaces;
use Tsukasa\QueryBuilder\QueryBuilder;

/**
 * Interface ILookupBuilder
 * @package Tsukasa\QueryBuilder
 */
interface ILookupBuilder
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param $lookup
     * @param $value
     * @return array
     */
    public function parseLookup(QueryBuilder $queryBuilder, $lookup, $value);

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $where
     * @return mixed
     */
    public function parse(QueryBuilder $queryBuilder, array $where);

    /**
     * @param \Closure $callback
     * @return mixed
     */
    public function setColumnCallback($callback);

    /**
     * @param ILookupCollection $lookupCollection
     * @return $this
     */
    public function addLookupCollection(ILookupCollection $lookupCollection);

    /**
     * @param IAdapter $adapter
     * @param $lookup
     * @param $column
     * @param $value
     * @return mixed
     */
    public function runLookup(IAdapter $adapter, $lookup, $column, $value);
}