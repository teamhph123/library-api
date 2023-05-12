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
use hph\Farret\Notif;
use Hph\RandomGenerator;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PasswordResetService extends ApiService
{

    public function __invoke(ServerRequestInterface $request, array $routeArgs = []): ResponseInterface
    {
        $requestobject = json_decode((string)$request->getBody(), true);
        $email = filter_var($requestobject['email'], FILTER_VALIDATE_EMAIL);
        if ($this->checkEmail($email)==0){

            return new JsonResponse(['error' => "This e-mail address is not registered in the system"], 404);
        }

        $nonce = $this->container->get(RandomGenerator::class)->uuidv4();
        $sql = "UPDATE users SET nonce = :nonce WHERE email = :email";
        $values = [
            'nonce' => $nonce,
            'email' => $email
        ];
        $stmt = $this->container->get('db')->prepare($sql);
        $stmt->execute($values);
        if($stmt->errorCode() != '00000') throw new \Exception(sprintf("Database error(%s): %s", $stmt->errorCode(), $stmt->errorInfo()[2]));
        $this->sendNotification($email, $nonce);

        return new EmptyResponse(200);
    }

    private function sendNotification($email, $nonce)
    {
        $configs = $this->container->get('config');
        $buffer = [];
        $buffer[] = $configs->get("paths.assets");
        $buffer[] = 'emails';
        $templatePath = implode("/", $buffer) . '/';

        $Notif = new Notif();
        $Notif->setTemplateDirectory($templatePath);
        $Notif->loadTemplate('password-reset');
        $Notif->addFart("UUID", $nonce);
        $Notif->render();

        // To send HTML mail, the Content-type header must be set
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html';
        $headers[] = 'X-Notif: patriot-erc';

        // Additional headers
        $headers[] = 'From: no-reply <no-reply@patriotdataprocessing.com>';
        $headers[] = 'Bcc: mike@frascogna.com, mj@hph.io';

        $message = $Notif->body;
        $to = $email;
        $subject = '[ ACTION REQUIRED ] - Password reset for Patriot Data Processing';

        // Mail it
        mail($to, $subject, $message, implode("\r\n", $headers));
    }

    private function checkEmail($email)
    {
        $sql = "SELECT email FROM users WHERE email = :email";
        $values = [
            'email' => $email
        ];
        $stmt = $this->container->get('db')->prepare($sql);
        $stmt->execute($values);
        return $stmt->rowCount();
    }
}
