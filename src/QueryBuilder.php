<?php

namespace Tsukasa\QueryBuilder;

use Doctrine\DBAL\Driver\Connection;
use Tsukasa\QueryBuilder\Aggregation\Aggregation;
use Tsukasa\QueryBuilder\Exception\QBException;
use Tsukasa\QueryBuilder\Expression\Expression;
use Tsukasa\QueryBuilder\Interfaces\IAdapter;
use Tsukasa\QueryBuilder\Interfaces\ILookupBuilder;
use Tsukasa\QueryBuilder\Interfaces\ILookupCollection;
use Tsukasa\QueryBuilder\Interfaces\IToSql;
use Tsukasa\QueryBuilder\LookupBuilder\LookupBuilder;
use Tsukasa\QueryBuilder\Q\Q;

use Tsukasa\QueryBuilder\Database\Mysql\Adapter as MysqlAdapter;
use Tsukasa\QueryBuilder\Database\Sqlite\Adapter as SqliteAdapter;
use Tsukasa\QueryBuilder\Database\Pgsql\Adapter as PgsqlAdapter;

class QueryBuilder implements QueryBuilderInterface
{
    const TYPE_SELECT = 'SELECT';
    const TYPE_INSERT = 'INSERT';
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
    /**
     * @var array|string
     */
    private $_order = [];
    /**
     * @var array
     */
    private $_group = [];
    /**
     * @var array|string|\Tsukasa\QueryBuilder\Aggregation\Aggregation
     */
    private $_select = [];
    /**
     * @var array|string|null
     */
    private $_from;
    /**
     * @var array
     */
    private $_union = [];
    /**
     * @var null|int
     */
    private $_limit;
    /**
     * @var null|int
     */
    private $_offset;
    /**
     * @var array
     */
    private $_having = [];
    /**
     * @var null|string
     */
    private $_alias;
    /**
     * @var null|string sql query type SELECT|UPDATE|DELETE
     */
    private $_type;
    /**
     * @var array
     */
    private $_update = [];
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
     * @param Connection $connection
     * @param BaseAdapter|null $adapter
     * @param LookupBuilder|null $lookupBuilder
     * @return QueryBuilderInterface
     */
    public static function getInstance(Connection $connection, $adapter = null, $lookupBuilder = null)
    {
        if ($adapter === null) {
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
                    throw new QBException('Unknown driver');
            }
        }

        $lookupBuilder = $lookupBuilder ?: new LookupBuilder();
        $lookupBuilder->addLookupCollection($adapter->getLookupCollection());
        return new static($connection, $adapter, $lookupBuilder);
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
     * @return QueryBuilderInterface
     */
    public function addLookupCollection(ILookupCollection $lookupCollection)
    {
        $this->lookupBuilder->addLookupCollection($lookupCollection);
        return $this;
    }

    public function setType($type)
    {
        $types = [static::TYPE_INSERT, static::TYPE_UPDATE, static::TYPE_DELETE, static::TYPE_SELECT];
        if (in_array($type, $types, true)) {
            $this->_type = $type;
        } else {
            throw new QBException('Incorrect type');
        }


        return $this;
    }

    /**
     * @return QueryBuilderInterface
     */
    public function setTypeSelect()
    {
        $this->_type = self::TYPE_SELECT;
        return $this;
    }

    /**
     * @return QueryBuilderInterface
     */
    public function setTypeInsert()
    {
        $this->_type = self::TYPE_INSERT;
        return $this;
    }

    /**
     * @return QueryBuilderInterface
     */
    public function setTypeUpdate()
    {
        $this->_type = self::TYPE_UPDATE;
        return $this;
    }

