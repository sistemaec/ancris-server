<?php

namespace Pointerp\Modelos\Medicos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Usuarios;
use Pointerp\Modelos\Medicos\MedicosEspecialidades;
use Pointerp\Modelos\Modelo;

class Medicos extends Modelo {
  public function initialize() {
    $this->setSource('medicos');

    $this->hasOne('usuario_id', Usuarios::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relUsuario',
    ]);

    $this->hasMany('id', MedicosEspecialidades::class, 'medico_id',
    [
      'reusable' => true,
      'alias'    => 'relEspecialidades'
    ]);
  }

  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relUsuario != null) {   
      $res['relUsuario'] = $this->relUsuario->toArray();
    }

    if ($this->relEspecialidades != null) {   
      $items = [];
      foreach ($this->relEspecialidades as $it) {
        $ins = $it->toArray();
        if ($it->relEspecialidad != null) {
          $ins['relEspecialidad'] = $it->relEspecialidad->toArray();
        }
        array_push($items, $ins);
      }
      $res['relEspecialidades'] = $items;
    }

    return $res;
  }
}