<?php

$GLOBALS['appRoot'] = dirname(__DIR__ );

$bootstrapFiles = [];

$bootstrapFiles[] = $GLOBALS['appRoot'] . '/vendor/autoload.php';
$bootstrapFiles[] = $GLOBALS['appRoot'] . '/bootstrap/config-schema.php';
$bootstrapFiles[] = $GLOBALS['appRoot'] . '/bootstrap/container.php';
$bootstrapFiles[] = $GLOBALS['appRoot'] . '/config/config.php';
$bootstrapFiles[] = $GLOBALS['appRoot'] . '/bootstrap/error_handling.php';
$bootstrapFiles[] = $GLOBALS['appRoot'] . '/bootstrap/cors.php';
$bootstrapFiles[] = $GLOBALS['appRoot'] . '/bootstrap/router.php';
$bootstrapFiles[] = $GLOBALS['appRoot'] . '/bootstrap/logging.php';


foreach($bootstrapFiles as $file) {
    if(!file_exists($file)) throw new Exception("Could not include $file because the file is not found.");
    include($file);
}
