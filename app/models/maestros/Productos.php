<?php

namespace Pointerp\Modelos\Maestros;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;
use Pointerp\Modelos\Maestros\Registros;
use Pointerp\Modelos\Maestros\ProductosPrecios;
use Pointerp\Modelos\Maestros\ProductosImposiciones;

class Productos extends Modelo {
  
  public function initialize() {
    $this->setSource('productos');

    $this->hasOne('grupo', Registros::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relCategoria',
    ]);
    $this->hasOne('tipo', Registros::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relTipo',
    ]);

    $this->hasMany('id', ProductosPrecios::class, 'producto_id',
      [
        'reusable' => true,
        'alias'    => 'relPrecios'
      ]
    );

    $this->hasMany('id', ProductosImposiciones::class, 'producto_id',
      [
        'reusable' => true,
        'alias'    => 'relImposiciones'
      ]
    );
  }

  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relCategoria != null) {   
      $res['relCategoria'] = $this->relCategoria->toArray();
    }
    if ($this->relTipo != null) {   
      $res['relTipo'] = $this->relTipo->toArray();
    }

    return $res;
  }
}