    /**
     * @return QueryBuilderInterface
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
        return $this->_type === null
            ? self::TYPE_SELECT
            : $this->_type;
    }

    public function setOptions($options = '')
    {
        $this->_queryOptions = $options;
        return $this;
    }

    /**
     * @param Aggregation $aggregation
     * @return string
     */
    protected function buildSelectFromAggregation(Aggregation $aggregation)
    {
        $tableAlias = $this->getAlias();
        $rawColumn = $aggregation->getField();
        $newSelect = $this->getLookupBuilder()->buildJoin($this, $rawColumn);
        if ($newSelect === false) {
            if ($tableAlias === null || $rawColumn === '*') {
                $columns = $rawColumn;
            } elseif (strpos($rawColumn, '.') !== false) {
                $columns = $rawColumn;
            } else {
                $columns = $tableAlias . '.' . $rawColumn;
            }
        } else {
            list($alias, $joinColumn) = $newSelect;
            $columns = $alias . '.' . $joinColumn;
        }
        $fieldsSql = $this->getAdapter()->buildColumns($columns);
        $aggregation->setFieldSql($fieldsSql);

        return $aggregation->setQB($this)->toSQL();
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function buildSelect()
    {
        if (empty($this->_select)) {
            $this->_select = ['*'];
        }

        $select = [];
        $builder = $this->getLookupBuilder();
        if (is_array($this->_select)) {
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
        return $this->getAdapter()->sqlSelect($select, $this->_queryOptions);
    }

    protected function pushToSelect($select, $alias = null)
    {
        $isValid = is_string($select)
            || (is_numeric($select) && is_finite($select))
            || is_a($select, Expression::class)
            || is_a($select, Aggregation::class)
        ;

        if (!$isValid) {
            throw new QBException('Incorrect select type');
        }

        if ($alias) {
            $this->_select[$alias] = $select;
        } else {
            $this->_select[] = $select;
        }

        return $this;
    }

    /**
     * @param string|IToSql $select
     * @param null $alias
     * @return QueryBuilderInterface
     */
    public function addSelect($select, $alias = null)
    {
        if (is_string($select) && $newSelect = $this->getLookupBuilder()->buildJoin($this, $select)) {
            list($t_alias, $column) = $newSelect;
            $this->pushToSelect($t_alias . '.' . $column, $alias);
        } else if ($select instanceof IToSql) {
            $this->pushToSelect($select->setQb($this), $alias);
        } else {
            $this->pushToSelect($select, $alias);
        }

        return $this;
    }

    /**
     * @param array|string $select
     * @return QueryBuilderInterface
     */
    public function setSelect($select)
    {
        $this->_select = [];

        if (empty($select)) {
            return $this;
        }

        if (is_array($select)) {
            foreach ($select as $key => $part) {
                $this->addSelect($part, $key);
            }
        } else {
            $this->addSelect($select);
        }

        return $this;
    }

    /**
     * @param array|string $tableName
     * @param null|string $alias
     * @return QueryBuilderInterface
     */
    public function setFrom($tableName, $alias = null)
    {
        if ($alias && is_string($alias)) {
            if (is_array($tableName)) {
                $tableName = current($tableName);
            }

            $tableName = [$alias => $tableName];
        }

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
     * @return QueryBuilderInterface
     */
    public function paginate($page = 1, $pageSize = 10)
    {
        $page = (int)$page;
        $pageSize = (int)$pageSize;

        $this->setLimit($pageSize);
        $this->setOffset($page > 1 ? $pageSize * ($page - 1) : 0);
        return $this;
    }

    /**
     * @param string|number $limit
     * @return QueryBuilderInterface
     */
    public function setLimit($limit)
    {
        $this->_limit = (int)$limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->_limit;
    }

    /**
     * @param string|number $offset
     * @return QueryBuilderInterface
     */
    public function setOffset($offset)
    {
        $this->_offset = (int)$offset;
        return $this;
    }

    /**
     * @return int|string|null
     */
    public function getOffset()
    {
        return $this->_offset;
    }

    /**
     * @return ILookupBuilder|\Tsukasa\QueryBuilder\LookupBuilder\LookupBuilder
     */
    public function getLookupBuilder()
    {
        return $this->lookupBuilder;
    }

    /**
     * @return BaseAdapter|IAdapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param string $joinType LEFT JOIN, RIGHT JOIN, etc...
     * @param string|QueryBuilderInterface $tableName
     * @param array $on link columns
     * @param string|null $alias string
     * @param string|null $index
     * @return QueryBuilderInterface
     */
    public function join($joinType, $tableName, array $on = [], $alias = null, $index = null)
    {
        if ($tableName instanceof QueryBuilder) {
            $this->_join[] = $this->getAdapter()->sqlJoin($joinType, $tableName, $on, $alias, $index);
        } else {
            if ($joinType === 'RAW' && !empty($tableName)) {
                $join = $this->getAdapter()->quoteSql($tableName);
            } else {
                $join = $this->getAdapter()->sqlJoin($joinType, $tableName, $on, $alias);
            }

            if (!$alias) {
                $alias = count($this->_join);
            }
            $this->_join[$alias] = $join;
            $this->_joinAlias[$tableName][] = $alias;
        }
        return $this;
    }

    /**
     * @param $sql
     * @param string $alias
     * @return QueryBuilderInterface
     */
    public function joinRaw($sql, $alias = null)
    {
        return $this->join('RAW', $sql, [], $alias);
    }

    /**
     * @param array|string $columns columns
     * @return QueryBuilderInterface
     */
    public function setGroup($columns)
    {
        if ($columns && is_string($columns)) {
            $columns = array_map('trim', explode(',', $columns));
        }

        $this->_group = $columns;
        return $this;
    }

    /**
     * @param array|string $columns columns
     * @return QueryBuilderInterface
     */
    public function addGroup($columns)
    {
        if ($columns && is_string($columns)) {
            $columns = array_map('trim', explode(',', $columns));
        }
        $this->_group = array_merge($this->_group, $columns);
        return $this;
    }

    protected function pushOrder($column)
    {

    }

    /**
     * @param array|string|null $columns columns
     * @return QueryBuilderInterface
     */
    public function setOrder($columns)
    {

        $this->_order = [];

        if (empty($columns)) {
            return $this;
        }

        if (is_array($columns)) {
            foreach ($columns as $column) {
                $this->addOrder($column);
            }
        } else {
            $this->addOrder($columns);
        }

        return $this;
    }

    /**
     * @param string|Expression $column
     * @return QueryBuilderInterface
     */
    public function addOrder($column)
    {
        $isValid = is_string($column)
            || is_a($column, Expression::class)
        ;

        if (!$isValid) {
            throw new QBException('Incorrect order type');
        }

        if (is_string($column) && strpos($column, ',') !== false) {
            $columns = preg_split('/\s*,\s*/', $column, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($columns as $_column) {
                $temp = explode(' ', $_column);
                if (count($temp) === 2) {

                    $_column = $temp[0];
                    if (strtoupper(trim($temp[1])) === 'DESC') {
                        $_column = '-' . $_column;
                    }

                    $this->_order[] = $_column;
                } else {
                    $this->_order[] = current($temp);
                }
            }
        } else {
            $this->_order[] = $column;
        }

        return $this;
    }

    /**
     * @param $tableName
     * @param array $rows
     * @return QueryBuilderInterface
     */
    public function insert($tableName, $rows)
    {
        $this->setTypeInsert();
        $this->_update = [$tableName, $rows];
        return $this;
    }

    /**
     * @param $tableName string
     * @param array $values columns [name => value...]
     * @return QueryBuilderInterface
     */
    public function update($tableName, array $values)
    {
        $this->setTypeUpdate();
        $this->_update = [$tableName, $values];
        return $this;
    }

    public function getAlias()
    {
        return $this->_alias;
    }

    public function setAlias($alias = null)
    {
        if (empty($alias)) {
            $alias = null;
        }

        $this->_alias = $alias;
        return $this;
    }

    protected function buildCondition($condition, &$params = [])
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
     * @param string $operator
     * @return string
     */
    public function parseCondition($condition, $operator = 'AND')
    {
        $parts = [];

        if (is_array($condition)) {
            foreach ($condition as $key => $value) {
                if (is_numeric($key)) {
                    if ($value instanceof IToSql) {
                        $parts[] = $this->parseCondition($value, $operator);
                    } elseif ($value instanceof QueryBuilder) {
                        $parts[] = $this->parseCondition($value, $operator);
                    } else if (is_array($value)) {
                        $parts[] = $this->parseCondition($value, $operator);
                    } else if (is_string($value)) {
                        $parts[] = $value;
                    }
                } else {
                    $tableAlias = $this->getAlias();
                    $value = $this->getAdapter()->prepareValue($value);

                    list($lookup, $column, $lookupValue) = $this->lookupBuilder->parseLookup($this, $key, $value);
                    $column = $this->getLookupBuilder()->fetchColumnName($column);
                    if ($tableAlias !== null && strpos($column, '.') === false) {
                        $column = $tableAlias . '.' . $column;
                    }
                    $parts[] = $this->lookupBuilder->runLookup($this->getAdapter(), $lookup, $column, $lookupValue);
                }
            }

            if ($parts) {
                if (count($parts) === 1) {
                    return $parts[0];
                }

                return '(' . implode(') ' . $operator . ' (', $parts) . ')';
            }

        } else if ($condition instanceof IToSql) {
            return $condition
                ->setQb($this)
                ->toSql();
        } else if ($condition instanceof QueryBuilder) {
            return $condition->toSQL();
        } else if (is_string($condition)) {
            return $condition;
        }

        return '';
    }

    protected function buildAndCondition($operator, $operands, &$params)
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
     * @return QueryBuilderInterface
     */
    public function addWhere($condition)
    {
        if (!empty($condition)) {
            $this->_whereAnd[] = $condition;
        }
        return $this;
    }

    public function setWhere($condition)
    {
        $this->_whereAnd = [];

        return $this->addWhere($condition);
    }

    /**
     * @param $condition
     * @return QueryBuilderInterface
     */
    public function addOrWhere($condition)
    {
        if (!empty($condition)) {
            $this->_whereOr[] = $condition;
        }
        return $this;
    }

    public function setOrWhere($condition)
    {
        $this->_whereOr = [];

        return $this->addWhere($condition);
    }

    /**
     * @return array
     */
    protected function buildWhereTree()
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

    protected function buildWhere()
    {
        $params = [];
        $sql = $this->buildCondition($this->buildWhereTree(), $params);
        return empty($sql) ? '' : ' WHERE ' . $sql;
    }


    protected function generateSelectSql()
    {
        // Fetch where conditions before pass it to adapter.
        // Reason: Dynamic sql build in callbacks

        // $this->prepareJoin();

        $where = $this->buildWhere();
        $order = $this->buildOrder();
        $union = $this->buildUnion();

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
        $options = $this->_queryOptions ;
        if ($options) {
            $options = " {$options} ";
        }

        $limitOffset = $this->buildLimitOffset();
        return strtr('{delete}{options}{from}{where}{limit_offset}', [
            '{delete}' => 'DELETE',
            '{options}' => $options,
            '{from}' => $this->buildFrom(),
            '{where}' => $this->buildWhere(),
            '{limit_offset}' => $limitOffset,
        ]);
    }

    public function generateInsertSql()
    {
        list($tableName, $values) = $this->_update;
        $this->setAlias();
        return $this->getAdapter()->sqlInsert($tableName, $values, $this->_queryOptions);
    }

    public function generateUpdateSql()
    {
        list($tableName, $values) = $this->_update;
        $this->setAlias();
        return strtr('{update}{where}', [
            '{update}' => $this->getAdapter()->sqlUpdate($tableName, $values, $this->_queryOptions),
            '{where}' => $this->buildWhere(),
        ]);
    }

    /**
     * @return string
     * @throws QBException
     */
    public function toSQL()
    {
        switch ($this->getType())
        {
            case self::TYPE_SELECT:
                return $this->generateSelectSql();

            case self::TYPE_INSERT:
                return $this->generateInsertSql();

            case self::TYPE_UPDATE:
                return $this->generateUpdateSql();

            case self::TYPE_DELETE:
                return $this->generateDeleteSql();
        }

        throw new QBException('Unknown query type');
    }

    protected function buildHaving()
    {
        return $this->getAdapter()->sqlHaving(
            $this->parseCondition($this->_having),
            $this
        );
    }

    protected function buildLimitOffset()
    {
        return $this->getAdapter()->sqlLimitOffset(
            $this->_limit,
            $this->_offset
        );
    }

    protected function buildUnion()
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
        return $this->getAdapter()->sqlCreateTable(
            $tableName,
            $columns,
            $options,
            $ifNotExists
        );
    }

    /**
     * @param array|string|Q $having lookups
     * @return QueryBuilderInterface
     */
    public function setHaving($having)
    {
        $this->_having = [];

        return $this->addHaving($having);
    }

    public function addHaving($having)
    {
        if (!empty($having)) {
            $this->_having[] = $having;
        }

        return $this;
    }

    public function addUnion($union, $all = false)
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
            $tableName = substr($tableName, strpos($tableName, '.')+1);
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
        if ($tableAlias === null) {
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
            if (!is_numeric($alias) && $column === $alias) {
                return true;
            }
        }

        return false;
    }

    protected function applyTableAlias($column)
    {
        // If column already has alias - skip
        if ((strpos($column, '.') === false) && !$this->hasAliasedField($column)) {
            $tableAlias = $this->getAlias();
            return $tableAlias === null ? $column : $tableAlias . '.' . $column;
        }

        return $column;
    }

    protected function buildJoin()
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
        if (strpos($order, '-') === false) {
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
        return $this->_order;
    }

    protected function buildOrder()
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
                    $order[$column->toSQL()] = '';
                }
                else if ($column === '?') {
                    $order[] = $this->getAdapter()->getRandomOrder();
                } else {
                    list($newColumn, $direction) = $this->buildOrderJoin($column);
                    $order[$this->applyTableAlias($newColumn)] = $direction;
                }
            }
        } else {
            $order[] = $this->buildOrderJoin($this->_order);
        }

        $sql = $this->getAdapter()->sqlOrderBy($order);
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

    protected function buildGroup()
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

    protected function buildFrom()
    {
        if ($this->_alias !== null && !is_array($this->_from)) {
            $from = [$this->_alias => $this->_from];
        } else {
            $from = $this->_from;
        }
        $sql = $this->getAdapter()->sqlFrom($from);
        return empty($sql) ? '' : ' FROM ' . $sql;
    }
}
