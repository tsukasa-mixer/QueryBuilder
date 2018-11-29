<?php

namespace Tsukasa\QueryBuilder\LookupBuilder;

use Exception;
use Tsukasa\QueryBuilder\Interfaces\IAdapter;
use Tsukasa\QueryBuilder\Interfaces\ILookupBuilder;
use Tsukasa\QueryBuilder\Interfaces\ILookupCollection;
use Tsukasa\QueryBuilder\QueryBuilder;

abstract class Base implements ILookupBuilder
{
    /**
     * @var string
     */
    protected $default = 'exact';
    /**
     * @var string
     */
    protected $separator = '__';
    /**
     * @var callable|null
     */
    protected $callback = null;
    /**
     * @var callable|null
     */
    protected $joinCallback = null;
    /**
     * @var null|\Closure
     */
    protected $fetchColumnCallback = null;
    /**
     * @var ILookupCollection[]
     */
    private $_lookupCollections = [];

    public function __clone()
    {
        foreach ($this as $key => $val) {
            if (is_object($val) || is_array($val)) {
                $this->{$key} = unserialize(serialize($val));
            }
        }
    }

    /**
     * @param ILookupCollection $lookupCollection
     * @return $this
     */
    public function addLookupCollection(ILookupCollection $lookupCollection)
    {
        $this->_lookupCollections[] = $lookupCollection;
        return $this;
    }

    /**
     * @param mixed $callback
     * @return $this
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * @param $callback
     * @return $this
     */
    public function setJoinCallback($callback)
    {
        $this->joinCallback = $callback;
        return $this;
    }
    
    public function setFetchColumnCallback($callback)
    {
        $this->fetchColumnCallback = $callback;
        return $this;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function getJoinCallback()
    {
        return $this->joinCallback;
    }

    public function fetchColumnName($column)
    {
        if ($this->fetchColumnCallback === null) {
            return $column;
        }

        return $this->fetchColumnCallback->run($column);
    }

    public function runCallback(QueryBuilder $queryBuilder, $lookupNodes, $value)
    {
        if ($this->callback === null) {
            return null;
        }
        return $this->callback->run($queryBuilder, $this, $lookupNodes, $value);
    }

    public function runJoinCallback(QueryBuilder $queryBuilder, $lookupNodes)
    {
        if ($this->joinCallback === null) {
            return null;
        }
        return $this->joinCallback->run($queryBuilder, $this, $lookupNodes);
    }

    public function getSeparator()
    {
        return $this->separator;
    }

    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param $lookup
     * @return bool
     */
    public function hasLookup($lookup)
    {
        foreach ($this->_lookupCollections as $collection) {
            if ($collection->has($lookup)) {
                return true;
            }
        }
        return false;
    }


    /**
     * @param $lookup
     * @param $column
     * @param $value
     * @return string
     * @throws Exception
     * @exception \Exception
     */
    public function runLookup(IAdapter $adapter, $lookup, $column, $value)
    {
        foreach ($this->_lookupCollections as $collection) {
            if ($collection->has($lookup)) {
                return $collection->process($adapter, $lookup, $column, $value);
            }
        }
        throw new Exception('Unknown lookup: ' . $lookup . ', column: ' . $column . ', value: ' . (is_array($value) ? print_r($value, true) : $value));
    }

    abstract public function parse(QueryBuilder $queryBuilder, array $where);
}