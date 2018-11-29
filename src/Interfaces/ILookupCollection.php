<?php

namespace Tsukasa\QueryBuilder\Interfaces;

interface ILookupCollection
{
    /**
     * @param $lookup
     * @return bool
     */
    public function has($lookup);

    /**
     * @param $lookup
     * @param $column
     * @param $value
     * @return mixed
     */
    public function process(IAdapter $adapter, $lookup, $column, $value);
}