<?php
$loader = new \Phalcon\Loader();

$dirs = [
    $config->application->controllersDir,
    $config->application->modelsDir,
    $config->application->modMaestrosDir,
    $config->application->modMedicosDir,
    $config->application->modInventariosDir,
    $config->application->rutasDir,
    $config->application->libraryDir,
];

$names = [
    'Pointerp\Controladores' => '../app/controllers/',
    'Pointerp\Modelos' => '../app/models/',
    'Pointerp\Modelos\Medicos' => '../app/models/medicos',
    'Pointerp\Modelos\Maestros' => '../app/models/maestros',
    'Pointerp\Modelos\Inventarios' => '../app/models/inventarios',
    'Pointerp\Modelos\Ventas' => '../app/models/ventas',
    'Pointerp\Rutas' => '../app/rutas/',
    'Pointerp\Library' => '../app/library/',
];

/**
 * Se registran los directorios y nombres tomados del archivo de configuracion
 */
$loader->registerDirs($dirs);
$loader->registerNamespaces($names, true);

$loader->register();
