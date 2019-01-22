<?php
/**
 * QueryBuilder
 * User: m.korobitsyn
 * Date: 22.01.19 17:04
 */

namespace Tsukasa\QueryBuilder\Interfaces;

use Doctrine\DBAL\Connection;
use Tsukasa\QueryBuilder\BaseLookupCollection;

interface ISQLGenerator
{
    /**
     * @return string
     */
    public function getTablePrefix();

    /**
     * @return BaseLookupCollection|ILookupCollection
     */
    public function getLookupCollection();

    /**
     * Quotes a column name for use in a query.
     * If the column name contains prefix, the prefix will also be properly quoted.
     * If the column name is already quoted or contains '(', '[[' or '{{', then this method will do nothing.
     *
     * @param string $name column name
     * @return string the properly quoted column name
     * @see quoteSimpleColumnName()
     */
    public function quoteColumn($name);

    /**
     * Quotes a simple column name for use in a query.
     * A simple column name should contain the column name only without any prefix.
     * If the column name is already quoted or is the asterisk character '*', this method will do nothing.
     * @param string $name column name
     * @return string the properly quoted column name
     */
    public function quoteSimpleColumnName($name);

    /**
     * Returns the actual name of a given table name.
     * This method will strip off curly brackets from the given table name
     * and replace the percentage character '%' with [[Connection::tablePrefix]].
     * @param string $name the table name to be converted
     * @return string the real name of the given table name
     */
    public function getRawTableName($name);

    /**
     * @return null|Connection
     */
    public function getDriver();

    /**
     * @param Connection $driver
     * @return $this
     */
    public function setDriver(Connection $driver);

    /**
     * Quotes a string value for use in a query.
     * Note that if the parameter is not a string, it will be returned without change.
     *
     * Note sqlite3:
     * A string constant is formed by enclosing the string in single quotes (').
     * A single quote within the string can be encoded by putting two single
     * quotes in a row - as in Pascal. C-style escapes using the backslash
     * character are not supported because they are not standard SQL.
     *
     * @param string $value string to be quoted
     * @return string the properly quoted string
     * @see http://www.php.net/manual/en/function.PDO-quote.php
     */
    public function quoteValue($value);

    /**
     * Quotes a table name for use in a query.
     * If the table name contains schema prefix, the prefix will also be properly quoted.
     * If the table name is already quoted or contains '(' or '{{',
     * then this method will do nothing.
     * @param string $name table name
     * @return string the properly quoted table name
     * @see quoteSimpleTableName()
     */
    public function quoteTableName($name);

    /**
     * Quotes a simple table name for use in a query.
     * A simple table name should contain the table name only without any schema prefix.
     * If the table name is already quoted, this method will do nothing.
     * @param string $name table name
     * @return string the properly quoted table name
     */
    public function quoteSimpleTableName($name);

    /**
     * @param $sql
     * @return mixed
     *
     */
    public function quoteSql($sql);

    public function convertToDbValue($rawValue);

    /**
     * Checks to see if the given limit is effective.
     * @param mixed $limit the given limit
     * @return boolean whether the limit is effective
     */
    public function hasLimit($limit);

    /**
     * Checks to see if the given offset is effective.
     * @param mixed $offset the given offset
     * @return boolean whether the offset is effective
     */
    public function hasOffset($offset);

    /**
     * @param integer $limit
     * @param integer $offset
     * @return string the LIMIT and OFFSET clauses
     */
    public function sqlLimitOffset($limit = null, $offset = null);

    /**
     * @param $columns
     * @return string
     */
    public function buildColumns($columns);

    /**
     * Builds a SQL statement for adding a primary key constraint to an existing table.
     * @param string $name the name of the primary key constraint.
     * @param string $tableName the table that the primary key constraint will be added to.
     * @param string|array $columns comma separated string or array of columns that the primary key will consist of.
     * @return string the SQL statement for adding a primary key constraint to an existing table.
     */
    public function sqlAddPrimaryKey($tableName, $name, $columns);

    /**
     * Builds a SQL statement for removing a primary key constraint to an existing table.
     * @param string $name the name of the primary key constraint to be removed.
     * @param string $tableName the table that the primary key constraint will be removed from.
     * @return string the SQL statement for removing a primary key constraint from an existing table.
     */
    public function sqlDropPrimaryKey($tableName, $name);

    public function sqlAlterColumn($tableName, $column, $type);

