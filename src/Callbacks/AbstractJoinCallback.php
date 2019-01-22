<?php

namespace Tsukasa\QueryBuilder\Callbacks;

use Tsukasa\QueryBuilder\Interfaces\ILookupBuilder;
use Tsukasa\QueryBuilder\Interfaces\QueryBuilderInterface;


abstract class AbstractJoinCallback
{
    abstract public function run(QueryBuilderInterface $queryBuilder, ILookupBuilder $lookupBuilder, array $lookupNodes);
}