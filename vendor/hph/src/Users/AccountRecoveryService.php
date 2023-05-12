<?php
/**
 * Class PasswordResetService
 *
 * PasswordResetService documentation goes here. Explain
 * what this class does in a few lines.
 *
 * Example Usage:
 * if(true) {
 *   dosomething();
 * }
 *
 * @package Hphio\Api\Auth\Users
 * @author Michael Munger <mj@hph.io> on 4/18/22
 * @access public
 * @since 7.4
 * @copyright 2022 High Powered Help, Inc. All rights reserved.
 */


namespace Hph\Users;


use Hph\ApiService;
use Laminas\Diactoros\Response\JsonResponse;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AccountRecoveryService extends ApiService
{

    public function __invoke(ServerRequestInterface $request, array $routeArgs = []): ResponseInterface
    {
        $nonce = filter_var($routeArgs['uuid'], FILTER_SANITIZE_STRING);
        $password = $request->getParsedBody()['password'];
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $values = ['nonce' => $nonce];

        $sql = "select id from users where nonce = :nonce";
        $stmt = $this->container->get('db')->prepare($sql);
        $stmt->execute($values);
        if($stmt->errorCode() != '00000') throw new \Exception(sprintf("Database error (%s): %s", $stmt->errorCode(), $stmt->errorInfo()[2]));

        $userObj = $stmt->fetch(PDO::FETCH_OBJ);
        $updateValues = ['password' => $hash, 'id' => $userObj->id];

        unset($stmt);
        $stmt = $this->container->get('db')->prepare("update users set password = :password, nonce = NULL where id = :id");
        $stmt->execute($updateValues);

        if($stmt->errorCode() != '00000') throw new \Exception(sprintf("Database error (%s): %s", $stmt->errorCode(), $stmt->errorInfo()[2]));

        $stmt = $this->container->get('db')->prepare("SELECT username from users where id = :id");
        $stmt->execute(['id'=>$userObj->id]);
        if($stmt->errorCode() != '00000') throw new \Exception(sprintf("Database error (%s): %s", $stmt->errorCode(), $stmt->errorInfo()[2]));

        $row = $stmt->fetch(PDO::FETCH_OBJ);

        $returnData = ["username" => $row->username];

        return new JsonResponse($returnData, 200);
    }
}
