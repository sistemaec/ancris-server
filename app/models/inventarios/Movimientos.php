<?php

namespace Pointerp\Modelos\Inventarios;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;
use Pointerp\Modelos\inventarios\Bodegas;
use Pointerp\Modelos\inventarios\MovimientosItems;

class Movimientos extends Modelo
{
  public function initialize() {
    $this->setSource('movimientos');

    $this->hasOne('bodega_id', Bodegas::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relBodega',
    ]);
    $this->hasMany('id', MovimientosItems::class, 'movimiento_id',
    [
      'reusable' => true,
      'alias'    => 'relItems'
    ]);
  }
  
  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relBodega != null) {   
      $res['relBodega'] = $this->relBodega->toArray();
    }

    if ($this->relItems != null) {   
      $items = [];
      foreach ($this->relItems as $it) {
        if ($it->relProducto != null) {
          $ins = $it->toArray();
          $ins['relProducto'] = $it->relProducto->toArray();
          array_push($items, $ins);
        }
      }
      $res['relItems'] = $items;
    }
    return $res;
  }
}
