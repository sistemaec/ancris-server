<?php

namespace Pointerp\Modelos\Medicos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Usuarios;
use Pointerp\Modelos\Modelo;

class Medicos extends Modelo {
  public function initialize() {
    $this->setSource('medicos');

    $this->hasOne('usuario_id', Usuarios::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relUsuario',
    ]);
  }

  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relUsuario != null) {   
      $res['relUsuario'] = $this->relUsuario->toArray();
    }
    return $res;
  }
}