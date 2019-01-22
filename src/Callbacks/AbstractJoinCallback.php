<?php

namespace Tsukasa\QueryBuilder\Callbacks;

use Tsukasa\QueryBuilder\Interfaces\ILookupBuilder;
use Tsukasa\QueryBuilder\QueryBuilder;
use Tsukasa\QueryBuilder\QueryBuilderInterface;


abstract class AbstractJoinCallback
{
    abstract public function run(QueryBuilderInterface $queryBuilder, ILookupBuilder $lookupBuilder, array $lookupNodes);
}