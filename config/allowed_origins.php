<?php
/* This file is maintained for production for allowed origins */

function getCorsAllowedOrigins() : array {
    $pathToAllowedOrigins = $GLOBALS['appRoot'] . '/config/origins.json';
	$originObj = json_decode(file_get_contents($pathToAllowedOrigins), true);
    return $originObj['origins'];
}
