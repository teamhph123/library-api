<?php

namespace Hph;

use Exception;
use Hph\Models\User;
use ReflectionClass;
use Nette\Utils\DateTime;
use League\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

abstract class ApiService
{
    protected ?Container $container = null;

    abstract public function __invoke(ServerRequestInterface $request, array $routeArgs = []): ResponseInterface;

    public function __construct(Container $container)
    {
        $this->setContainer($container);
    }

    /**
     * @param Container $container
     * @return void
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * @param Exception $e
     * @return void
     */
    public function logException(Exception $e): void
    {
        $className = get_class($this);
        $reflection_class = new ReflectionClass($className);

        $logPath = $this->container->get('config')->get('paths.homedir') . "/logs/exceptions.log";

        $fh = fopen($logPath, 'a');
        fwrite($fh, "==================================================" . PHP_EOL);
        fwrite($fh, "Exception for endpoint: " . $reflection_class->getName() . PHP_EOL);
        fwrite($fh, (new DateTime())->format("Y-m-d H:i:s") . PHP_EOL);
        fwrite($fh, "Exception(" . $e->getCode() . "):" . $e->getMessage() . PHP_EOL);
        fwrite($fh, "Occured on: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL);
        fwrite($fh, "Code:" . PHP_EOL . $e->getCode() . PHP_EOL);
        fwrite($fh, PHP_EOL);
        fwrite($fh, "Stack Trace:\n" . $e->getTraceAsString());
        fwrite($fh, PHP_EOL);
        fwrite($fh, "<end>");
    }

    protected function clientExists($id)
    {
        $sql = "SELECT `id` FROM `clients` WHERE `id` = :client_id LIMIT 1";
        $stmt = $this->container->get("db")->prepare($sql);
        $stmt->execute(['client_id' => $id]);
        if ($stmt->errorCode() !== '00000')
            throw new Exception($stmt->errorInfo()[2]);
        return ($stmt->rowCount() == 1);
    }

    /**
     * @param $id
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @todo - Refactor this into a base class for dealing with leads. Only methods that can be used by ALL endpoints should be in this class.
     */
    protected function leadExists($id): bool
    {
        $sql = "SELECT `id` FROM `leads` WHERE `id` = :lead_id LIMIT 1";
        $values = ['lead_id' => $id];

        $runner = $this->container->get(QueryRunner::class);
        $stmt = $runner->useQuery($sql)->withValues($values)->run();

        if ($stmt->errorCode() !== '00000') {
            throw new Exception($stmt->errorInfo()[2]);
        }
        return ($stmt->rowCount() == 1);
    }

    /**
     * @param $leadId
     * @return true
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @todo - Refactor this into a base class for dealing with leads. Only methods that can be used by ALL endpoints should be in this class.
     * @todo - fix business logic. See notes below.
     * Notes:
     * Roles that are managing roles should all return true for this.\
     * So, query the entity that the lead belongs to, and check to see if the current user has an managerial role
     * and that the current user's parent entity is the same as the lead's entity. If so, return true because managing roles
     * should have access to all data for their organizations (entity).
     */
    protected function isYourLead($leadId)
    {
        if ($this->validateAdminPermissions())
            return true;

        return true;
        $sql = <<<SQL
SELECT `id`
FROM `leads`
WHERE `owner` = :ownerId
AND `id` = :leadId
LIMIT 1
SQL;

        $values = [
            'ownerId' => $this->container->get('current_user')->id,
            'leadId' => $leadId
        ];

        $runner = $this->container->get(QueryRunner::class);
        $stmt = $runner->useQuery($sql)->withValues($values)->run();

        if ($stmt->errorCode() !== '00000') {
            throw new Exception($stmt->errorInfo()[2]);
        }
        return ($stmt->rowCount() == 1);
    }

    public function getRequestedUser($id): User
    {
        $sql = "SELECT * FROM users WHERE id = :client_id";
        $stmt = $this->container->get("db")->prepare($sql);
        $stmt->execute(['client_id' => $id]);
        if ($stmt->errorCode() !== '00000')
            throw new Exception($stmt->errorInfo()[2]);
        return $stmt->fetchObject(User::class, [$this->container]);
    }

    public function validateAdminPermissions(): bool
    {
        if ($this->container->get('current_user')->role == 1)
            return true;
        return false;
    }

    protected function returnException(Exception $e)
    {
        $this->logException($e);
        $code = ($e->getCode() > 99 ? $e->getCode() : 500);
        return new JsonResponse(['error' => $e->getMessage()], $code);
    }

    protected function dumpHeaders(string $uuid, string $path, ServerRequestInterface $request)
    {
        $fh = fopen($path . '/' . $uuid . '.headers', 'w');
        fwrite($fh, json_encode($request->getHeaders()));
        $request->getBody()->rewind();
        fclose($fh);
    }

    protected function dumpPayload(string $uuid, string $path, ServerRequestInterface $request): void
    {
        $fh = fopen($path . '/' . $uuid, 'w');
        fwrite($fh, $request->getBody()->getContents());
        $request->getBody()->rewind();
        fclose($fh);
    }

    protected function dumpTransactionToLog(ServerRequestInterface $request, $serviceName)
    {
        $path = $this->container->get('config')->get('paths.homedir') . sprintf('/logs/transactions/%s/', $serviceName);
        if(!file_exists($path)) mkdir($path,0777,true);
        if(!file_exists($path)) throw new Exception("Could not create transaction log directory!", 500);
        $uuid = $this->container->get(RandomGenerator::class)->uuidv4();
        $this->dumpPayload($uuid, $path, $request);
        $this->dumpHeaders($uuid, $path, $request);
    }
}
