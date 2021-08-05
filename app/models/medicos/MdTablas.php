<?php

namespace Pointerp\Modelos\Medicos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;

class MdTablas extends Modelo {
  public function initialize() {
    $this->setSource('mdtablas');
  }
}