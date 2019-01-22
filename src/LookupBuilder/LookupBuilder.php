<?php

namespace Tsukasa\QueryBuilder\LookupBuilder;

use Tsukasa\QueryBuilder\Exception\QBException;
use Tsukasa\QueryBuilder\QueryBuilder;
use Tsukasa\QueryBuilder\QueryBuilderInterface;

class LookupBuilder extends Base
{
    public function parseLookup(QueryBuilderInterface $queryBuilder, $rawLookup, $value)
    {
        $nodesCount = substr_count($rawLookup, $this->separator);
        $lookupNodes = explode($this->separator, $rawLookup);

        switch (substr_count($rawLookup, $this->separator)) {
            case 0:
                $rawLookup = $this->fetchColumnName($rawLookup);
                return [$this->default, $rawLookup, $value];
            case 1:
                if ($this->hasLookup(end($lookupNodes))) {
                    list($column, $lookup) = explode($this->separator, $rawLookup);
                    if ($this->hasLookup($lookup) === false) {
                        throw new QBException('Unknown lookup:' . $lookup);
                    }
                    $column = $this->fetchColumnName($column);
                    return [$lookup, $column, $value];
                }
                break;
            default:
                if ($nodesCount > 1) {
                    if ($this->columnCallback === null) {
                        throw new QBException('Unknown lookup: ' . $rawLookup);
                    }

                    return $this->runCallback($queryBuilder, explode($this->separator, $rawLookup), $value);
                }
        }

        return $this->runCallback($queryBuilder, $lookupNodes, $value);
    }

    public function buildJoin(QueryBuilderInterface $queryBuilder, $lookup)
    {
        if (substr_count($lookup, $this->getSeparator()) > 0) {
            return $this->runJoinCallback($queryBuilder, explode($this->getSeparator(), $lookup));
        }

        return false;
    }

    public function parse(QueryBuilderInterface $queryBuilder, array $where)
    {
        $conditions = [];
        foreach ($where as $lookup => $value) {
            /**
             * Parse new QOr([[username => 1], [username => 2]])
             */
            if (is_numeric($lookup) && is_array($value)) {
                $lookup = key($value);
                $value = array_shift($value);
            }
            $conditions[] = $this->parseLookup($queryBuilder, $lookup, $value);
        }
        return $conditions;
    }
}