<?php
/**
 * QueryBuilder
 * User: m.korobitsyn
 * Date: 22.01.19 14:39
 */

namespace Tsukasa\QueryBuilder\Interfaces;

use Tsukasa\QueryBuilder\BaseAdapter;
use Tsukasa\QueryBuilder\Exception\QBException;
use Tsukasa\QueryBuilder\Expression\Expression;
use Tsukasa\QueryBuilder\Q\Q;

interface QueryBuilderInterface
{
    public function getConnection();

    /**
     * @param  $connection \Doctrine\DBAL\Connection
     */
    public function setConnection($connection);

    /**
     * @param ILookupCollection $lookupCollection
     * @return $this
     */
    public function addLookupCollection(ILookupCollection $lookupCollection);

    public function setType($type);

    /**
     * @return $this
     */
    public function setTypeSelect();

    /**
     * @return $this
     */
    public function setTypeInsert();

    /**
     * @return $this
     */
    public function setTypeUpdate();

    /**
     * @return $this
     */
    public function setTypeDelete();

    /**
     * If type is null return TYPE_SELECT
     * @return string
     */
    public function getType();

    public function setOptions($options = '');

    /**
     * @param string|IToSql $select
     * @param null|string $alias
     * @return $this
     */
    public function addSelect($select, $alias = null);

    /**
     * @param array|string $select
     * @return $this
     */
    public function setSelect($select);

    /**
     * @param array|string $tableName
     * @param null|string $alias
     * @return $this
     */
    public function setFrom($tableName, $alias = null);

    /**
     * @param $alias string join alias
     * @return bool
     */
    public function hasJoin($alias);

    /**
     * @param int $page
     * @param int $pageSize
     * @return $this
     */
    public function paginate($page = 1, $pageSize = 10);

    /**
     * @param string|number $limit
     * @return $this
     */
    public function setLimit($limit);

    /**
     * @return int
     */
    public function getLimit();

    /**
     * @param string|number $offset
     * @return $this
     */
    public function setOffset($offset);

    /**
     * @return int|string|null
     */
    public function getOffset();

    /**
     * @return ILookupBuilder|\Tsukasa\QueryBuilder\LookupBuilder\LookupBuilder
     */
    public function getLookupBuilder();

    /**
     * @return ISQLGenerator|IAdapter
     */
    public function getAdapter();

    /**
     * @param string $joinType LEFT JOIN, RIGHT JOIN, etc...
     * @param string|QueryBuilderInterface $tableName
     * @param array $on link columns
     * @param string|null $alias string
     * @param string|null $index
     * @return $this
     */
    public function join($joinType, $tableName, array $on = [], $alias = null, $index = null);

    /**
     * @param $sql
     * @param string $alias
     * @return $this
     */
    public function joinRaw($sql, $alias = null);

    /**
     * @param array|string $columns columns
     * @return $this
     */
    public function setGroup($columns);

    /**
     * @param array|string $columns columns
     * @return $this
     */
    public function addGroup($columns);

    /**
     * @param array|string|null $columns columns
     * @return static
     */
    public function setOrder($columns);

    /**
     * @param string|Expression $column
     * @return static
     */
    public function addOrder($column);

    public function getOrder();

    /**
     * @param $tableName
     * @param array $rows
     * @return $this
     */
    public function insert($tableName, $rows);

    /**
     * @param $tableName string
     * @param array $values columns [name => value...]
     * @return $this
     */
    public function update($tableName, array $values);

    public function getAlias();

    public function setAlias($alias = null);

    public function getJoinAlias($tableName);

    public function getJoins();


    /**
     * @param $condition
     * @return $this
     */
    public function addWhere($condition);

    public function setWhere($condition);

    /**
     * @param $condition
     * @return $this
     */
    public function addOrWhere($condition);

    public function setOrWhere($condition);

    public function getSelect();

    /**
     * @return string
     * @throws QBException
     */
    public function toSQL();

    /**
     * @param array|string|Q $having lookups
     * @return $this
     */
    public function setHaving($having);

    public function addHaving($having);

    public function addUnion($union, $all = false);

    public function setUnions(array $unions, $all = false);

    /**
     * Makes alias for joined table
     * @param $table
     * @param bool $increment
     * @return string
     */
    public function makeAliasKey($table, $increment = false);

    /**
     * @param string $table
     * @param string $code
     * @param string $topAlias
     *
     * @return string
     */
    public function makeMappedAliasKey($table, $code, $topAlias = null);

    public function getJoin($tableName);

    /**
     * For inner usage
     *
     * @param $condition
     * @param string $operator
     * @return string
     */
    public function parseCondition($condition, $operator = 'AND');
}