<?php

use Erc\Api\Auth\Authenticator;
use Laminas\Diactoros\ServerRequestFactory;

function ercErrorHandler($errno, $errstr, $errfile, $errline) {
    list($request, $payload) = getEnvironment();
    $data = getUserInfo($request, $payload);

    $data[] = "Error Number: " . $errno;
    $data[] = sprintf("Location: %s:%s", $errfile, $errline);
    $data[] = "Error Information";
    $data[] = str_replace('\n', "\n", $errstr);

    try {
        $data[] = 'Request body: ' . $request->getBody()->getContents();
    } catch (Exception $e) {
        $data[] = 'Exception handled while attempting to get the request body: ' . $e->getMessage();
    }

    $data[] = '<$_FILES>';
    $data[] = print_r($_FILES, true);
    $data[] = '<$_FILES>';

    $errorData = implode("\n",$data);


    sendErrorReport($errorData);

}

/**
 * @param $request
 * @param $payload
 * @return array
 */
function getUserInfo($request, $payload): array
{
    $data = [];
    $data[] = sprintf("%s %s", $request->getMethod(), $request->getUri());
    $data[] = sprintf("IP: %s", $_SERVER['REMOTE_ADDR']);
    $data[] = sprintf("UserAgent: %s", $_SERVER['HTTP_USER_AGENT']);
    $data[] = "-----Token-----";
    $data[] = "sub: " . $payload->sub;
    $data[] = "User: " . $payload->user;
    $data[] = "User ID: " . $payload->user_id;
    $data[] = "Current User: " . $payload->current_user;
    $data[] = "Current User ID: " . $payload->current_user_id;
    $data[] = "iat: " . $payload->iat;
    $data[] = "nbf: " . $payload->nbf;
    $data[] = "exp: " . $payload->exp;
    $data[] = "jti: " . $payload->jti;
    $data[] = "-----/Token-----";
    $data[] = "";
    return $data;
}

/**
 * @return array
 * @throws \Psr\Container\ContainerExceptionInterface
 * @throws \Psr\Container\NotFoundExceptionInterface
 */
function getEnvironment(): array
{
    $errorLog = "/home/erc-beta/log/error_handler.log";

    $config_values = null;
    $configPath = dirname(__DIR__) . '/config/config.php';
//    error_log($configPath, 3, $errorLog);

    include($configPath);

    $container = getContainer($config_values);

    $request = ServerRequestFactory::fromGlobals(
        $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
    );


    $payload = [];

    if($request->getHeader('Authorization')) {
//        $current_user = $container->get('current_user');
        $auth = $container->get(Authenticator::class);
        $payload = $auth->verifyToken($request);
    }

    return array($request, $payload);
}

/**
 * @param string $errorData
 * @return void
 */
function sendErrorReport(string $errorData): void
{
    $file = tempnam('/tmp/', 'error_');
    $fh = fopen($file, 'w');
    fwrite($fh, $errorData);
    fclose($fh);

    $content = file_get_contents($file);
    $content = chunk_split(base64_encode($content));
    $uid = md5(uniqid(time()));
    $name = basename($file);

    // Sender's name
    $from_name = "ERC Errors";

    // Sender's email
    $from_mail = "errors@" . $_SERVER['SERVER_NAME'];

    // Recipient's email
    $reply_to = "no-reply@" . $_SERVER['SERVER_NAME'];

    // Recipient's email
    $mail_to = "erc-errors@hph.io";

    // Email Subject
    $subject = "Error on " . $_SERVER['SERVER_NAME'];

    $body = $errorData;

    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type:text/plain; charset=iso-8859-1";
    $headers[] = "X-Notif: erc-errors";
    $headers[] = sprintf("From: %s <%s>", $from_name, $from_mail);


    mail($mail_to, $subject, $body, implode("\r\n", $headers));
}

function ercExceptionHandler(Throwable $ex) {
    list($request, $payload) = getEnvironment();
    $data = getUserInfo($request, $payload);

    $data[] = "Exception Type: " . get_class($ex);
    $data[] = "Message: " . $ex->getMessage();
    $data[] = sprintf("In: %s:%s", $ex->getFile(), $ex->getLine());
    $data[] = '';
    $data = $data + $ex->getTrace();
    $data[] = '';

    try {
        $data[] = 'Request body: ' . $request->getBody()->getContents();
    } catch (Exception $e) {
        $data[] = 'Exception handled while attempting to get the request body: ' . $e->getMessage();
    }

    $errorData = implode("\n",$data);
    $errorData .= $ex->getCode();

    sendErrorReport($errorData);
}

function catchFatalErrors()
{
    $error = error_get_last();
    if ($error == null) return;
    if ($error["type"] == E_ERROR)
        ercErrorHandler($error["type"], $error["message"], $error["file"], $error["line"]);
}

function setupHandler($config_values) {
    if(php_sapi_name() === 'cli') return;
    $config = getConfigs($config_values);
    if($config->get('errors.usehandler')) {
        register_shutdown_function("catchFatalErrors");
        set_error_handler( "ercErrorHandler" );
        set_exception_handler( "ercExceptionHandler" );
    }
}

setupHandler(getConfigValues());
