<?php
declare(strict_types = 1);

namespace RB\Transport\Db;

use RB\Transport\DB;
use RB\Transport\Util;

abstract class Model
{
    const CREATED_TS = 'created_ts';
    const UPDATED_TS = 'updated_ts';

    protected string $table;
    public string $primaryKey = 'id';
    public bool $timestamps = true;

    private array $oldData;
    private array $data;

    /**
     * Model constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
        if (!empty($this->data[$this->primaryKey])) {
            unset($this->data[$this->primaryKey]);
        }
        $this->oldData = $data;
    }

    /**
     * @return Model[]
     */
    public static function all(): iterable
    {
        return self::select();
    }

    /**
     * @param mixed $id
     * @return Model|null
     */
    public static function find($id): Model
    {
        $model = new static();
        $collect = self::select([
            $model->primaryKey => $id
        ]);

        return array_shift($collect);
    }

    /**
     * @param array $where
     * @param array $orders
     * @param int $offset
     * @param int|null $limit
     * @return Model[]
     */
    public static function select(array $where = [], array $orders = [], int $offset = 0, int $limit = null): iterable
    {
        $models = [];

        foreach (DB::select(static::getTable(), [], $where, $orders, $offset, $limit) as $data) {
            $models[] = new static($data);
        }

        return $models;
    }

    /**
     * @param array $values
     * @return int
     */
    public static function insert(array $values): int
    {
        return DB::insert(static::getTable(), $values);
    }

    /**
     * @param array $values
     * @param array $where
     * @return int
     */
    public static function update(array $values, array $where = []): int
    {
        return DB::update(static::getTable(), $values, $where);
    }

    /**
     * @return string
     */
    public static function getTable(): string
    {
        $class = explode('\\',static::class);
        $table = end($class);
        if (substr($table, -5) == 'Model') {
            $table = substr($table, 0, -5);
        }
        return (new static())->table ?? Util::caseCamelToSnake($table);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        if ($name == $this->primaryKey) {
            return $this->getId();
        }

        $value = $this->data[$name] ?? null;
        $function = 'get'.Util::caseSnakeToCamel($name).'Attribute';
        if (function_exists($function)) {
            return $this->$function($value);
        }
        return $value;
    }

    /**
     * @param string $name
     * @param mixed|null $value
     * @return $this
     */
    public function __set(string $name, $value = null): self
    {
        if ($name == $this->primaryKey) {
            $this->setId((int)$value);
            return $this;
        }

        $function = 'set'.Util::caseSnakeToCamel($name).'Attribute';
        if (function_exists($function)) {
            $this->$function($value);
        } else {
            $this->data[$name] = $value;
        }

        return $this;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->oldData[$this->primaryKey] ?? null;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->oldData[$this->primaryKey] = $id;
        return $this;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function save(array $attributes = []): self
    {
        $attributes += $this->data;

        foreach ($attributes as $key => $value) {
            if (empty($this->oldData[$key]) || $value != $this->oldData[$key]) {
                $diff[$key] = $value;
            }
        }

        if (isset($diff)) {

            if ($this->timestamps) {
                $this->data[self::UPDATED_TS] = $diff[self::UPDATED_TS] = (new \DateTime())->format('Y-m-d H:i:s');
            }

            if (empty($this->oldData[$this->primaryKey])) {
                if ($this->timestamps) {
                    $this->data[self::CREATED_TS] = $diff[self::CREATED_TS] = $this->data[self::UPDATED_TS];
                }
                $this->oldData[$this->primaryKey] = DB::insert(self::getTable(), $diff);
            } else {
                DB::update(self::getTable(), $diff, [$this->primaryKey => $this->oldData[$this->primaryKey]]);
            }
        }

        return $this;
    }
}