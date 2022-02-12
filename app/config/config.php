<?php

/*
 * Modified: prepend directory path of current file, because of this file own different ENV under between Apache and command line.
 * NOTE: please remove this comment.
 */
defined('BASE_PATH') || define('BASE_PATH', getenv('BASE_PATH') ?: realpath(dirname(__FILE__) . '/../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');

return new \Phalcon\Config([
    'database' => [
        'adapter'     => 'Mysql',
        'host'        => '107.180.27.234', //remotemysql.com
        'username'    => 'daikoapp', // aRmvBqS4iI poinspve_iceq
        'password'    => 'bp#pn2g~nFm-', // sZbk7mlQ3T Caricatur@55
        'dbname'      => 'daikores', // aRmvBqS4iI poinspve_ancris
        'charset'     => 'utf8',
        'port'        => 3306
    ],
    'application' => [
        'appDir'         => APP_PATH . '/',
        'controllersDir' => APP_PATH . '/controllers/',
        'modelsDir'      => APP_PATH . '/models/',
        'modMaestrosDir' => APP_PATH . '/models/maestros',
        'modMedicosDir'  => APP_PATH . '/models/medicos',
        'modInventariosDir' => APP_PATH . '/models/inventarios',
        'migrationsDir'  => APP_PATH . '/migrations/',
        'viewsDir'       => APP_PATH . '/views/',
        'libraryDir'     => APP_PATH . '/library/',
        'rutasDir'       => APP_PATH . '/rutas/',
        'cacheDir'       => BASE_PATH . '/cache/',
        'baseUri'        => '/',
    ],
    'entorno' => [
        'origen'        => '*',
        'tokenDuracion' => 12,
        'tokenSize'     => 16,
    ],
    'cors' => [
        'origen' => '*',
        'exposedHeaders' => [],
        // Should be in lowercases.
        'allowedHeaders' => ['x-requested-with', 'content-type', 'authorization'],
        // Should be in uppercase.
        'allowedMethods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        // Requests originating from here can entertain CORS.
        'allowedOrigins' => [
            '*',
        ],
        // Cache preflight for 7 days (expressed in seconds).
        'maxAge'         => 604800,
    ],
]);
