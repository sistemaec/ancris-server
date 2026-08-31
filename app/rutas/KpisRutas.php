<?php

namespace Pointerp\Rutas;

class KpisRutas extends \Phalcon\Mvc\Router\Group
{
  public function initialize()
  {
    $controlador = 'kpis';
    $this->setPaths(['namespace' => 'Pointerp\Controladores',]);
    $this->setPrefix('/api/v4/kpis');

    $this->addGet('/dashboard/desde/{desde:[0-9\-]+}/hasta/{hasta:[0-9\-]+}', [
      'controller' => $controlador,
      'action'     => 'kpis',
    ]);
  }
}
