<?php

namespace Tsukasa\QueryBuilder\Driver;

use Doctrine\DBAL\Driver\PDOSqlite\Driver;

class SqliteDriver extends Driver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        $connect = parent::connect($params, $username, $password, $driverOptions);
        $connect->sqliteCreateFunction('REGEXP', 'preg_match', 2);
        return $connect;
    }
}