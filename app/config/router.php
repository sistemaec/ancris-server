<?php

use Phalcon\Di;
use Phalcon\Mvc\Router\Group;
use Pointerp\Rutas\SeguridadRutas;
use Pointerp\Rutas\MedicosRutas;
use Pointerp\Rutas\AjustesRutas;
use Pointerp\Rutas\InventariosRutas;
use Pointerp\Rutas\MaestrosRutas;
use Pointerp\Rutas\VentasRutas;
use Pointerp\Rutas\CorsRutas;

$router = $di->getRouter();
$router->setDefaultNamespace('Pointerp\Controladores');

$router->mount(new SeguridadRutas());
$router->mount(new CorsRutas());
$router->mount(new MedicosRutas());
$router->mount(new AjustesRutas());
$router->mount(new InventariosRutas());
$router->mount(new MaestrosRutas());
$router->mount(new VentasRutas());

$router->addGet('/prueba/{texto}', [
  'controller' => 'seguridad',
  'action'     => 'prueba',
]);

$router->handle($_SERVER['REQUEST_URI']);
