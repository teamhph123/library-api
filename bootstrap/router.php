<?php

use Hph\Auth\Authenticator;
use Hph\Auth\Login;
use Hph\Nonce\NonceService;
use Hph\Auth\UserNav;
use Hph\Users\AccountRecoveryService;
use Hph\Users\PasswordResetService;
use Hph\Users\UserActivationService;
use League\Container\Container;
use League\Route\Router;
use League\Route\Strategy\JsonStrategy;
use Library\Api\Signup\SignupService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function getRouter(Container $container, ServerRequestInterface $request): Router
{
    // $strategy = (new ApplicationStrategy)->setContainer($container);
    // $strategy = $container->get(ApplicationStrategy::class);
    $strategy = $container->get(JsonStrategy::class); //Strategy changed by Michael Munger <mj@hph.io> on 2022-09-12 @ 10:10am to allow the router to auto-generate OPTIONS responses for monitoring.
    $strategy->setContainer($container);

    // $router = (new Router)->setStrategy($strategy);
    $router = $container->get(Router::class);

    if ($request->getMethod() == 'OPTIONS') {
        $strategy->addResponseDecorator(function (ResponseInterface $response): ResponseInterface {
            return $response->withAddedHeader('Access-Control-Allow-Headers', 'Content-Type, authorization, x-mock-match-request-body');
        });
    }

    $router->setStrategy($strategy);

    $router->map('POST', '/api/v1/auth/login', Login::class);
    $router->map('POST', '/api/v1/auth/users/activate/{uuid:alphanum_dash}', UserActivationService::class);
    $router->map('POST', '/api/v1/auth/users/recover/{uuid:alphanum_dash}', AccountRecoveryService::class);
    $router->map('POST', '/api/v1/auth/users/reset', PasswordResetService::class);
    $router->map('GET', '/api/v1/auth/nonce/{uuid:alphanum_dash}', NonceService::class);
    $router->map('POST', '/api/v1/signup', SignupService::class);


    $router->group('/api/v1', function ($router) use ($container) {

        $router->map('GET', '/auth/users/{id:number}/nav', UserNav::class);


    })->middleware(new Authenticator($container));

    return $router;
}
