<?php

use League\Config\Configuration;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

function getSystemLogger(Configuration $configs) {

    if($configs->get('logging.system.enabled') == false) return nullLogging();

    switch('logging.system.level') {
        case 'debug':
            //return something else.
        default:
            return systemLogging();
    }
}

function getFinancialLogger(Configuration $configs) {

    if($configs->get('logging.system.enabled') == false) return nullLogging();

    return financialLogger();
}

function nullLogging() : Logger {
    $log = new Logger('name');
    $log->pushHandler(new NullHandler());
    return $log;
}

/**
 * @return \Monolog\Logger
 */
function systemLogging(): Logger
{
// the default date format is "Y-m-d\TH:i:sP"
    $dateFormat = "Y-m-d g:i A";

    // the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
    // we now change the default output format according our needs.
    $output = "%datetime% [%level_name%] - %message% %context% %extra%\n";

    // finally, create a formatter
    $formatter = new LineFormatter($output, $dateFormat);

    $log = new Logger('SystemLogger');
    $log->pushHandler(new StreamHandler('build/logs/runtime/debug.log', Logger::DEBUG));
    $log->pushHandler(new StreamHandler('build/logs/runtime/info.log', Logger::INFO));
    $log->pushHandler(new StreamHandler('build/logs/runtime/notice.log', Logger::NOTICE));
    $log->pushHandler(new StreamHandler('build/logs/runtime/warn.log', Logger::WARNING));
    $log->pushHandler(new StreamHandler('build/logs/runtime/error.log', Logger::ERROR));
    $log->pushHandler(new StreamHandler('build/logs/runtime/critical.log', Logger::CRITICAL));
    $log->pushHandler(new StreamHandler('build/logs/runtime/alert.log', Logger::ALERT));
    $log->pushHandler(new StreamHandler('build/logs/runtime/EMERGENCY.log', Logger::EMERGENCY));

    foreach ($log->getHandlers() as $handler) {
        $handler->setFormatter($formatter);
    }
    return $log;
}

function financialLogger() : Logger {
    // the default date format is "Y-m-d\TH:i:sP"
    $dateFormat = "Y-m-d g:i A";

    // the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
    // we now change the default output format according our needs.
    $output = "%datetime% [%level_name%] - %message% %context% %extra%\n";

    // finally, create a formatter
    $formatter = new LineFormatter($output, $dateFormat);

    $log = new Logger('FinancialLogger');
    $log->pushHandler(new StreamHandler('build/logs/runtime/financial.log', Logger::DEBUG));

    foreach ($log->getHandlers() as $handler) {
        $handler->setFormatter($formatter);
    }
    return $log;
}
