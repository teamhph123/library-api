<?php declare(strict_types=1);
/**
 * Class Login
 *
 * Authenticates a user / pass, and issus a JWT token to be used as a bearer token for future requests.
 *
 * @package Hphio\Api\Auth
 * @access public
 * @since 7.4
 */


namespace Hph\Auth;

use Hph\ApiService;
use Hph\Models\User;
use Hph\Models\UserBase;
use Laminas\Diactoros\Response\JsonResponse;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Valitron\Validator;


class Login extends ApiService
{

    protected ?UserBase $authorized_user = null;

    function validateRequest($jsonData): bool
    {
        $validator = new Validator((array)$jsonData);
        $validator->rule('required', ['user', 'pass']);
        $validator->rule('email', ['user']);
        if(!$validator->validate()) throw new Exception('Your authorization syntax is invalid. You must submit a valid username and password.', 400);
        return true;
    }

    function __invoke(ServerRequestInterface $request, array $routeArgs = []): ResponseInterface
    {
        try {
            $requestObj = json_decode($request->getBody()->getContents(), true);
            $this->validateRequest($requestObj);
            $requestObj['user'] = trim($requestObj['user']); //Fixed front-end issue where extraneous \n are added to username.
            $requestObj['user'] = strtolower($requestObj['user']);
            $user = $this->container->get(User::class);
            $user = $user->loadFromEmail($requestObj['user']);
            $authorized = $user->authorize($requestObj['user'], $requestObj['pass']);
            if($authorized) return new JsonResponse(['token' => $user->issueJWT()],200);
        } catch (Exception $e) {
            return $this->returnException($e);
        }
        return new JsonResponse(['error' => "Authorization failed."], 401);
    }
}
