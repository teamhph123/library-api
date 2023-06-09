<?php

/**
 * Abstraction of the users table
 * Generated by Abstractor from hphio\util
 */

namespace Hph\Models;

use DateInterval;
use \DateTime;
use Hph\DBEnabledClassTrait;
use Hph\QueryRunner;
use Exception;
use Firebase\JWT\JWT;
use Hph\RandomGenerator;

class User extends UserBase
{
    use DBEnabledClassTrait;
    public $stmt;


    /**
     * @throws Exception
     */
    public function loadFromEmail(?string $email) {

        $sql = <<<EOF
SELECT t.*
FROM users t
WHERE email=:email
LIMIT 1
EOF;

        $values = ["email" => $email];

        $runner = $this->container->get(QueryRunner::class);
        $stmt = $runner->useQuery($sql)->withValues($values)->run();
        if($stmt->rowCount() == 0) throw new Exception("Invalid user or password.", 401);

        $result = $stmt->fetchObject(User::class, [$this->container]);
        return $result;
    }

    /**
     * @param $username
     * @param $password
     * @return bool
     * @throws Exception
     */
    public function authorize($username, $password) :bool {
        if(strcmp($this->username, $username) !== 0) return false;
        $result = password_verify($password, $this->password);
        if(!$result) throw new Exception("Your username or password is invalid", 401);
        return $result;
    }

    public function issueJWT($payload = null) {

        list($exp, $config, $generatedPayload) = $this->buildPayload();
        $payload = $payload ?? $generatedPayload;

        $jwt = JWT::encode($payload, $config->get('crypto.key'), 'HS256');

        $userToken = $this->container->get(UserToken::class);
        $userToken->token = $jwt;
        $userToken->delete_after = $exp->format("Y-m-d H:i:s");
        $userToken->user = $this->id;
        $userToken->jti = $payload["jti"];
        $userToken->insert();

        return $jwt;
    }

    public function getRole() {
        $sql = "SELECT * FROM roles WHERE id = :id";
        $values = ["id" => $this->role];
        $stmt = $this->prepareExecute($sql, $values);
        if( $stmt->rowCount() == 0 ) throw new Exception("No role found for id: $this->role. This should have never happened.");
        $role = $stmt->fetchObject(\Hphio\Auth\Models\Role::class, [$this->container]);
        return $role->name;
    }

    /**
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function buildPayload(): array
    {
        $iat = $this->container->get(DateTime::class);
        $nbf = clone $iat;
        $exp = clone $iat;
        $jti = $this->container->get(RandomGenerator::class);

        $config = $this->container->get('config');
        $exp->add(new DateInterval($config->get("authentication.expiry")));

        $payload = [];
        $payload['sub'] = 'authorization';
        $payload["user"] = $this->username;
        $payload["user_id"] = $this->id;
        $payload["current_user"] = $this->username;
        $payload["current_user_id"] = $this->id;
        $payload["iat"] = $iat->getTimestamp();
        $payload["nbf"] = $nbf->getTimestamp();
        $payload["exp"] = $exp->getTimestamp();
        $payload["jti"] = $jti->uuidv4();
        $payload["uuid"] = $this->uuid;

        return array($exp, $config, $payload);
    }

//    public function __toString() {
//        $properties = get_object_vars($this);
//        $masked_properties = ['container', 'password', 'nonce', 'role', 'parent_entity', 'activated_on', 'password_version'];
//        foreach($masked_properties as $mask) {
//            unset($properties[$mask]);
//        }
//        $buffer = '';
//        foreach($properties as $name => $value) {
//            $buffer .= "$name: $value\n";
//        }
//        return $buffer;
//    }
}
