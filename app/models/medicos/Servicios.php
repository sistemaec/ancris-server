<?php

namespace Pointerp\Modelos\Medicos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;
use Pointerp\Modelos\Medicos\Especialidades;
use Pointerp\Modelos\Maestros\Productos;

class Servicios extends Modelo {
  public function initialize() {
    $this->setSource('mdservicios');

    $this->hasOne('especialidad_id', Especialidades::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relEspecialidad',
    ]);
    $this->hasOne('producto_id', Productos::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relProducto',
    ]);
  }

  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relEspecialidad != null) {   
      $res['relEspecialidad'] = $this->relEspecialidad->toArray();
    }
    if ($this->relProducto != null) {   
      $res['relProducto'] = $this->relProducto->toArray();
    }
    return $res;
  }
}
