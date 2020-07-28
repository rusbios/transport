<?php
declare(strict_types = 1);

namespace RB\Transport;

use RB\Transport\Db\Client;

class DB
{
    private static Client $connect;

    /**
     * @param string $table
     * @param array $columns
     * @param array $where
     * @param array $orders
     * @param int $offset
     * @param int|null $limit
     * @return array
     */
    public static function select(
        string $table,
        array $columns = [],
        array $where = [],
        array $orders = [],
        int $offset = 0,
        int $limit = null
    ): array
    {
        foreach ($columns as &$column) {
            $column = self::wrap($column);
        }
        $columns = $columns ? implode(', ', $columns) : '*';

        $sql = "select $columns from " . self::wrap($table);

        foreach ($where as $key => $value) {
            $wheres[] = self::where($key, $value);
        }
        if (isset($wheres)) {
            $sql .= ' where ' . implode(' and ', $wheres);
        }

        foreach ($orders as $key => $order) {
            if (is_int($key)) {
                $key = $order;
                $order = '';
            }
            $orderBy[] = self::wrap($key) . ' ' . trim($order) ?? 'asc';
        }
        if (isset($orderBy)) {
            $sql .= ' order by ' . implode(', ', $orderBy);
        }

        if ($offset > 0) {
            $sql .= " offset $offset";
        }

        if ($limit) {
            $sql .= " limit $limit";
        }

        return self::$connect->query($sql);
    }

    /**
     * @param string $table
     * @param array $values
     * @return int
     */
    public static function insert(string $table, array $values): int
    {
        foreach ($values as $key => $value) {
            $keys[] = self::wrap($key);
            $datas[] = "'" . trim($value) . "'";
        }
        $sql = sprintf(
            'insert into %s (%s) values (%s);',
            self::wrap($table),
            implode(', ', $keys),
            implode(', ', $datas)
        );

        return self::$connect->insert($sql);
    }

    /**
     * @param string $table
     * @param array $values
     * @param array $where
     * @return int
     */
    public static function update(string $table, array $values, array $where = []): int
    {
        $datas = [];
        foreach ($values as $key => $value) {
            $datas[] = self::wrap($key) . " = '" . trim($value) . "'";
        }

        $sql = 'update ' . self::wrap($table) . ' set ' . implode(', ', $datas);

        foreach ($where as $key => $value) {
            $wheres[] = self::where($key, $value);
        }
        if (isset($wheres)) {
            $sql .= ' where ' . implode(' and ', $wheres);
        }

        return self::$connect->updated($sql);
    }

    /**
     * @param string $key
     * @param string|bool|int|float|null $value
     * @param string|null $operator
     * @return string
     */
    private static function where(string $key, $value = null, string $operator = null): string
    {
        switch (trim($operator)) {
            case '=':
                $operator = '=';
                break;

            case '!=' || '<>':
                $operator = $value ? 'is null' : '=';
                break;

            case '>':
                $operator = '>';
                break;

            case '<':
                $operator = '<';
                break;

            case '<=':
                $operator = '<=';
                break;

            case '>=':
                $operator = '>=';
                break;

            default:
                $operator = $value ? 'is null' : '=';
        }

        if (!$value) {
            return self::wrap($key) . $operator;
        }

        if (is_bool($value)) {
            $value = (int)$value;
        }

        if (is_float($value)) {
            $value = (string)$value;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value != (int)$value) {
                return self::wrap($key) . " $operator '$value'";
            }
            $value = (int)$value;
        }

        if (is_int($value)) {
            return self::wrap($key) . " $operator $value";
        }
    }

    /**
     * @param string $key
     * @return string
     */
    private static function wrap(string $key): string
    {
        return '`' . trim($key) . '`';
    }
}