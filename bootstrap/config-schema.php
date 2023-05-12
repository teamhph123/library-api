<?php

use League\Config\Configuration;
use Nette\Schema\Expect;

function getConfigs($config_values) : Configuration {
    $config = new Configuration([
        'database' => Expect::structure([
            'host' => Expect::string()->default('localhost'),
            'port' => Expect::int()->min(1)->max(65535),
            'database' => Expect::string()->required(),
            'username' => Expect::string()->required(),
            'password' => Expect::string()->nullable()
        ]),
        'crypto' => Expect::structure([
            'key' => Expect::string()
        ]),
        'logging' => Expect::structure([
            'system' => Expect::structure([
                'enabled' => Expect::bool()->default(false),
                'level' => Expect::string(),
                'path' => Expect::string()
            ]),
            'financial' => Expect::structure([
                'enabled' => Expect::bool()->default(false),
                'level' => Expect::string(),
                'path' => Expect::string()
            ]),
        ]),
        'authentication' => Expect::structure([
            'expiry' => Expect::string()->default('P1M')
        ]),
        'security' => Expect::structure([
            'cors' => Expect::structure([
                'enabled' => Expect::bool()->default(false),
                'allowed_origins' => Expect::arrayOf('string')
            ])
        ]),
        'backup' => Expect::structure([
            'path' => Expect::string(),
            'index' => Expect::string()
        ]),
        'uploads' => Expect::structure([
            'path' => Expect::string()
        ]),
        'paths' => Expect::structure([
            'uploads' => Expect::string(),
            'downloads' => Expect::string(),
            'assets' => Expect::string(),
            'homedir' => Expect::string(),
            'approot' => Expect::string()
        ]),
        'errors' => Expect::structure([
            'usehandler' => Expect::bool()
        ])
    ]);
    
    $config->merge($config_values);
    return $config;
}
