<?php

namespace Tsukasa\QueryBuilder\Interfaces;

/**
 * Interface ILookupBuilder
 * @package Tsukasa\QueryBuilder
 */
interface ILookupBuilder
{
    /**
     * @param QueryBuilderInterface $queryBuilder
     * @param $lookup
     * @param $value
     * @return array
     */
    public function parseLookup(QueryBuilderInterface $queryBuilder, $lookup, $value);

    /**
     * @param QueryBuilderInterface $queryBuilder
     * @param array $where
     * @return mixed
     */
    public function parse(QueryBuilderInterface $queryBuilder, array $where);

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