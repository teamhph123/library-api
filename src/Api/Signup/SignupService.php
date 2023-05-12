<?php

namespace Library\Api\Signup;

use Exception;
use hph\Farret\Notif;
use Hph\QueryRunner;
use Hph\RandomGenerator;
use Laminas\Diactoros\Response\JsonResponse;
use PDO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Library\LibraryApiService;

class SignupService extends LibraryApiService
{

    public function __invoke(ServerRequestInterface $request, array $routeArgs = []): ResponseInterface
    {
        $requestObj = json_decode($request->getBody()->getContents(), true);

        try {
            $this->validateRequest($requestObj);
            $id = $this->createUser($requestObj);
            $obj = $this->loadUser($id);
            $this->sendNotification($obj, $request);
        } catch (Exception $e) {
            return $this->returnException($e);
        }

        return new JsonResponse($obj, 201);
    }

    public function getAllUsersEmails()
    {
        $sql = "SELECT `email` FROM users";
        $stmt = $this->container->get('db')->prepare($sql);
        $stmt->execute();
        if ($stmt->errorCode() != '00000')
            throw new Exception(sprintf(("Database error (%s): %s"), $stmt->errorCode(), $stmt->errorInfo()[2]));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validateRequest($requestObj)
    {
        if (!filter_var($requestObj['email'], FILTER_VALIDATE_EMAIL) and $requestObj['email'] !== "") {
            throw new Exception("Unable to create user. Malformed email address.", 400);
        }

        if ($requestObj['email'] == "") {
            throw new Exception("Unable to create user. Missing email.", 400);
        }

        $allUsersEmails = $this->getAllUsersEmails();
        $allUsersEmailsValues = [];
        foreach ($allUsersEmails as $value) {
            $allUsersEmailsValues[] = $value['email'];
        }

        if (in_array($requestObj['email'], $allUsersEmailsValues)) {
            throw new Exception("A user with that email is already created.", 400);
        }
    }

    private function createUser($requestObj)
    {
        $sql = <<<SQL
INSERT INTO users (username, email, password, nonce, role, first, last, company_name, uuid)
VALUES (:username, :email, :password, :nonce, :role, :first, :last, :company_name, :uuid);
SQL;

        $uuid = $this->container->get(RandomGenerator::class)->uuidv4();
        $email = strtolower($requestObj['email']);
        $password = $requestObj['password'];
        $role = 3;
        $values = [
            'username' => $email,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'nonce' => $uuid,
            'role' => $role,
            'first' => $requestObj['first'],
            'last' => $requestObj['last'],
            'company_name' => $requestObj['company_name'],
            'uuid' => $uuid
        ];

        $runner = $this->container->get(QueryRunner::class);
        $runner->useQuery($sql);
        $runner->withValues($values);

        try {
            $runner->run();
        } catch (Exception $e) {
            throw new Exception("There was a problem creating a new user. Error: " . $e->getMessage() . " uuid: f5c27fab-4f72-4962-b907-08df026338d4", 500);
        }

        return $runner->lastInsertId();
    }

    private function loadUser($id)
    {
        $sql = <<<SQL
SELECT id, username, email, nonce, role, first, last, company_name, created, last_login, activation_status, activated_on,uuid
FROM users
WHERE id = :id
SQL;

        $values = ['id' => $id];
        $runner = $this->container->get(QueryRunner::class);
        $runner->useQuery($sql);
        $runner->withValues($values);

        try {
            $stmt = $runner->run();
        } catch (Exception $e) {
            throw new Exception("There was a problem loading user with id: $id. Error: " . $e->getMessage() . " uuid: ed9f7af1-92b1-4535-900f-5368c3fa6994", 500);
        }

        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    private function sendNotification($obj, ServerRequestInterface $request)
    {

        $configs = $this->container->get('config');
        $buffer = [];
        $buffer[] = $configs->get("paths.assets");
        $buffer[] = 'emails';
        $templatePath = implode("/", $buffer) . 'SignupService.php/';
        $Notif = new Notif();
        $Notif->setTemplateDirectory($templatePath);
        $Notif->loadTemplate('activation');
        $Notif->addFart("UUID", $obj->nonce);
        $Notif->addFart("DOMAIN", $request->getServerParams()['SERVER_NAME']);

        $Notif->addFart('FIRSTNAME', $obj->first);
        $Notif->render();

        // To send HTML mail, the Content-type header must be set
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html';
        $headers[] = 'X-Notif: support-hph';

        // Additional headers
        $headers[] = 'From: no-reply <teamhph123@gmail.com>';
        $headers[] = 'Bcc: diana99dimitrova@gmail.com';

        $message = $Notif->body;
        $to = $obj->email;
        $subject = '[ ACTION REQUIRED ] - Account activation for Support HPH';
//         Mail it
        mail($to, $subject, $message, implode("\r\n", $headers));
    }
}
