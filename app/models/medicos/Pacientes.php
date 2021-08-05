<?php

namespace Pointerp\Modelos\Medicos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;
use Pointerp\Modelos\Maestros\Clientes;
use Pointerp\Modelos\Medicos\MdTablasRegistros;

class Pacientes extends Modelo {
  public function initialize() {
    $this->setSource('pacientes');

    $this->hasOne('cliente_id', Clientes::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relCliente',
    ]);
    $this->hasOne('sexo', MdTablasRegistros::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relSexo',
    ]);
    $this->hasOne('estado_civil', MdTablasRegistros::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relEstadoCivil',
    ]);
    $this->hasOne('grupo_sanguineo', MdTablasRegistros::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relGrupoSanguineo',
    ]);
  }

  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relCliente != null) {   
      $res['relCliente'] = $this->relCliente->toArray();
    }
    if ($this->relSexo != null) {   
      $res['relSexo'] = $this->relSexo->toArray();
    }
    if ($this->relEstadoCivil != null) {   
      $res['relEstadoCivil'] = $this->relEstadoCivil->toArray();
    }
    if ($this->relGrupoSanguineo != null) {   
      $res['relGrupoSanguineo'] = $this->relGrupoSanguineo->toArray();
    }
    return $res;
  }

}