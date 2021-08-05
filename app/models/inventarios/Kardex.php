<?php

namespace Pointerp\Modelos\Inventarios;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;
use Pointerp\Modelos\Maestros\Productos;
use Pointerp\Modelos\inventarios\Bodegas;

class Kardex extends Modelo {
  public function initialize() {
    $this->setSource('kardex');

    $this->hasOne('producto_id', Productos::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relProducto',
    ]);
    $this->hasOne('bodega_id', Bodegas::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relBodega',
    ]);
  }

  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relProducto != null) {
      $res['relProducto'] = $this->relProducto->toArray();
    }
    if ($this->relBodega != null) {
      $res['relBodega'] = $this->relBodega->toArray();
    }
    return $res;
  }
}