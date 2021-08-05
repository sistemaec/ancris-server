<?php

namespace Pointerp\Modelos\Medicos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;

class Examenes extends Modelo {
  public function initialize() {
    $this->setSource('mdconsultas_examenes');
  }
}