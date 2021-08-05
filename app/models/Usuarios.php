<?php

namespace Pointerp\Modelos;

use Phalcon\Mvc\Model;

class Usuarios extends Modelo
{
    public function initialize()
    {
        $this->setSource('usuarios');

        $this->hasOne('rol_id', Roles::class, 'id', [
            'reusable' => true, // cache
            'alias'    => 'relRol',
          ]);
    }

    public function jsonSerialize () : array {
        $res = $this->toArray();
        if ($this->relRol != null) {   
          $res['relRol'] = $this->relRol->toArray();
        }
        return $res;
    }
}