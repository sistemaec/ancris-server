<?php

namespace Pointerp\Modelos\Medicos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;

class Especialidades extends Modelo {
  public function initialize() {
    $this->setSource('mdespecialidades');
  }
}