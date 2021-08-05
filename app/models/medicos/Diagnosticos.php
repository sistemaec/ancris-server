<?php

namespace Pointerp\Modelos\Medicos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;
use Pointerp\Modelos\Medicos\Especialidades;

class Diagnosticos extends Modelo {
  public function initialize() {
    $this->setSource('mdiagnosticos');

    $this->hasOne('categoria', MdTablasRegistros::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relDiagnostico',
    ]);
  }

  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relDiagnostico != null) {   
      $res['relDiagnostico'] = $this->relDiagnostico->toArray();
    }
  }
}