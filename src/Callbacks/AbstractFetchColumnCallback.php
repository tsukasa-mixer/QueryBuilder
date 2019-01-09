<?php

namespace Tsukasa\QueryBuilder\Callbacks;


abstract class AbstractFetchColumnCallback
{
    abstract public function run($column);
}