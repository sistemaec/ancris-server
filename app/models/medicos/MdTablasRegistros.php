<?php

namespace Pointerp\Modelos\Medicos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;

class MdTablasRegistros extends Modelo {
  public function initialize() {
    $this->setSource('mdtablas_registros');
  }
}