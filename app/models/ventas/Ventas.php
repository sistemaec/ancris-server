<?php

namespace Pointerp\Modelos\Ventas;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;
use Pointerp\Modelos\Sucursales;
use Pointerp\Modelos\Maestros\Clientes;
use Pointerp\Modelos\Inventarios\Bodegas;
use Pointerp\Modelos\Ventas\VentasItems;

class Ventas extends Modelo
{
  public function initialize() {
    $this->setSource('ventas');

    $this->hasOne('sucursal_id', Sucursales::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relSucursal',
    ]);
    $this->hasOne('movimiento_id', Bodegas::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relBodega',
    ]);
    $this->hasOne('cliente_id', Clientes::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relCliente',
    ]);
    $this->hasMany('id', VentasItems::class, 'venta_id',
    [
      'reusable' => true,
      'alias'    => 'relItems'
    ]);
  }
  
  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relSucursal != null) {   
      $res['relSucursal'] = $this->relSucursal->toArray();
    }
    if ($this->relBodega != null) {   
      $res['relBodega'] = $this->relBodega->toArray();
    }
    if ($this->relCliente != null) {   
      $res['relCliente'] = $this->relCliente->toArray();
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
