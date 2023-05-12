<?php
/**
 * Author: Michael Munger
 * Date: 2022-01-01
 */

include('allowed_origins.php');

function getConfigValues(): array
{

    $homedir = posix_getpwuid(posix_getuid())['dir'];

    $configValues = [];
    $configValues = array_merge($configValues, getDatabaseConfig());
    $configValues = array_merge($configValues, getCryptoConfig());
    $configValues = array_merge($configValues, getLoggingConfig());
    $configValues = array_merge($configValues, getAuthenticationConfig());
    $configValues = array_merge($configValues, getSecurityConfig());
    $configValues = array_merge($configValues, getBackupConfig($homedir));
    $configValues = array_merge($configValues, getUploadsConfig($homedir));
    $configValues = array_merge($configValues, getPathsConfig($homedir));
    $configValues = array_merge($configValues, getErrorsConfig());

    return $configValues;
}

function getLoggingConfig(): array
{

    $logging_config = [];
    $logging_config = array_merge($logging_config, getSystemLoggingConfig());
    $logging_config = array_merge($logging_config, getFinancialLoggingConfig());

    return ['logging' => $logging_config];
}

function getFinancialLoggingConfig(): array
{
    return ['financial' =>
        [
            'enabled' => false,
            'level' => 'debug',
            'path' => dirname(__DIR__) . '/build/logs/financial.log'
        ]
    ];
}

function getSystemLoggingConfig(): array
{
    return ['system' =>
        [
            'enabled' => false,
            'level' => 'debug',
            'path' => dirname(__DIR__) . '/build/logs/system.log'
        ]
    ];
}

function getCryptoConfig(): array
{
    return [
        'crypto' => [
            'key' => 'io5OFoo4AiPai4ovahs3Ush8aeY0li9i'
        ]
    ];
}


function getDatabaseConfig(): array
{
    return ['database' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'library_local',
        'username' => 'root',
        'password' => 'dU94&amp;RoVSx^ITB3E'
    ],];
}

function getAuthenticationConfig(): array
{
    return ['authentication' =>
        [
            'expiry' => 'P1M',
        ]];
}

function getSecurityConfig(): array
{
    return [
        'security' => [
            'cors' => [
                'enabled' => true,
                'allowed_origins' => getCorsAllowedOrigins()
            ]
        ]
    ];
}

function getBackupConfig($homedir): array
{

    return ['backup' =>
        [
            'path' => $homedir . '/backups',
            'index' => 'backups.json'
        ]];
}

function getUploadsConfig($homedir): array
{

    return ['uploads' => [
        'path' => $homedir . "/uploads"
    ]
    ];
}

function getPathsConfig($homedir): array
{

    return ['paths' =>
        [
            'downloads' => $homedir . '/downloads',
            'uploads' => $homedir . '/uploads',
            'assets' => dirname(__DIR__) . '/assets',
            'homedir' => $homedir,
            'approot' => dirname(__DIR__)
        ]];
}

function getErrorsConfig()
{

    return ['errors' =>
        [
            'usehandler' => false
        ]];
}



//
//<?php
///**
// * Author: Michael Munger
// * Date: 1/31/22
// */
//
//$homedir = posix_getpwuid(posix_getuid())['dir'];
//
//$config_values = [
//    'database' => [
//        'host' => 'localhost',
//        'port' => 3306,
//        'database' => 'erc_local',
//        'username' => 'root',
//        'password' => 'a1C4Ufkanh7k'
//    ],
//    'crypto' => [
//        'key' => 'io5OFoo4AiPai4ovahs3Ush8aeY0li9i'
//    ],
//    'logging' => [
//        'system' => [
//            'enabled' => false,
//            'level' => 'debug',
//            'path' => dirname(__DIR__).'/build/logs/system.log'
//        ],
//        'financial' => [
//            'enabled' => false,
//            'level' => 'debug',
//            'path' => dirname(__DIR__).'/build/logs/financial.log'
//        ]
//    ],
//    'authentication' => [
//        'expiry' => 'P1M'
//    ],
//    'backup' => [
//        'path' => $homedir . '/backups',
//        'index' => 'backups.json'
//    ],
//    'uploads' => [
//        'path' => $homedir . '/uploads'
//    ],
//    'paths' => [
//        'uploads' => $homedir . '/uploads',
//        'assets' => dirname(__DIR__) . '/assets',
//        'homedir' => $homedir,
//        'approot' => dirname(__DIR__)
//    ]
//];
