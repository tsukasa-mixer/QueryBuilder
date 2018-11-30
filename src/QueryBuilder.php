<?php

namespace Tsukasa\QueryBuilder;

use Doctrine\DBAL\Driver\Connection;
use Exception;
use Tsukasa\QueryBuilder\Aggregation\Aggregation;
use Tsukasa\QueryBuilder\Exception\QBException;
use Tsukasa\QueryBuilder\Interfaces\ILookupBuilder;
use Tsukasa\QueryBuilder\Interfaces\ILookupCollection;
use Tsukasa\QueryBuilder\Interfaces\ISQLGenerator;
use Tsukasa\QueryBuilder\LookupBuilder\LookupBuilder;
use Tsukasa\QueryBuilder\Q\Q;
use Tsukasa\QueryBuilder\Q\QAnd;

use Tsukasa\QueryBuilder\Database\Mysql\Adapter as MysqlAdapter;
use Tsukasa\QueryBuilder\Database\Sqlite\Adapter as SqliteAdapter;
use Tsukasa\QueryBuilder\Database\Pgsql\Adapter as PgsqlAdapter;

class QueryBuilder
{
    const TYPE_SELECT = 'SELECT';
    const TYPE_UPDATE = 'UPDATE';
    const TYPE_DELETE = 'DELETE';

    /**
     * @var array|Q|string
     */
    private $_whereAnd = [];
    /**
     * @var array|Q|string
     */
    private $_whereOr = [];
    /**
     * @var array|string
     */
    private $_join = [];
    private $_join_map = [];
    /**
     * @var array|string
     */
    private $_order = [];
    /**
     * @var null|string
     */
    private $_orderOptions = null;
    /**
     * @var array
     */
    private $_group = [];
    /**
     * @var array|string|\Tsukasa\QueryBuilder\Aggregation\Aggregation
     */
    private $_select = [];
    /**
     * @var null|string|array
     */
    private $_distinct = null;
    /**
     * @var array|string|null
     */
    private $_from = null;
    /**
     * @var array
     */
    private $_union = [];
    /**
     * @var null|string|int
     */
    private $_limit = null;
    /**
     * @var null|string|int
     */
    private $_offset = null;
    /**
     * @var array
     */
    private $_having = [];
    /**
     * @var null|string
     */
    private $_alias = null;
    /**
     * @var null|string sql query type SELECT|UPDATE|DELETE
     */
    private $_type = null;
    /**
     * @var array
     */
    private $_update = [];

    protected $tablePrefix = '';
    /**
     * @var BaseAdapter
     */
    protected $adapter;
    /**
     * @var ILookupBuilder
     */
    protected $lookupBuilder;
    /**
     * @var null
     */
    protected $schema;
    /**
     * Counter of joined tables aliases
     * @var int
     */
    private $_aliasesCount = 0;

    private $_joinAlias = [];

    /**
     * Strings options query
     * @var string
     */
    private $_queryOptions = '';
    /**
     * @var Connection
     */
    protected $connection;

    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param  $connection \Doctrine\DBAL\Connection
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    public function getDatabasePlatform()
    {
        return $this->getConnection()->getDatabasePlatform();
    }

    /**
     * @param Connection $connection
     * @return QueryBuilder
     * @throws Exception
     */
    public static function getInstance(Connection $connection)
    {
        $driver = $connection->getDriver();
        switch ($driver->getName()) {
            case 'pdo_mysql':
                $adapter = new MysqlAdapter($connection);
                break;
            case 'pdo_sqlite':
                $adapter = new SqliteAdapter($connection);
                break;
            case 'pdo_pgsql':
                $adapter = new PgsqlAdapter($connection);
                break;
            default:
                throw new Exception('Unknown driver');
        }
        $lookupBuilder = new LookupBuilder();
        $lookupBuilder->addLookupCollection($adapter->getLookupCollection());
        return new QueryBuilder($connection, $adapter, $lookupBuilder);
    }

    /**
     * QueryBuilder constructor.
     * @param Connection $connection
     * @param BaseAdapter $adapter
     * @param ILookupBuilder $lookupBuilder
     */
    public function __construct(Connection $connection, BaseAdapter $adapter, ILookupBuilder $lookupBuilder)
    {
        $this->connection = $connection;
        $this->adapter = $adapter;
        $this->lookupBuilder = $lookupBuilder;
    }

