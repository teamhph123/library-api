<?php

namespace Hph;

use Exception;
use League\Container\Container;
use PDO;

class QueryRunner
{
    protected ?string $sql = null;
    protected ?array $values = null;
    protected ? Container $container = null;
    protected ?int $insertId = null;
    protected ?PDO $pdo = null;
    protected ?array $txValues = null;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->pdo = $this->container->get('db');
    }

    /**
     * @param string $sql
     * @return QueryRunner
     * @todo parse the query to get a list of fields that should be present before we try to preapre the statement.
     */
    public function useQuery(string $sql): QueryRunner
    {
        $this->sql = $sql;
        return $this;
    }

    public function withValues(array $values): QueryRunner
    {
        $this->values = $values;
        return $this;
    }

    public function run()
    {
        try {
            $stmt = $this->pdo->prepare($this->sql);
        } catch (Exception $e) {
            print_r('err');
            // $this->saveError($e);
        }

        if ($this->pdo->errorCode() != '00000') {
            throw new Exception(
                sprintf(
                    "SQL Prepare error (%s): %s",
                    $this->pdo->errorCode(),
                    implode("|", $this->pdo->errorInfo())
                )
                ,
                500
            );
        }

        try {
            $stmt->execute($this->values);
        } catch (Exception $e) {
            // $this->saveError($e);
        }

        if ($stmt->errorCode() != '00000') {
            throw new Exception(
                sprintf(
                    "Database error (%s): %s",
                    $stmt->errorCode(),
                    implode("|", $stmt->errorInfo())
                )
                ,
                500
            );
        }

        $this->insertId = $this->pdo->lastInsertId();
        return $stmt;
    }

    public function getContainer(): ?Container
    {
        return $this->container;
    }

    public function getSql(): ?string
    {
        return $this->sql;
    }

    public function getValues(): ?array
    {
        return $this->values;
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function getPDO()
    {
        return $this->pdo;
    }

    public function initBatchTransaction(): void
    {
        $this->txValues = [];
    }

    public function addTransaction($values): void
    {
        $this->txValues[] = $values;
    }

    public function executeBatch(): void
    {
        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare($this->sql);

        foreach ($this->txValues as $values) {
            $stmt->execute($values);
        }

        $this->pdo->commit();
    }
}
