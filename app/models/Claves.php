<?php

namespace Pointerp\Modelos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;

class Claves extends Modelo
{
    public function initialize()
    {
        $this->setSource('claves');
    }   
}