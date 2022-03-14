<?php

namespace Pointerp\Modelos\Medicos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Medicos\Especialidades;
use Pointerp\Modelos\Modelo;

class MedicosEspecialidades extends Modelo {
  public function initialize() {
    $this->setSource('medicos_especalidades'); // falto la i en medicos_espec_i_alidades

    $this->hasOne('especialidad_id', Especialidades::class, 'id', [
        'reusable' => true, // cache
        'alias'    => 'relEspecialidad',
      ]);
  }

  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relEspecialidad != null) {   
      $res['relEspecialidad'] = $this->relEspecialidad->toArray();
    }
    return $res;
  }
}