    /**
     * @param ILookupCollection $lookupCollection
     * @return $this
     */
    public function addLookupCollection(ILookupCollection $lookupCollection)
    {
        $this->lookupBuilder->addLookupCollection($lookupCollection);
        return $this;
    }

    /**
     * @return $this
     */
    public function setTypeSelect()
    {
        $this->_type = self::TYPE_SELECT;
        return $this;
    }

    /**
     * @return $this
     */
    public function setTypeUpdate()
    {
        $this->_type = self::TYPE_UPDATE;
        return $this;
    }

    /**
     * @return $this
     */
    public function setTypeDelete()
    {
        $this->_type = self::TYPE_DELETE;
        return $this;
    }

    /**
     * If type is null return TYPE_SELECT
     * @return string
     */
    public function getType()
    {
        return empty($this->_type) ? self::TYPE_SELECT : $this->_type;
    }

    public function setOptions($options = '')
    {
        $this->_queryOptions = $options;
        return $this;
    }

    public function distinct($distinct)
    {
        $this->_distinct = $distinct;
        return $this;
    }

    /**
     * @param Aggregation $aggregation
     * @param string $columnAlias
     * @return string
     */
    protected function buildSelectFromAggregation(Aggregation $aggregation)
    {
        $tableAlias = $this->getAlias();
        $rawColumns = $aggregation->getFields();
        $newSelect = $this->getLookupBuilder()->buildJoin($this, $rawColumns);
        if ($newSelect === false) {
            if (empty($tableAlias) || $rawColumns === '*') {
                $columns = $rawColumns;
            }
            elseif (strpos($rawColumns, '.') !== false) {
                $columns = $rawColumns;
            }
            else {
                $columns = $tableAlias . '.' . $rawColumns;
            }
        } else {
            list($alias, $joinColumn) = $newSelect;
            $columns = $alias . '.' . $joinColumn;
        }
        $fieldsSql = $this->getAdapter()->buildColumns($columns);
        $aggregation->setFieldsSql($fieldsSql);

        return $this->getAdapter()->quoteSql($aggregation->toSQL($this));
    }

