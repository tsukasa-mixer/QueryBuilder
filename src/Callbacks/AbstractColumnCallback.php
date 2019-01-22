<?php

namespace Tsukasa\QueryBuilder\Callbacks;

use Tsukasa\QueryBuilder\Interfaces\ILookupBuilder;
use Tsukasa\QueryBuilder\Interfaces\QueryBuilderInterface;


abstract class AbstractColumnCallback
{
    abstract public function run(QueryBuilderInterface $queryBuilder, ILookupBuilder $lookupBuilder, array $lookupNodes, $value);
}