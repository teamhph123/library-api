<?php

/**
 * Abstraction of the roles table
 * Generated by Abstractor from hphio\util
 */

namespace Hph\Models;


use League\Container\Container;
/**
 * @codeCoverageIgnore
 */
class RoleBase
{

    /* <generated_8307439bc7050f426620456de9363c4b0c2c6ef1> */

    /* <database fields> */

    public $id   = null;
    public $name = null;

    /* </database fields> */


    /* <Dependency Injection Fields> */

    public ?Container $container = null;

    /* </Dependency Injection Fields> */

    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * Returns an associative array of values for this class.
     * @return array
     */

    public function getMyValues() : array {
        return [ "id"   => $this->id
               , "name" => $this->name
               ];
    }

    public function insert() {
        $sql = " INSERT INTO `roles`
                (  `name`
                )
                VALUES
                ( :name
                )";
        $values = $this->getMyValues();
        unset($values['id']);

        $this->prepareExecute($sql, $values);

        $this->id = $this->db->lastinsertid();
        return $this->id;

    }

    public function update() {
        $sql = "UPDATE `roles`
                SET
                `name` = :name
                WHERE `id` = :id
                LIMIT 1";

        $values = $this->getMyValues();
        $this->prepareExecute($sql, $values);
    }

    /* </generated_8307439bc7050f426620456de9363c4b0c2c6ef1> */
}
