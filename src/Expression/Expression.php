<?php

namespace Tsukasa\QueryBuilder\Expression;

use Tsukasa\QueryBuilder\Exception\QBException;

class Expression extends AbstractExpression
{
    protected $expression;
    protected $params = [];

    public function __construct($expression, array $params = [])
    {
        $this->params = $params;
        $this->expression = $expression;
    }

    public function toSQL()
    {
        if ($this->expression) {
            return $this->generateSql() ?: '';
        }

        return '';
    }

    protected function generateSql()
    {
        $sql = $this->expression;

        if (strpos($sql, '{') !== false)
        {
            $tableAlias = $this->qb->getAlias();

            $sql = preg_replace_callback('~\{([^\}]+)\}~', function($matches) use ($tableAlias) {
                $rawColumn = $matches[1];

                list(, $column) = $this->qb->getLookupBuilder()->parseLookup($this->qb, $rawColumn, null);
                $column = $this->qb->getLookupBuilder()->fetchColumnName($column);

                if ($tableAlias !== null && strpos($column, '.') === false) {
                    $column = $tableAlias . '.' . $column;
                }

                return $this->qb->getAdapter()->buildColumns($column);

            }, $sql);
        }

        if (strpos($sql, '?'))
        {
            if (mb_substr_count($sql, '?') !== count($this->params)) {
                throw new QBException('Incorrect parameters count in Expression: "' . addslashes($this->expression) . '"');
            }

            $sql = preg_replace_callback('~\?~', function($matches) {
                return $this->qb->getAdapter()->quoteValue(
                    $this->getNextParam()
                );
            }, $sql);

        }

        return $sql;
    }

    protected function getNextParam()
    {
        static $params;

        if ($params === null) {
            $params = $this->params;
            reset($params);
        }

        $value = current($params);

        if (next($params) === null) {
            reset($params);
        }

        return $value;
    }
}