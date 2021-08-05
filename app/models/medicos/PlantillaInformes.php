<?php

namespace Pointerp\Modelos\Medicos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;
use Pointerp\Modelos\Medicos\PlantillaCampos;

class PlantillaInformes extends Modelo {
  public function initialize() {
    $this->setSource('mdinformes_plantillas');

    $this->hasMany('id', PlantillaCampos::class, 'plantilla_id', [
      'reusable' => true,
      'alias'    => 'relCampos'
    ]);
  }

  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relCampos != null) {   
      $res['relCampos'] = $this->relCampos->toArray();
    }
    return $res;
  }
}
