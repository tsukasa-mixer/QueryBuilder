<?php

namespace Tsukasa\QueryBuilder\Callbacks;

use Tsukasa\QueryBuilder\Interfaces\ILookupBuilder;
use Tsukasa\QueryBuilder\QueryBuilder;


abstract class AbstractCallback
{
    abstract public function run(QueryBuilder $queryBuilder, ILookupBuilder $lookupBuilder, array $lookupNodes, $value);
}