<?php
/**
 * Class Authenticator
 *
 * Authenticator authenticates a user and creates the 'current_user' element for the container, which is used
 * to process future requests.
 *
 * Example Usage:
 *
 *
 * @package Hphio\Api\Auth
 * @author Michael Munger <mj@hph.io> on 2/3/22. Updated on 2022-11-24
 * @access public
 * @since 7.4
 * @copyright 2022 High Powered Help, Inc. All rights reserved.
 */


namespace Hph\Auth;

use \DateTime;
use Hph\Models;
use Hph\Models\User;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use League\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;
use function PHPUnit\Framework\throwException;


class Authenticator implements MiddlewareInterface
{

    public ?Container $container = null;
    private ?string $token = null;
    private ?User $current_user = null;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function verifyToken($request) {
        $authHeader = $request->getHeader('Authorization')[0];
        $token = str_replace('Bearer ', '', $authHeader);

        $secret = $this->container->get('config')->get("crypto.key");

        return $this->jwtDecode($token);
    }

    public function getCurrentUser($payload) : User {
        $sql = "SELECT t.*
                FROM users t
                WHERE ID = :id
                LIMIT 1";

        $stmt = $this->container->get("db")->prepare($sql);
        $stmt->execute(["id" => $payload->current_user_id]);
        if($stmt->errorInfo()[0] !== '00000') throw new Exception(sprintf("Database error: [%s] %s", $stmt->errorInfo()[0], $stmt->errorInfo()[2]));

        if($stmt->rowCount() == 0) throw new Exception("The user requested has disappeared during your request.", 409);

        return $stmt->fetchObject(User::class, [$this->container]);
    }

    public function getBaseUser($payload) : User {
        $sql = "SELECT t.*
                FROM users t
                WHERE ID = :id
                LIMIT 1";

        $stmt = $this->container->get("db")->prepare($sql);
        $stmt->execute(["id" => $payload->user_id]);
        if($stmt->errorInfo()[0] !== '00000') throw new Exception(sprintf("Database error: [%s] %s", $stmt->errorInfo()[0], $stmt->errorInfo()[2]));
        return $stmt->fetchObject(Models\User::class, [$this->container]);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     * @todo Fix JSON Errors that are not json in the body.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if($request->hasHeader('Authorization') == false ) return new EmptyResponse(401);

        try {
            $payload = $this->verifyToken($request);
            $this->jtiExists($payload->jti);
            $this->validatePayload($payload);
            $this->userMayImpersonate($payload);
            $user = $this->getCurrentUser($payload);
        } catch (Exception $e) {
            $errorCode = ($e->getCode() > 100 ? $e->getCode() : 401);
            return new JsonResponse(['error' => $e->getMessage() . '. Error uuid: 9c3a1bf2-bea2-4c8d-af07-2ca21315aca7.'], $errorCode);
        }

        $this->container->add('current_user', $user);

        return $handler->handle($request);
    }

    private function userExists($id, $username) {
        $stmt = $this->container->get('db')->prepare("SELECT * FROM users WHERE id=:id AND username=:username");
        $stmt->execute(["id" => $id, "username" => $username]);
        if($stmt->errorInfo()[0] !== '00000') throw new Exception(sprintf("Database error: [%s] %s", $stmt->errorInfo()[0], $stmt->errorInfo()[2]));
        return ($stmt->rowCount() == 1);
    }

    private function validateTimestamps($payload) {
        $now = $this->container->get(DateTime::class);
        $iat = $this->container->get(DateTime::class);
        $iat->setTimestamp($payload->iat);

        $nbf = $this->container->get(DateTime::class);
        $nbf->setTimestamp($payload->nbf);

        $exp = $this->container->get(DateTime::class);
        $exp->setTimestamp($payload->exp);

        if($now->getTimestamp() < $iat->getTimestamp())  return false;
        if($now->getTimestamp() < $nbf->getTimestamp())  return false;
        if($now->getTimestamp() >= $exp->getTimestamp()) return false;

        return true;
    }

    public function jtiExists($jti) {
        $stmt = $this->container->get('db')->prepare("SELECT * FROM user_tokens WHERE user_tokens.jti = :jti LIMIT 1");
        $stmt->execute(["jti" => $jti]);
        if($stmt->errorInfo()[0] !== '00000') throw new Exception(sprintf("Database error: [%s] %s", $stmt->errorInfo()[0], $stmt->errorInfo()[2]));
        if($stmt->rowCount() != 1) throw new Exception("Token error: Token does not exist", 401);
        return true;
    }

    /**
     * @param object $payload
     * @return bool
     * @throws \Exception
     * @todo Validate timestamps was remarked out because JWT will do it for us, but need to figure out mocking DateTime so we can make this test work again.
     */
    public function validatePayload(object $payload)
    {
        $valid = true;
        $valid = $valid && ($payload->sub == "authorization");
        $valid = $valid && ($this->userExists($payload->user_id, $payload->user));
//        $valid = $valid && ($this->userExists($payload->current_user_id, $payload->current_user));
//        $valid = $valid && ($this->validateTimestamps($payload));
//        $valid = $valid && ($this->jtiExists($payload->jti, $payload->user_id));

        if(!$valid) throw new Exception("Invalid or expired credentials in authorization payload.", 401);
        return $valid;
    }

    public function userMayImpersonate($payload) {
        if($payload->user_id == $payload->current_user_id) return true;  //Your mom was right. It's OK to be yourself.

        $sql = 'select count(*) from impersonate_permissions where user = :user_id and may_login_as = :current_user_id';
        $values = [
            'user_id' => $payload->user_id,
            'current_user_id' => $payload->current_user_id
        ];
        $stmt = $this->container->get('db')->prepare($sql);
        $stmt->execute($values);
        if($stmt->errorCode() != '00000') throw new Exception(sprintf("Database error (%s): %s", $stmt->errorCode(), $stmt->errorInfo()[2]));

        if ($stmt->rowCount() == 0) throw new Exception("You do not have permissions to authenticate as the requested user", 403);
        if ($stmt->rowCount() > 1) return true;
        return false;
    }

    /**
     * Wrapper function for JWT::encode().
     * @param mixed $obj
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function jwtEncode($obj) {
        return JWT::encode($obj,
            $this->container->get('config')->get('crypto.key'),
            'HS256');
    }

    public function jwtDecode($obj) : stdClass {
        return JWT::decode($obj,
        new Key($this->container->get('config')->get('crypto.key'), 'HS256')
        );
    }
}