    /**
     * @param $columns
     * @return array|string
     */
    public function buildColumnsqwe($columns)
    {
        if (!is_array($columns)) {
            if ($columns instanceof Aggregation) {
                $columns->setFieldsSql($this->buildColumns($columns->getFields()));
                return $this->quoteSql($columns->toSQL($this));
            } else if (strpos($columns, '(') !== false) {
                return $this->quoteSql($columns);
            } else {
                $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
            }
        }
        foreach ($columns as $i => $column) {
            if ($column instanceof Expression) {
                $columns[$i] = $this->quoteSql($column->toSQL());
            } else if (strpos($column, 'AS') !== false) {
                if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_\.]+)$/', $column, $matches)) {
                    list(, $rawColumn, $rawAlias) = $matches;
                    $columns[$i] = $this->quoteColumn($rawColumn) . ' AS ' . $this->quoteColumn($rawAlias);
                }
            } else if (strpos($column, '(') === false) {
                $columns[$i] = $this->quoteColumn($column);
            }
        }
        return is_array($columns) ? implode(', ', $columns) : $columns;
    }

    /**
     * @return string
     */
    public function buildSelect()
    {
        if (empty($this->_select)) {
            $this->_select = ['*'];
        }

        $builder = $this->getLookupBuilder();
        if (is_array($this->_select)) {
            $select = [];
            foreach ($this->_select as $alias => $column) {
                if ($column instanceof Aggregation) {
                    $select[$alias] = $this->buildSelectFromAggregation($column);
                } else if (is_string($column)) {
                    if (strpos($column, 'SELECT') !== false) {
                        $select[$alias] = $column;
                    } else {
                        $select[$alias] = $this->addColumnAlias($builder->fetchColumnName($column));
                    }
                } else {
                    $select[$alias] = $column;
                }
            }
        } else if (is_string($this->_select)) {
            $select = $this->addColumnAlias($this->_select);
        }
        return $this->getAdapter()->sqlSelect($select, $this->_distinct, $this->_queryOptions);
    }

    public function select($select, $distinct = null)
    {
        if ($distinct !== null) {
            $this->distinct($distinct);
        }

        if (empty($select)) {
            $this->_select = [];
            return $this;
        }

        $builder = $this->getLookupBuilder();
        $parts = [];
        if (is_array($select)) {
            foreach ($select as $key => $part) {
                if (is_string($part)) {
                    $newSelect = $builder->buildJoin($this, $part);
                    if ($newSelect) {
                        list($alias, $column) = $newSelect;
                        $parts[$key] = $alias . '.' . $column;
                    } else {
                        $parts[$key] = $part;
                    }
                } else {
                    $parts[$key] = $part;
                }
            }
        } else if (is_string($select)) {
            $newSelect = $builder->buildJoin($this, $select);
            if ($newSelect) {
                list($alias, $column) = $newSelect;
                $parts[$alias] = $column;
            } else {
                $parts[] = $select;
            }
        } else {
            $parts[] = $select;
        }
        $this->_select = $parts;
        return $this;
    }

    /**
     * @param $select array|string columns
     * @param $distinct array|string columns
     * @return $this
     */
    public function selectOld($select, $distinct = null)
    {
        if ($distinct !== null) {
            $this->distinct($distinct);
        }

        if (empty($select)) {
            $this->_select = [];
            return $this;
        }

        $tableAlias = $this->getAlias();
        $columns = [];
        $builder = $this->getLookupBuilder();
        if (is_array($select)) {
            foreach ($select as $columnAlias => $partSelect) {
                if ($partSelect instanceof Aggregation) {
                    $columns[$columnAlias] = $this->buildSelectFromAggregation($partSelect);
                } else if ($partSelect instanceof Expression) {
                    $columns[$columnAlias] = $this->getAdapter()->quoteSql($partSelect->toSQL());
                } else if (strpos($partSelect, 'SELECT') !== false) {
                    if (empty($columnAlias)) {
                        $columns[$columnAlias] = '(' . $partSelect . ')';
                    } else {
                        $columns[$columnAlias] = '(' . $partSelect . ') AS ' . $columnAlias;
                    }
                } else {
                    $newSelect = $builder->buildJoin($this, $partSelect);
                    if ($newSelect === false) {
                        $columns[$columnAlias] = empty($tableAlias) ? $partSelect : $tableAlias . '.' . $partSelect;
                        var_dump(empty($tableAlias) ? $partSelect : $tableAlias . '.' . $partSelect);
                    } else {
                        list($alias, $joinColumn) = $newSelect;
                        $columns[$columnAlias] = $alias . '.' . $joinColumn . ' AS ' . $partSelect;
                    }
                }
            }
        } else if ($select instanceof Aggregation) {
            $columns = $this->buildSelectFromAggregation($select);
        } else {
            $columns = $select;
        }
        $this->_select = $this->getAdapter()->buildColumns($columns);
        return $this;
    }

    /**
     * @param $tableName string
     * @return $this
     */
    public function from($tableName)
    {
        $this->_from = $tableName;
        return $this;
    }

    /**
     * @param $alias string join alias
     * @return bool
     */
    public function hasJoin($alias)
    {
        return array_key_exists($alias, $this->_join);
    }

    /**
     * @param int $page
     * @param int $pageSize
     * @return $this
     */
    public function paginate($page = 1, $pageSize = 10)
    {
        $this->limit($pageSize);
        $this->offset($page > 1 ? $pageSize * ($page - 1) : 0);
        return $this;
    }

    public function limit($limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    /**
     * @param $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->_offset = $offset;
        return $this;
    }

    /**
     * @return ILookupBuilder|\Tsukasa\QueryBuilder\LookupBuilder\LookupBuilder
     */
    public function getLookupBuilder()
    {
        return $this->lookupBuilder;
    }

    /**
     * @return BaseAdapter|ISQLGenerator
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param $joinType string LEFT JOIN, RIGHT JOIN, etc...
     * @param $tableName string
     * @param array $on link columns
     * @param string $alias string
     * @return $this
     * @throws Exception
     */
    public function join($joinType, $tableName = '', array $on = [], $alias = '')
    {
        if (is_string($joinType) && empty($tableName)) {
            $this->_join[] = $this->getAdapter()->quoteSql($joinType);
        } else if ($tableName instanceof QueryBuilder) {
            $this->_join[] = $this->getAdapter()->sqlJoin($joinType, $tableName, $on, $alias);
        } else {
            $this->_join[$alias] = $this->getAdapter()->sqlJoin($joinType, $tableName, $on, $alias);
            $this->_joinAlias[$tableName][] = $alias;
        }
        return $this;
    }

    /**
     * @param $sql
     * @param string $alias
     * @return $this
     */
    public function joinRaw($sql)
    {
        $this->_join[] = $this->getAdapter()->quoteSql($sql);
        return $this;
    }

    /**
     * @param array $columns columns
     * @return $this
     */
    public function group($columns)
    {
        if ($columns) {
            if (is_string($columns)) {
                $columns = array_map(function($item){ return trim($item);}, explode(',', $columns));
            }
        }

        $this->_group = $columns;
        return $this;
    }

    /**
     * @param array|string $columns columns
     * @param null $options
     * @return $this
     */
    public function order($columns, $options = null)
    {
        $this->_order = $columns;
        $this->_orderOptions = $options;
        return $this;
    }

    /**
     * Clear properties
     * @return $this
     */
    public function clear()
    {
        $this->_whereAnd = [];
        $this->_whereOr = [];
        $this->_join = [];
        $this->_insert = [];
        $this->_update = [];
        $this->_group = [];
        $this->_order = [];
        $this->_select = [];
        $this->_from = '';
        $this->_union = [];
        $this->_having = [];
        return $this;
    }

    /**
     * @param $tableName
     * @param array $rows
     * @return $this
     */
    public function insert($tableName, $rows)
    {
        return $this->getAdapter()->generateInsertSQL($tableName, $rows, $this->_queryOptions);
    }

    /**
     * @param $tableName string
     * @param array $values columns [name => value...]
     * @return $this
     */
    public function update($tableName, array $values)
    {
        $this->_update = [$tableName, $values];
        return $this;
    }

    public function raw($sql)
    {
        return $this->getAdapter()->quoteSql($sql);
    }

    public function getAlias()
    {
        return $this->_alias;
    }

    public function setAlias($alias)
    {
        $this->_alias = $alias;
        return $this;
    }

    public function buildCondition($condition, &$params = [])
    {
        if (!is_array($condition)) {
            return (string)$condition;
        }

        if (empty($condition)) {
            return '';
        }

        if (isset($condition[0]) && is_string($condition[0])) {
            $operatorRaw = array_shift($condition);
            $operator = strtoupper($operatorRaw);
            return $this->buildAndCondition($operator, $condition, $params);
        }

        return $this->parseCondition($condition);
    }

    public function getJoinAlias($tableName)
    {
        return $this->_joinAlias[$tableName];
    }

    public function getJoins()
    {
        return $this->_join;
    }

    /**
     * @param $condition
     * @return string
     */
    protected function parseCondition($condition)
    {
        $tableAlias = $this->getAlias();
        $parts = [];

        if ($condition instanceof Expression) {
            $parts[] = $this->getAdapter()->quoteSql($condition->toSQL());
        }
        else if ($condition instanceof Q) {
            $condition->setLookupBuilder($this->getLookupBuilder());
            $condition->setAdapter($this->getAdapter());
            $condition->setTableAlias($tableAlias);
            $parts[] = $condition->toSQL($this);
        }
        else if ($condition instanceof QueryBuilder) {
            $parts[] = $condition->toSQL();
        }
        else if (is_array($condition)) {
            foreach ($condition as $key => $value)
            {
                if (is_numeric($key) && ($value instanceof Expression)) {
                    $parts[] = $this->parseCondition($value);
                }
                else if (is_numeric($key) && ($value instanceof QueryBuilder)) {
                    $parts[] = $this->parseCondition($value);
                }
                else if ($value instanceof Q) {
                    $parts[] = $this->parseCondition($value);
                }
                else {
                    $value = $this->getAdapter()->prepareValue($value);

                    list($lookup, $column, $lookupValue) = $this->lookupBuilder->parseLookup($this, $key, $value);
                    $column = $this->getLookupBuilder()->fetchColumnName($column);
                    if (empty($tableAlias) === false && strpos($column, '.') === false) {
                        $column = $tableAlias . '.' . $column;
                    }
                    $parts[] = $this->lookupBuilder->runLookup($this->getAdapter(), $lookup, $column, $lookupValue);
                }
            }
        }
        else if (is_string($condition)) {
            $parts[] = $condition;
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        return '(' . implode(') AND (', $parts) . ')';
    }

    public function buildAndCondition($operator, $operands, &$params)
    {
        $parts = [];
        foreach ($operands as $operand) {
            if (is_array($operand)) {
                $operand = $this->buildCondition($operand, $params);
            } else {
                $operand = $this->parseCondition($operand);
            }
            if ($operand !== '') {
                $parts[] = $this->getAdapter()->quoteSql($operand);
            }
        }
        if (!empty($parts)) {
            return '(' . implode(') ' . $operator . ' (', $parts) . ')';
        }

        return '';
    }

    /**
     * @param $condition
     * @return $this
     */
    public function where($condition)
    {
        if (!empty($condition)) {
            $this->_whereAnd[] = $condition;
        }
        return $this;
    }

    /**
     * @param $condition
     * @return $this
     */
    public function orWhere($condition)
    {
        $this->_whereOr[] = $condition;
        return $this;
    }

    /**
     * @return array
     */
    public function buildWhereTree()
    {
        $where = [];
        foreach ($this->_whereAnd as $condition) {
            if (empty($where)) {
                $where = ['and', $condition];
            } else {
                $where = ['and', $where, ['and', $condition]];
            }
        }

        foreach ($this->_whereOr as $condition) {
            if (empty($where)) {
                $where = ['or', $condition];
            } else {
                $where = ['or', $where, ['and', $condition]];
            }
        }
        return $where;
    }

    public function getSelect()
    {
        return $this->_select;
    }

    public function buildWhere()
    {
        $params = [];
        $sql = $this->buildCondition($this->buildWhereTree(), $params);
        return empty($sql) ? '' : ' WHERE ' . $sql;
    }

//    protected function prepareJoin()
//    {
//        $builder = $this->getLookupBuilder();
//        if (is_array($this->_select)) {
//            foreach ($this->_select as $select) {
//                if (strpos($select, '__') > 0) {
//                    $builder->buildJoin($select);
//                }
//            }
//        } else {
//            if (strpos($this->_select, '__') > 0) {
//                $builder->buildJoin($this->_select);
//            }
//        }
//
//        foreach ($this->_order as $order) {
//            $builder->buildJoin($order);
//        }
//
//        foreach ($this->_group as $group) {
//            $builder->buildJoin($group);
//        }
//    }

    private function generateSelectSql()
    {
        // Fetch where conditions before pass it to adapter.
        // Reason: Dynamic sql build in callbacks

        // $this->prepareJoin();

        $where = $this->buildWhere();
        $order = $this->buildOrder();
        $union = $this->buildUnion();

        /*
        $hasAggregation = false;
        if (is_array($this->_select)) {
            foreach ($this->_select as $key => $value) {
                if ($value instanceof Aggregation) {

                }
            }
        } else {
            $hasAggregation = $this->_select instanceof Aggregation;
        }
        */

        $select = $this->buildSelect();
        $from = $this->buildFrom();
        $join = $this->buildJoin();
        $group = $this->buildGroup();
        $having = $this->buildHaving();
        $limitOffset = $this->buildLimitOffset();
        return strtr('{select}{from}{join}{where}{group}{having}{order}{limit_offset}{union}', [
            '{select}' => $select,
            '{from}' => $from,
            '{where}' => $where,
            '{group}' => $group,
            '{order}' => empty($union) ? $order : '',
            '{having}' => $having,
            '{join}' => $join,
            '{limit_offset}' => $limitOffset,
            '{union}' => empty($union) ? '' : $union . $order
        ]);
    }

    public function generateDeleteSql()
    {
        $limitOffset = $this->buildLimitOffset();
        return strtr('{delete}{from}{where}{limit_offset}', [
            '{delete}' => 'DELETE ' . $this->_queryOptions,
            '{from}' => $this->buildFrom(),
            '{where}' => $this->buildWhere(),
            '{limit_offset}' => $limitOffset,
        ]);
    }

    public function generateUpdateSql()
    {
        list($tableName, $values) = $this->_update;
        $this->setAlias(null);
        return strtr('{update}{where}', [
            '{update}' => $this->getAdapter()->sqlUpdate($tableName, $values, $this->_queryOptions),
            '{where}' => $this->buildWhere(),
        ]);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function toSQL()
    {
        $type = $this->getType();
        if ($type === self::TYPE_SELECT) {
            return $this->generateSelectSql();
        }

        if ($type === self::TYPE_UPDATE) {
            return $this->generateUpdateSql();
        }

        if ($type === self::TYPE_DELETE) {
            return $this->generateDeleteSql();
        }

        throw new QBException('Unknown query type');
    }

    public function buildHaving()
    {
        return $this->getAdapter()->sqlHaving($this->_having, $this);
    }

    public function buildLimitOffset()
    {
        return $this->getAdapter()->sqlLimitOffset($this->_limit, $this->_offset);
    }

    public function buildUnion()
    {
        $sql = '';
        foreach ($this->_union as  list($union, $all)) {
            $sql .= ' ' . $this->getAdapter()->sqlUnion($union, $all);
        }

        return empty($sql) ? '' : $sql;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param $tableName
     * @param $columns
     * @param null $options
     * @param bool $ifNotExists
     * @return string
     */
    public function createTable($tableName, $columns, $options = null, $ifNotExists = false)
    {
        return $this->getAdapter()->sqlCreateTable($tableName, $columns, $options, $ifNotExists);
    }

    /**
     * @param array|string|Q $where lookups
     * @return $this
     */
    public function having($having)
    {
        if (($having instanceof Q) == false) {
            $having = new QAnd($having);
        }
        $having->setLookupBuilder($this->getLookupBuilder());
        $having->setAdapter($this->getAdapter());
        $this->_having = $having;
        return $this;
    }

    public function union($union, $all = false)
    {
        $this->_union[] = [$union, $all];
        return $this;
    }

    /**
     * @param $tableName
     * @param $name
     * @param $columns
     * @return string
     */
    public function addPrimaryKey($tableName, $name, $columns)
    {
        return $this->getAdapter()->sqlAddPrimaryKey($tableName, $name, $columns);
    }

    /**
     * @param $tableName
     * @param $column
     * @param $type
     * @return string
     */
    public function alterColumn($tableName, $column, $type)
    {
        return $this->getAdapter()->sqlAlterColumn($tableName, $column, $type);
    }

    /**
     * Makes alias for joined table
     * @param $table
     * @param bool $increment
     * @return string
     */
    public function makeAliasKey($table, $increment = false)
    {
        if ($increment) {
            ++$this->_aliasesCount;
        }

        $tableName = $this->getAdapter()->getRawTableName($table);

        if (strpos($tableName, '.') !== false) {
            $tableName = substr($tableName, strpos($tableName, '.'));
        }

        return strtr('{table}_{count}', [
            '{table}' => $tableName,
            '{count}' => $this->_aliasesCount + 1
        ]);
    }

    /**
     * @param string $table
     * @param string $code
     * @param string $topAlias
     *
     * @return string
     */
    public function makeMappedAliasKey($table, $code, $topAlias = null)
    {
        $key = $topAlias . '_' . $code;

        if (empty($this->_joinAlias[$table])) {
            $this->_joinAlias[$table]['__alias_count__'] = 1;
        }


        if (!empty($this->_joinAlias[$table][$key])) {
            return $this->_joinAlias[$table][$key];
        }

        $this->_joinAlias[$table][$key] = strtr('{table}_{count}', [
            '{table}' => $this->getAdapter()->getRawTableName($table),
            '{count}' => $this->_joinAlias[$table]['__alias_count__'] += 1
        ]);

        return $this->_joinAlias[$table][$key];
    }

    public function getJoin($tableName)
    {
        return $this->_join[$tableName];
    }

    /**
     * @param $column
     * @return string
     */
    protected function addColumnAlias($column)
    {
        $tableAlias = $this->getAlias();
        if (empty($tableAlias)) {
            return $column;
        }

        if (strpos($column, '.') === false &&
            strpos($column, '(') === false &&
            strpos($column, 'SELECT') === false
        ) {
            return $tableAlias . '.' . $column;
        }

        return $column;
    }

    protected function hasAliasedField($column)
    {
        foreach ($this->_select as $alias => $item)
        {
            if (!is_numeric($alias)) {
                if ($column == $alias) {
                    return true;
                }
            }

            if ($item instanceof Aggregation) {
                if ($column == $item->getAlias())
                {
                    return true;
                }
            }
        }

        return false;
    }

    protected function applyTableAlias($column)
    {
        // If column already has alias - skip
        if (strpos($column, '.') === false)
        {
            if (!$this->hasAliasedField($column))
            {
                $tableAlias = $this->getAlias();
                return empty($tableAlias) ? $column : $tableAlias . '.' . $column;
            }
        }

        return $column;
    }

    public function buildJoin()
    {
        if (empty($this->_join)) {
            return '';
        }
        $join = [];
        foreach ($this->_join as $part) {
            $join[] = $part;
        }
        return ' ' . implode(' ', $join);
    }


    /**
     * @param $order
     * @return array
     */
    protected function buildOrderJoin($order)
    {
        if (strpos($order, '-', 0) === false) {
            $direction = 'ASC';
        } else {
            $direction = 'DESC';
            $order = substr($order, 1);
        }
        $order = $this->getLookupBuilder()->fetchColumnName($order);
        $newOrder = $this->getLookupBuilder()->buildJoin($this, $order);
        if ($newOrder === false) {
            return [$order, $direction];
        }

        list($alias, $column) = $newOrder;
        return [$alias . '.' . $column, $direction];
    }

    public function getOrder()
    {
        return [$this->_order, $this->_orderOptions];
    }

    public function buildOrder()
    {
        /**
         * не делать проверку по empty(), проваливается половина тестов с ORDER BY
         * и проваливается тест с построением JOIN по lookup
         */
        if ($this->_order === null) {
            return '';
        }

        $order = [];
        if (is_array($this->_order)) {
            foreach ($this->_order as $column) {
                if ($column instanceof Expression) {
                    $order[$column->toSQL($this)] = '';
                }
                else if ($column === '?') {
                    $order[] = $this->getAdapter()->getRandomOrder();
                } else {
                    list($newColumn, $direction) = $this->buildOrderJoin($column);
                    $order[$this->applyTableAlias($newColumn)] = $direction;
                }
            }
        } else if (is_string($this->_order)) {
            $columns = preg_split('/\s*,\s*/', $this->_order, -1, PREG_SPLIT_NO_EMPTY);
            $order = array_map(function ($column) {
                $temp = explode(' ', $column);
                if (count($temp) == 2) {
                    return $this->getAdapter()->quoteColumn($temp[0]) . ' ' . $temp[1];
                }

                return $this->getAdapter()->quoteColumn($column);
            }, $columns);
            $order = implode(', ', $order);
        } else {
            $order = $this->buildOrderJoin($this->_order);
        }

        $sql = $this->getAdapter()->sqlOrderBy($order, $this->_orderOptions);
        return empty($sql) ? '' : ' ORDER BY ' . $sql;
    }

    /**
     * @param $group
     * @return string
     */
    protected function buildGroupJoin($group)
    {
        if (strpos($group, '.') === false) {
            $newGroup = $this->getLookupBuilder()->fetchColumnName($group);
            $newGroup = $this->getLookupBuilder()->buildJoin($this, $newGroup);

            if ($newGroup === false) {
                return $group;
            }

            list($alias, $column) = $newGroup;
            return $alias . '.' . $column;
        }

        return $group;
    }

    public function buildGroup()
    {
        $group = [];
        if ($this->_group) {
            foreach ($this->_group as $key => $column) {
                $newColumn = $this->buildGroupJoin($column);
                $group[] = $this->applyTableAlias($newColumn);
            }
        }

        $sql = $this->getAdapter()->sqlGroupBy($group);
        return empty($sql) ? '' : ' GROUP BY ' . $sql;
    }

    public function buildFrom()
    {
        if (!empty($this->_alias) && !is_array($this->_from)) {
            $from = [$this->_alias => $this->_from];
        } else {
            $from = $this->_from;
        }
        $sql = $this->getAdapter()->sqlFrom($from);
        return empty($sql) ? '' : ' FROM ' . $sql;
    }
}
