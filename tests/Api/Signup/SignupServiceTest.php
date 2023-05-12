<?php

namespace Tests\Api\Signup;

use Hph\Models\User;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Stream;
use League\Container\Container;
use Library\Api\Signup\SignupService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tests\LibraryTest;

class SignupServiceTest extends LibraryTest
{

    /**
     * @return void
     * @dataProvider providerTestInvoke
     */
    public function test__invoke(ServerRequestInterface $request,
                                 array                  $routeArgs,
                                 Container              $container,
                                 ResponseInterface      $expectedResponse)
    {
        $this->loadDataSet();
        $service = $container->get(SignupService::class);
        $response = $service->__invoke($request, $routeArgs);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseObj = json_decode($response->getBody()->getContents(), true);
        $expectedObj = json_decode($expectedResponse->getBody()->getContents(), true);
        $this->assertSame($expectedResponse->getStatusCode(), $response->getStatusCode(), print_r($responseObj, true));

        unset($responseObj['created']);
        unset($expectedObj['created']);
        unset($responseObj['last_login']);
        unset($expectedObj['last_login']);

        $this->assertSame($expectedObj, $responseObj);
    }

    public function providerTestInvoke()
    {
        return[
            $this->validRequest()
        ];
    }

    private function validRequest()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $routeArgs = [];
        /* <container with user> */
        $jsonPayload= '{
            "first":"John",
            "last":"Doe",
            "company_name":"TestX",
            "password":"1235",
            "email":"test123@test.com"
        }';

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $jsonPayload);
        rewind($stream);

        $serverParams = [];
        $serverParams["SERVER_NAME"] = "dev.support.hph.io";

        $request->method('getBody')->willReturn(new Stream($stream));
        $request->method('getServerParams')->willReturn($serverParams);

        $user = $this->createMock(User::class);
        $user->id = '1';
        $user->role = '1';

        $container = $this->buildContainer()
            ->withConfig()
            ->withDatabase()
            ->withCurrentUser($user)
            ->withoutRandomness()
            ->getContainer();

        /* <expected response template> */
        $jsonPayload = file_get_contents(dirname(__FILE__) . "/fixtures/validRequest.json");
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $jsonPayload);
        rewind($stream);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $expectedResponse->method('getStatusCode')->willReturn(201);
        $expectedResponse->method('getBody')->willReturn(new Stream($stream));
        /* </expected response template> */

        return [$request, $routeArgs, $container, $expectedResponse];
    }


  /**
   * @return void
   * @dataProvider providerTestInvokeFailures
   */
  public function test__invokeFailures(ServerRequestInterface $request,
                                       array                  $routeArgs,
                                       Container              $container,
                                       ResponseInterface      $expectedResponse) {

    $service = $container->get(SignupService::class);
    $response = $service->__invoke($request, $routeArgs);

    $responseObj = json_decode($response->getBody()->getContents(), true);
    $expectedObj = json_decode($expectedResponse->getBody()->getContents(), true);

    $this->assertSame($expectedResponse->getStatusCode(), $response->getStatusCode(), print_r($responseObj, true));
    $this->assertSame($expectedObj, $responseObj);
  }

  public function providerTestInvokeFailures ()
  {
    return [
        $this->malformedEmail(),
        $this->missingEmail(),
        $this->existingUserEmail()
    ];
  }

  private function malformedEmail()
  {
    $request = $this->createMock(ServerRequestInterface::class);
    $routeArgs = [];
    /* <container with user> */
    $jsonPayload= '{
            "first":"John",
            "last":"Doe",
            "company_name":"TestX",
            "password":"1235",
            "email":"test123@test.@com"
        }';

    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $jsonPayload);
    rewind($stream);

    $serverParams = [];
    $serverParams["SERVER_NAME"] = "dev.support.hph.io";

    $request->method('getBody')->willReturn(new Stream($stream));
    $request->method('getServerParams')->willReturn($serverParams);

    $user = $this->createMock(User::class);
    $user->id = '1';
    $user->role = '1';

    $container = $this->buildContainer()
        ->withConfig()
        ->withDatabase()
        ->withCurrentUser($user)
        ->withoutRandomness()
        ->getContainer();

    $response = ['error' => 'Unable to create user. Malformed email address.'];
    $responseJsonPayload = json_encode($response);

    $responseStream = fopen('php://memory', 'r+');
    fwrite($responseStream, $responseJsonPayload);
    rewind($responseStream);

    $expectedResponse = $this->createMock(ResponseInterface::class);
    $expectedResponse->method('getStatusCode')->willReturn(400);

    $expectedResponse->method('getBody')->willReturn(new Stream($responseStream));

    return [$request, $routeArgs, $container, $expectedResponse];
  }

  private function missingEmail ()
  {
    $request = $this->createMock(ServerRequestInterface::class);
    $routeArgs = [];
    /* <container with user> */
    $jsonPayload= '{
            "first":"John",
            "last":"Doe",
            "company_name":"TestX",
            "password":"1235",
            "email":""
        }';

    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $jsonPayload);
    rewind($stream);

    $serverParams = [];
    $serverParams["SERVER_NAME"] = "dev.support.hph.io";

    $request->method('getBody')->willReturn(new Stream($stream));
    $request->method('getServerParams')->willReturn($serverParams);

    $user = $this->createMock(User::class);
    $user->id = '1';
    $user->role = '1';

    $container = $this->buildContainer()
        ->withConfig()
        ->withDatabase()
        ->withCurrentUser($user)
        ->withoutRandomness()
        ->getContainer();

    $response = ['error' => 'Unable to create user. Missing email.'];
    $responseJsonPayload = json_encode($response);

    $responseStream = fopen('php://memory', 'r+');
    fwrite($responseStream, $responseJsonPayload);
    rewind($responseStream);

    $expectedResponse = $this->createMock(ResponseInterface::class);
    $expectedResponse->method('getStatusCode')->willReturn(400);

    $expectedResponse->method('getBody')->willReturn(new Stream($responseStream));

    return [$request, $routeArgs, $container, $expectedResponse];
  }

  private function existingUserEmail ()
  {
    $request = $this->createMock(ServerRequestInterface::class);
    $routeArgs = [];
    /* <container with user> */
    $jsonPayload= '{
            "first":"John",
            "last":"Doe",
            "company_name":"TestX",
            "password":"1235",
            "email":"kaloyan@hph.io"
        }';

    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $jsonPayload);
    rewind($stream);

    $serverParams = [];
    $serverParams["SERVER_NAME"] = "dev.support.hph.io";

    $request->method('getBody')->willReturn(new Stream($stream));
    $request->method('getServerParams')->willReturn($serverParams);

    $user = $this->createMock(User::class);
    $user->id = '1';
    $user->role = '1';

    $container = $this->buildContainer()
        ->withConfig()
        ->withDatabase()
        ->withCurrentUser($user)
        ->withoutRandomness()
        ->getContainer();

    $response = ['error' => 'A user with that email is already created.'];
    $responseJsonPayload = json_encode($response);

    $responseStream = fopen('php://memory', 'r+');
    fwrite($responseStream, $responseJsonPayload);
    rewind($responseStream);

    $expectedResponse = $this->createMock(ResponseInterface::class);
    $expectedResponse->method('getStatusCode')->willReturn(400);

    $expectedResponse->method('getBody')->willReturn(new Stream($responseStream));

    return [$request, $routeArgs, $container, $expectedResponse];
  }



}
