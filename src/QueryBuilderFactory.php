<?php

namespace Tsukasa\QueryBuilder;

use Doctrine\DBAL\Driver\Connection;
use Tsukasa\QueryBuilder\Interfaces\ILookupBuilder;
use Tsukasa\QueryBuilder\Interfaces\ISQLGenerator;

class QueryBuilderFactory
{
    /**
     * @var ISQLGenerator
     */
    protected $adapter;
    /**
     * @var ILookupBuilder
     */
    protected $lookupBuilder;
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * QueryBuilder constructor.
     * @param Connection $connection
     * @param ISQLGenerator $adapter
     * @param ILookupBuilder $lookupBuilder
     * @internal param ICallback $callback
     */
    public function __construct(Connection $connection, ISQLGenerator $adapter, ILookupBuilder $lookupBuilder)
    {
        $this->connection = $connection;
        $this->adapter = $adapter;
        $this->lookupBuilder = $lookupBuilder;
    }

    public function getQueryBuilder()
    {
        return new QueryBuilder($this->connection, $this->adapter, $this->lookupBuilder);
    }
}