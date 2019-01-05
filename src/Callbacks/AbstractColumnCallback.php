<?php

namespace Tsukasa\QueryBuilder\Callbacks;


abstract class AbstractColumnCallback
{
    abstract public function run($column);
}