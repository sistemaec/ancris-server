<?php

namespace Pointerp\Modelos\Medicos;

use Phalcon\Mvc\Model;
use Pointerp\Modelos\Modelo;
use Pointerp\Modelos\Medicos\Pacientes;
use Pointerp\Modelos\Medicos\Medicos;
use Pointerp\Modelos\Medicos\RecetaItems;

class Consultas extends Modelo {
  public function initialize() {
    $this->setSource('mdconsultas');

    $this->hasOne('paciente_id', Pacientes::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relPaciente',
    ]);
    $this->hasOne('medico_id', Medicos::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relMedico',
    ]);
    $this->hasOne('diagnostico_clase', Diagnosticos::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relDiagnostico',
    ]);
    $this->hasOne('servicio_id', Servicios::class, 'id', [
      'reusable' => true, // cache
      'alias'    => 'relServicio',
    ]);
    $this->hasMany('id', RecetaItems::class, 'consulta_id',
    [
      'reusable' => true,
      'alias'    => 'recetaItems'
    ]);
    $this->hasMany('id', Examenes::class, 'consulta_id',
    [
      'reusable' => true,
      'alias'    => 'relExamenes'
    ]);
  }

  public function jsonSerialize () : array {
    $res = $this->toArray();
    if ($this->relMedico != null) {   
      $res['relMedico'] = $this->relMedico->toArray();
    }
    if ($this->relPaciente != null) {
      $res['relPaciente'] = $this->relPaciente->toArray();
      if ($this->relPaciente->relCliente != null) {
        $res['relPaciente']['relCliente'] = $this->relPaciente->relCliente->toArray();
      }
    }
    if ($this->relDiagnostico != null) {   
      $res['relDiagnostico'] = $this->relDiagnostico->toArray();
    }
    if ($this->relServicio != null) {   
      $res['relServicio'] = $this->relServicio->toArray();
      if ($this->relServicio->relEspecialidad != null) {
        $res['relServicio']['relEspecialidad'] = $this->relServicio->relEspecialidad->toArray();
      }
    }
    if ($this->recetaItems != null) {   
      $items = [];
      foreach ($this->recetaItems as $it) {
        $ins = $it->toArray();
        if ($it->relProducto != null) {
          $ins['relProducto'] = $it->relProducto->toArray();
        }
        array_push($items, $ins);
      }
      $res['recetaItems'] = $items;
    }
    if ($this->relExamenes != null) {   
      $res['relExamenes'] = $this->relExamenes->toArray();
    }
    return $res;
  }
}