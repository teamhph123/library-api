<?php

namespace Hph\Nonce;

use Hph\ApiService;
use Hph\QueryRunner;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NonceService extends ApiService
{

    public function __invoke(ServerRequestInterface $request, array $routeArgs = []): ResponseInterface
    {
        $sql = "select nonce from users where nonce = :nonce";
        $values = ['nonce' => $routeArgs['uuid']];
        $runner = $this->container->get(QueryRunner::class);
        $runner->useQuery($sql);
        $runner->withValues($values);
        try {
            $stmt = $runner->run();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        $statusCode = ($stmt->rowCount() == 1 ? 200 : 404);
        return new EmptyResponse($statusCode);

    }
}