    /**
     * @param $tableName
     * @param array $rows
     * @param string $options Sql Options
     * @return string
     */
    public function sqlInsert($tableName, array $rows, $options = '');

    public function sqlUpdate($tableName, array $columns, $options = '');

    /**
     * @param $select
     * @param $from
     * @param $where
     * @param $order
     * @param $group
     * @param $limit
     * @param $offset
     * @param $join
     * @param $having
     * @param $union
     * @param string $options
     * @return string
     * @throws \Exception
     */
    public function generateSelectSQL($select, $from, $where, $order, $group, $limit, $offset, $join, $having, $union, $options = '');

    /**
     * @param $tableName
     * @param array $columns
     * @param null|string $options
     * @param bool $ifNotExists
     * @return string
     */
    public function sqlCreateTable($tableName, $columns, $options = null, $ifNotExists = false);

    /**
     * @param $oldTableName
     * @param $newTableName
     * @return string
     */
    public function sqlRenameTable($oldTableName, $newTableName);

    /**
     * @param $tableName
     * @param bool $ifExists
     * @param bool $cascade
     * @return string
     */
    public function sqlDropTable($tableName, $ifExists = false, $cascade = false);

    /**
     * @param $tableName
     * @param bool $cascade
     * @return string
     */
    public function sqlTruncateTable($tableName, $cascade = false);

    /**
     * @param $tableName
     * @param $name
     * @return string
     */
    public function sqlDropIndex($tableName, $name);

    /**
     * @param $value
     * @return string
     */
    public function getSqlType($value);

    /**
     * @param $tableName
     * @param $column
     * @return string
     */
    public function sqlDropColumn($tableName, $column);

    /**
     * @param $tableName
     * @param $oldName
     * @param $newName
     * @return mixed
     */
    public function sqlRenameColumn($tableName, $oldName, $newName);

    /**
     * @param $tableName
     * @param $name
     * @return mixed
     */
    public function sqlDropForeignKey($tableName, $name);

    /**
     * @param $tableName
     * @param $name
     * @param $columns
     * @param $refTable
     * @param $refColumns
     * @param null|string $delete
     * @param null|string $update
     * @return string
     */
    public function sqlAddForeignKey($tableName, $name, $columns, $refTable, $refColumns, $delete = null, $update = null);

    /**
     * @return string
     */
    public function getRandomOrder();

    /**
     * @param mixed $value
     * @return string
     */
    public function getBoolean($value = null);

    /**
     * @param mixed $value
     * @return string
     */
    public function getDateTime($value = null);

    /**
     * @param mixed $value
     * @return string
     */
    public function getDate($value = null);

    /**
     * @param mixed $value
     * @return mixed
     */
    public function getTimestamp($value = null);

    /**
     * @param $tableName
     * @param $column
     * @param $type
     * @return string
     */
    public function sqlAddColumn($tableName, $column, $type);

    /**
     * @param $tableName
     * @param $name
     * @param array $columns
     * @param bool $unique
     * @return string
     */
    public function sqlCreateIndex($tableName, $name, array $columns, $unique = false);

    /**
     * @param $tables
     * @return string
     */
    public function sqlFrom($tables);

    /**
     * @param $joinType string
     * @param $tableName string
     * @param $on string|array
     * @param $alias string
     * @return string
     */
    public function sqlJoin($joinType, $tableName, $on = [], $alias = null, $index = null);

    /**
     * @param $where string|array
     * @return string
     */
    public function sqlWhere($where);

    /**
     * @param $having
     * @return string
     */
    public function sqlHaving($having);

    /**
     * @param QueryBuilderInterface|string $union
     * @param bool $all
     * @return string
     */
    public function sqlUnion($union, $all = false);

    /**
     * @param $tableName
     * @param $sequenceName
     * @return string
     */
    public function sqlResetSequence($tableName, $sequenceName);

    /**
     * @param bool $check
     * @param string $schema
     * @param string $table
     * @return string
     */
    public function sqlCheckIntegrity($check = true, $schema = '', $table = '');

    /**
     * @param $columns
     * @return string
     */
    public function sqlGroupBy($columns);

    /**
     * @param array $columns
     * @param null|string $options
     * @return string
     */
    public function sqlOrderBy(array $columns, $options = null);

    /**
     * @param array|null|string $columns
     * @param string $options
     *
     * @return string
     */
    public function sqlSelect($columns, $options = '');

    /**
     * Prepare value for db
     * @param $value
     * @return int
     */
    public function prepareValue($value);
}