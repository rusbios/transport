<?php
declare(strict_types = 1);

namespace RB\Transport\Db;

use PDO;

class Client
{
    protected PDO $connect;

    /**
     * Client constructor.
     * @param string $driver
     * @param string $host
     * @param string $dbName
     * @param string $user
     * @param string $password
     * @param int $port
     */
    public function __construct(string $driver, string $host, string $dbName, string $user, string $password, int $port)
    {
        $this->connect = new PDO(
            sprintf('%s:host=%s;port=%i;dbname=%s', $driver, $host, $port, $dbName),
            $user,
            $password
        );
    }

    /**
     * @param string $sql
     * @return array
     */
    public function query(string $sql): array
    {
        return $this->connect->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $sql
     * @return int
     */
    public function insert(string $sql): int
    {
        $this->connect->query($sql);
        return (int)$this->connect->lastInsertId();
    }

    /**
     * @param string $sql
     * @return int
     */
    public function updated(string $sql): int
    {
        return $this->connect->query($sql)->rowCount();
    }
}