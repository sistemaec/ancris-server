<?php

namespace Pointerp\Modelos\Ventas;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;
use Pointerp\Modelos\Maestros\Productos;

class VentasItems extends Modelo {
  public function initialize() {
    $this->setSource('ventas_items');

    $this->hasOne('producto_id', Productos::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relProducto',
    ]);
  }

  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relProducto != null) {   
      $res['relProducto'] = $this->relProducto->toArray();
    }
    return $res;
  } 
}