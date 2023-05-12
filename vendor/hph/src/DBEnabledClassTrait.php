<?php

namespace Hph;

use Exception;

trait DBEnabledClassTrait
{

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function prepareExecute($sql, $values = [], $exceptionMessage = null)
    {
        $this->db = $this->container->get("db");

        $stmt = $this->db->prepare($sql);

        if ($stmt === false) throw new \PDOException(sprintf("Database error [%s: %s] while preparing sql: (%s)", $this->db->errorCode(), $this->db->errorInfo()[2], $sql));

        $stmt->execute($values);

        if ($stmt->errorCode() !== '00000') {
            $defaultError = sprintf("Database error (%s) %s", $stmt->errorCode(), $stmt->errorInfo()[2]);
            throw new Exception($exceptionMessage ?? $defaultError);
        }

        return $stmt;
    }
}
