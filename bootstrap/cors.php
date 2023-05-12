<?php

function handleCors($config) {

    if(php_sapi_name() == 'cli') return false;

    if($config->get("security.cors.enabled") == false ) return false;

    // Allow from any origin
//    echo "<pre>";
//    var_dump($config->get("security.cors.allowed_origins"));
//    echo "</pre>";
//    die(__FILE__ . ":" . __LINE__);

    if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $config->get("security.cors.allowed_origins"))) {

        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
        // you want to allow, and if so:
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

    /**
     * Removed to allow JsonStrategy in the router to auto-generate OPTIONS responses.
     * By: Michael Munger <mj@hph.io> - 2022-09-12 @ 10:00

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {

            // may also be using PUT, PATCH, HEAD etc
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH");
        }

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {

            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }

        return true;
    }
     * */

    return false;
}
