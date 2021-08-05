<?php

namespace Pointerp\Modelos\Maestros;

use Phalcon\Mvc\Model;

class ProductosPrecios extends Modelo {
  public function initialize() {
    $this->setSource('productos_precios');
  }
}