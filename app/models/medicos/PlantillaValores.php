<?php

namespace Pointerp\Modelos\Medicos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;

class PlantillaValores extends Modelo {
  public function initialize() {
    $this->setSource('mdiplantilla_valores');
  }
}
