<?php

namespace Tsukasa\QueryBuilder\LookupBuilder;

use Tsukasa\QueryBuilder\Callbacks\AbstractColumnCallback;
use Tsukasa\QueryBuilder\Callbacks\AbstractFetchColumnCallback;
use Tsukasa\QueryBuilder\Callbacks\AbstractJoinCallback;
use Tsukasa\QueryBuilder\Exception\QBException;
use Tsukasa\QueryBuilder\Interfaces\IAdapter;
use Tsukasa\QueryBuilder\Interfaces\ILookupBuilder;
use Tsukasa\QueryBuilder\Interfaces\ILookupCollection;
use Tsukasa\QueryBuilder\QueryBuilder;
use Tsukasa\QueryBuilder\QueryBuilderInterface;

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
     * @var AbstractColumnCallback|null
     */
    protected $columnCallback;
    /**
     * @var AbstractJoinCallback|null
     */
    protected $joinCallback;
    /**
     * @var AbstractFetchColumnCallback|null
     */
    protected $fetchColumnCallback;
    /**
     * @var ILookupCollection[]
     */
    private $_lookupCollections = [];

    public function __clone()
    {
        foreach ($this as $key => $val) {
            $this->{$key} = clone $val;
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
     * @param mixed $columnCallback
     * @return $this
     */
    public function setColumnCallback($columnCallback)
    {
        $this->columnCallback = $columnCallback;
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

    public function getColumnCallback()
    {
        return $this->columnCallback;
    }

    public function getJoinCallback()
    {
        return $this->joinCallback;
    }

    public function fetchColumnName($column)
    {
        if ($this->fetchColumnCallback !== null) {
            if ($this->fetchColumnCallback instanceof \Closure) {
                $call = $this->fetchColumnCallback;
                return $call($column);
            }


            if ($this->fetchColumnCallback instanceof AbstractFetchColumnCallback) {
                return $this->fetchColumnCallback->run($column);
            }
        }

        return $column;
    }

    public function runCallback(QueryBuilderInterface $queryBuilder, $lookupNodes, $value)
    {
        if ($this->columnCallback !== null) {
            if ($this->columnCallback instanceof \Closure) {
                $call = $this->columnCallback;
                return $call($queryBuilder, $this, $lookupNodes, $value);
            }

            if ($this->columnCallback instanceof AbstractColumnCallback) {
                return $this->columnCallback->run($queryBuilder, $this, $lookupNodes, $value);
            }
        }

        return null;
    }

    public function runJoinCallback(QueryBuilderInterface $queryBuilder, $lookupNodes)
    {
        if ($this->joinCallback !== null) {
            if ($this->joinCallback instanceof \Closure) {
                $call = $this->joinCallback->bindTo($this, $this);
                return $call($queryBuilder, $this, $lookupNodes);
            }

            if ($this->joinCallback instanceof AbstractJoinCallback) {
                return $this->joinCallback->run($queryBuilder, $this, $lookupNodes);
            }
        }

        return null;
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
     * @param IAdapter $adapter
     * @param $lookup
     * @param $column
     * @param $value
     * @return string
     * @exception \Exception
     */
    public function runLookup(IAdapter $adapter, $lookup, $column, $value)
    {
        foreach ($this->_lookupCollections as $collection) {
            if ($collection->has($lookup)) {
                return $collection->process($adapter, $lookup, $column, $value);
            }
        }
        throw new QBException('Unknown lookup: ' . $lookup . ', column: ' . $column . ', value: ' . (is_array($value) ? print_r($value, true) : $value));
    }

    abstract public function parse(QueryBuilderInterface $queryBuilder, array $where);
}