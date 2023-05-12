<?php

use Hph\Auth\Login;
use Hph\Auth\UserNav;
use Hph\Models\User;
use Hph\Models\UserToken;
use Hph\Models\UserTokenBase;
use Hph\Nonce\NonceService;
use Hph\Users\PasswordResetService;
use Hph\RandomGenerator;
use Hph\QueryRunner;
use Laminas\Diactoros\ResponseFactory;
use League\Container\Container;
use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;
use League\Route\Strategy\JsonStrategy;
use Library\Api\Signup\SignupService;
use Library\LibraryApiService;

function getDatabaseConnection(Container $container): PDO
{
    $config = $container->get('config');

    $dsn = sprintf('mysql:dbname=%s;host=%s', $config->get("database.database"), $config->get("database.host"));

    try {
        $dbh = new PDO($dsn, $config->get("database.username"), $config->get("database.password"), [PDO::MYSQL_ATTR_LOCAL_INFILE => true]);
        $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        throw new Exception("Connection failed: " . $e->getMessage());
    }

    return $dbh;

}

/**
 * @param $config_values
 * @return \League\Container\Container
 * @throws \Exception
 */
function getContainer($config_values)
{

    $responseFactory = new ResponseFactory();
    $container = new Container();

    try {

        $configs = getConfigs($config_values);
        $container->add('config', $configs);
        $container->add('db', getDatabaseConnection($container));

        $container->add(ApplicationStrategy::class);
        $container->add(JsonStrategy::class)->addArgument($responseFactory);
        $container->add(Router::class);
//        $container->add(Reader::class);
//        $container->add(Writer::class);

        $container->add(RandomGenerator::class);
        $container->add(DateTime::class);
        $container->add(QueryRunner::class)->addArgument($container);

        /* API services */
        $container->add(LibraryApiService::class)->addArgument($container);

        /* Auth services */
        $container->add(NonceService::class)->addArgument($container);
        $container->add(Login::class)->addArgument($container);
        $container->add(User::class)->addArgument($container);
        $container->add(UserToken::class)->addArgument($container);
        $container->add(UserTokenBase::class)->addArgument($container);
        $container->add(UserNav::class)->addArgument($container);

        /* <signup> */
        $container->add(SignupService::class)->addArgument($container);
        /* <signup> */

        /* <password_management> */
        $container->add(PasswordResetService::class)->addArgument($container);
        /* </password_management> */


    } catch (Exception $e) {
        echo "<pre>";
        echo "Container problem. Error ({$e->getCode()}) . {$e->getMessage()} in file {$e->getFile()}:{$e->getLine()}";
        echo "Trace:";
        var_dump($e->getTraceAsString());
        echo "</pre>";
        die(__FILE__ . ":" . __LINE__);
    }


    return $container;
}
