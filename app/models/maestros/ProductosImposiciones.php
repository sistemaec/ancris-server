<?php

namespace Pointerp\Modelos\Maestros;

use Phalcon\Mvc\Model;

class ProductosImposiciones extends Modelo {
  public function initialize() {
    $this->setSource('productos_imposiciones');
    
    $this->hasOne('impuesto_id', Impuestos::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relImpuesto',
    ]);
  }

  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relImpuesto != null) {   
      $res['relImpuesto'] = $this->relImpuesto->toArray();
    }
    return $res;
  }

}