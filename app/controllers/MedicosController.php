<?php

namespace Pointerp\Controladores;

use Phalcon\Di;
use Phalcon\Mvc\Model\Query;
use Pointerp\Modelos\Medicos\Consultas;
use Pointerp\Modelos\Medicos\Pacientes;
use Pointerp\Modelos\Maestros\Clientes;
use Pointerp\Modelos\Medicos\MdTablasRegistros;
use Pointerp\Modelos\Maestros\Registros;
use Pointerp\Modelos\Medicos\Medicos;
use Pointerp\Modelos\Medicos\MedicosEspecialidades;
use Pointerp\Modelos\Medicos\Especialidades;
use Pointerp\Modelos\Medicos\Servicios;
use Pointerp\Modelos\Medicos\PlantillaInformes;
use Pointerp\Modelos\Medicos\PlantillaCampos;
use Pointerp\Modelos\Medicos\RecetaItems;
use Pointerp\Modelos\Medicos\Examenes;

class MedicosController extends ControllerBase  {

  // PACIENTES 

  public function pacientesBuscarAction() {
    $this->view->disable();
    $tipoBusca = $this->dispatcher->getParam('tipo');
    $estado = $this->dispatcher->getParam('estado');
    $filtro = $this->dispatcher->getParam('filtro');
    $empresa = $this->dispatcher->getParam('emp');
    $atrib = $this->dispatcher->getParam('atrib');
    $filtro = str_replace('%C3%91' , 'Ñ',$filtro);
    $filtro = str_replace('%C3%B1' , 'ñ',$filtro);
    $filtro = str_replace('%20', ' ', $filtro);
    if ($atrib == 0) {
      if ($tipoBusca == 0) {
        // Comenzando por
        $filtro .= '%';
      } else {
        $filtroSP = str_replace('  ', ' ',trim($filtro));
        $filtro = '%' . str_replace(' ' , '%',$filtroSP) . '%';
      }
    }

    $campo = 'upper(c.nombres) like';
    switch($atrib) {
      case 1: {
        $campo = 'c.identificacion =';
        break;
      };
      case 2: {
        $campo = 'c.codigo =';
        break;
      }
    };

    $condicion = 'c.empresa_id = :emp: AND ' . $campo . ' :fil:';
    if ($estado == 0) {
        $condicion .= ' AND p.estado = 0';
    }
    $di = Di::getDefault();
    $qry = new Query('SELECT p.* 
      FROM Pointerp\Modelos\Medicos\Pacientes p 
      LEFT JOIN Pointerp\Modelos\Maestros\Clientes c ON p.cliente_id = c.id 
      Where ' . $condicion . ' Order by c.nombres', $di 
    );
    
    $res  =  $qry->execute([
      'fil' => strtoupper($filtro),
      'emp' => $empresa
    ]);

    if ($res->count() > 0) {
      $this->response->setStatusCode(200, 'Ok');
    } else {
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function pacientePorIdAction() {
    $id = $this->dispatcher->getParam('id');
    $res = Pacientes::findFirstById($id);
    if ($res != null) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $res = [];
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function pacienteRegistradoAction() {
    $ced = $this->dispatcher->getParam('ced');
    $nom = $this->dispatcher->getParam('nom');
    $id = $this->dispatcher->getParam('id');
    $nom = str_replace('%20', ' ', $nom);
    $params = [];
    $params += [ 'nom' => $nom ];
    $condicion = 'nombres = :nom:';
    if (strlen($ced) >= 10) {
      $condicion = 'identificacion = :ced: OR ' . $condicion;
      $params += [ 'ced' => $ced ];
    }
    $rows = Clientes::find([
      'conditions' => $condicion,
      'bind' => $params
    ]);
    $existe = false;
    $res = 'Se puede registrar los nuevos datos';
    if ($rows->count() > 0) {
      $pas = Pacientes::find([
        'conditions' => 'id != :id: and cliente_id = :cli:',
        'bind' => [ 'id' => $id, 'cli' => $rows[0]->id ]
      ]);
      if ($pas->count() > 0) {
        $existe = true;
        $res = 'Estos datos ya estan registrados busquelo como ' . $rows[0]->nombres;
        $this->response->setStatusCode(406, 'Not Acceptable');
      }
    }
    if (!$existe) {
      $this->response->setStatusCode(200, 'Ok');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function pacientePorCedulaAction() {
    $ced = $this->dispatcher->getParam('ced');
    $ret = (object) [
      'res' => false,
      'cid' => 0,
      'data' => "",
      'msj' => 'No se encontro esta cedula'
    ];
    $rows = Clientes::find([
      'conditions' => 'identificacion = :ced:',
      'bind' => [ 'ced' => $ced ]
    ]);
    if ($rows->count() > 0) {
      $ret->cid = $rows[0]->id;
      $ret->data = $rows[0];
      $ret->msj = "Cliente registrado";
      $pas = Pacientes::find([
        'conditions' => 'cliente_id = :cli:',
        'bind' => [ 'cli' => $rows[0]->id ]
      ]);
      if ($pas->count() > 0) {
        $existe = true;
        $ret->res = true;
        $ret->cid = $pas[0]->id;
        $ret->data = $pas[0];
        $ret->msj = "Encontrado";
      }
      $this->response->setStatusCode(200, 'Ok');
    }  else {
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  public function cedulaRegistradaAction() {
    $ced = $this->dispatcher->getParam('ced');
    $id = $this->dispatcher->getParam('id');
    $rows = Clientes::find([
      'conditions' => 'identificacion = :ced: and id != :id:',
      'bind' => [ 'id' => $id, 'ced' => $ced ]
    ]);
    $ret = (object) [
      'res' => false,
      'cid' => 0,
      'data' => "",
      'msj' => 'No se encontro esta cedula'
    ];
    if ($rows->count() > 0) {
      $ret->res = true;
      $ret->cid = $rows[0]->id;
      $ret->data = $rows[0];
      $ret->msj = 'Este numero de cedula ya esta registrado';
      $this->response->setStatusCode(406, 'Not Acceptable');
    } else {
      $this->response->setStatusCode(200, 'Ok');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  public function pacienteCrearAction() {
    $datos = $this->request->getJsonRawBody();
    $con = new Contribuyentes();
    $res = $this->guardarDatos($con, $datos, true);
    $msj = 'Los datos se registraron correctamente';
    if (false === $res) {
        $this->response->setStatusCode(500, 'Internal Server Error');
        $msj = 'No se puede registrar los datos';
    } else {
        $this->response->setStatusCode(201, 'Created');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode("Paciente creado"));
    $this->response->send();
  }

  public function pacienteGuardarAction() {
    //$msg = "Procesando registro";
    try {
      $datos = $this->request->getJsonRawBody();
      $ret = (object) [
        'res' => false,
        'cid' => $datos->id,
        'msj' => 'Los datos no se pudieron procesar'
      ];
      $this->response->setStatusCode(406, 'Not Acceptable');
      if ($datos->id > 0) {
        // Traer paciente por id
        $pac = Pacientes::findFirstById($datos->id);
        if (strlen($datos->fecha_nacimiento) > 0) $pac->fecha_nacimiento = $datos->fecha_nacimiento;
        $pac->sexo = $datos->sexo;
        $pac->estado_civil = $datos->estado_civil;
        $pac->grupo_sanguineo = $pac->grupo_sanguineo;
        $pac->estado = $pac->estado;
        if($pac->update()) {
          // Actualizar datos de cliente
          $cli = Clientes::findFirstById($datos->relCliente->id);
          if (strlen($datos->relCliente->identificacion) > 0) $cli->identificacion = $datos->relCliente->identificacion;
          if ($datos->relCliente->identificacion_tipo > 0) $cli->identificacion_tipo = $datos->relCliente->identificacion_tipo;
          if (strlen($datos->relCliente->direccion) > 0) $cli->direccion = $datos->relCliente->direccion;
          if (strlen($datos->relCliente->telefonos) > 0) $cli->telefonos = $datos->relCliente->telefonos;
          if (strlen($datos->relCliente->representante_nom) > 0) $cli->representante_nom = $datos->relCliente->representante_nom;
          if (strlen($datos->relCliente->representante_ced) >0) $cli->representante_ced = $datos->relCliente->representante_ced;
          if (strlen($datos->relCliente->email) >0) $cli->email = $datos->relCliente->email;
          $cli->nombres = $datos->relCliente->nombres;
          if ($cli->update()) {
            $ret->res = true;
            $ret->cid = $datos->id;
            $ret->msj = "Se actualizo correctamente los datos del paciente";
            $this->response->setStatusCode(200, 'Ok');
          } else {
            $msj = "Los datos se actualizaron parcialmente" . "\n";
            foreach ($cli->getMessages() as $m) {
              $msj .= $m . "\n";
            }
            $ret->res = false;
            $ret->cid = $datos->id;
            $ret->msj = $msj;
          }
          
        } else {
          $msj = "No se puede actualizar los datos: " . "\n";
          foreach ($pac->getMessages() as $m) {
            $msj .= $m . "\n";
          }
          $ret->res = false;
          $ret->cid = $datos->id;
          $ret->msj = $msj;
        }
      } else {
        // Buscar o crear cliente
        $cliret = $this->clienteBuscarCrear($datos->relCliente);
        if ($cliret->res) {
          // Crear nuevo paciente
          $pac = new Pacientes();
          $pac->id = 0;
          $pac->cliente_id = $cliret->cid;
          $pac->foto = '';
          $pac->fecha_nacimiento = $datos->fecha_nacimiento;
          $pac->sexo = $datos->sexo;
          $pac->estado_civil = $datos->estado_civil;
          $pac->grupo_sanguineo = $datos->grupo_sanguineo;
          $pac->alergias = '';
          $pac->antecedentes_familiares = '';
          $pac->antecedentes_personales = '';
          $pac->estado = 0;
          if ($pac->create()) {
            $ret->res = true;
            $ret->cid = $pac->id;
            $ret->msj = "Se registro correctamente el nuevo paciente";
            $this->response->setStatusCode(201, 'Created');  
          } else {
            $msj = "No se pudo crear el nuevo paciente: " . "\n";
            foreach ($pac->getMessages() as $m) {
              $msj .= $m . "\n";
            }
            $ret->res = false;
            $ret->cid = 0;
            $ret->msj = $msj;
          }
        } else {
          $ret->res = false;
          $ret->cid = 0;
          $ret->msj = $cliret->msj;
        }
      }
    } catch (Exception $e) {
      $ret->res = false;
      $ret->cid = 0;
      $ret->msj = $e->getMessage();
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  public function pacienteModificarEstadoAction() {
    $id = $this->dispatcher->getParam('id');
    $est = $this->dispatcher->getParam('est');
    $res = Pacientes::findFirstById($id);
    if ($res != null) {
      $res->estado = $est;
      if($res->update()) {
        $msj = "Estado actualizado";
        $this->response->setStatusCode(200, 'Ok');
      } else {
        $this->response->setStatusCode(404, 'Error');
        $msj = "No se puede actualizar los datos: " . "\n";
        foreach ($res->getMessages() as $m) {
          $msj .= $m . "\n";
        }
      } 
    } else {
      $msj = "No se encontro el servicio";
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($msj));
    $this->response->send();
  }

  private function clienteBuscarCrear($cli) {    
    $ced = $cli->identificacion;
    $nom = $cli->nombres;
    $params = [];
    $params += [ 'nom' => $nom ];
    $condicion = 'nombres = :nom:';
    if (strlen($ced) >= 10) {
      $condicion = 'identificacion = :ced: OR ' . $condicion;
      $params += [ 'ced' => $ced ];
    }
    $rows = Clientes::find([
      'conditions' => $condicion,
      'bind' => $params
    ]);
    $ret = (object) [
      'res' => false,
      'cid' => -1,
      'msj' => 'Cliente no procesado'
    ];
    if ($rows->count() > 0) {
      $id = $rows[0]->id;
      $enc = Clientes::findFirstById($id);
      $mods = 0;
      if ($enc != null) {
        $enc->nombres = $cli->nombres;
        if (strlen($cli->identificacion)) { $enc->identificacion = $cli->identificacion; $mods++; }
        if ($cli->identificacion_tipo > 0) { $enc->identificacion_tipo = $cli->identificacion_tipo; $mods++; }
        if (strlen($cli->direccion)) { $enc->direccion = $cli->direccion; $mods++; }
        if (strlen($cli->telefonos)) { $enc->telefonos = $cli->telefonos; $mods++; }
        if (strlen($cli->representante_nom)) { $enc->representante_nom = $cli->representante_nom; $mods++; }
        if (strlen($cli->representante_ced)) { $enc->representante_ced = $cli->representante_ced; $mods++; }
        if (strlen($cli->email)) { $enc->email = $cli->email; $mods++; }
        if ($mods > 0) {
          if ($enc->update()) {
            $ret->res = true;
            $ret->cid = $enc->id;
            $ret->msj = 'Se actualizo correctamente los datos del cliente';
          } else {
            $msj = "No se pudo actualizar:" . "\n";
            foreach ($enc->getMessages() as $m) {
              $msj .= $m . "\n";
            }
            $ret->res = false;
            $ret->cid = $enc->id;
            $ret->msj = $msj;
          }
        } else {
          $ret->res = true;
          $ret->cid = $enc->id;
          $ret->msj = 'No se requieren actualizaciones';
        }
      }
    } else {
      // Creara y devolver el id creado
      // Traer codigo automatico Select valor from registros where tabla_id = 2 and indice = 1
      $cod = "000";
      $rid = 0;
      $rows = Registros::find([
        'conditions' => 'tabla_id = 2 and indice = 1'
      ]);
      if ($rows->count() > 0) {
        $rid = $rows[0]->id;
        $num = $rows[0]->valor + 1;
        $cod .= $num;
      }
      $nuevo = new Clientes();
      $nuevo->empresa_id = $cli->empresa_id;
      $nuevo->codigo = $cod;
      $nuevo->identificacion = $cli->identificacion;
      $nuevo->identificacion_tipo = $cli->identificacion_tipo;
      $nuevo->nombres = $cli->nombres;
      $nuevo->direccion = $cli->direccion;
      $nuevo->telefonos = $cli->telefonos;
      $nuevo->email = $cli->email;
      $nuevo->representante_nom = $cli->representante_nom;
      $nuevo->representante_ced = $cli->representante_ced;
      $nuevo->cupo = 0;
      $nuevo->estado = 0;
      if($nuevo->create()) {
        $reg = Registros::findFirstById($rid);
        if ($reg != null) {
          $reg->valor++;
          $reg->update();
        }
        $ret->res = true;
        $ret->cid = $nuevo->id;
        $ret->msj = 'Cliente creado exitosamente';
      } else {
        $msj = "";
        foreach ($nuevo->getMessages() as $m) {
          $msj .= $m . "\n";
        }
        $ret->res = false;
        $ret->cid = 0;
        $ret->msj = $msj;
      }
    }
    return $ret;
  }

  private function alterarEstado($id, $est) {
    $con = Contribuyentes::findFirstById($id);
    if ($con != null) {
        $con->estado = $est;
        return $con->save();
    } else {
        return false;
    }
  }

  public function pruebaFuncionAction() {
    $datos = $this->request->getJsonRawBody();
    $ret = $this->clienteBuscarCrear($datos);
    if ($ret->cid > 0) {
      $this->response->setStatusCode(200, 'Ok');  
    } else {
      $this->response->setStatusCode(409, 'Ok');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  private function pacienteCrear($datos) {
    $ret = (object) [
      'res' => false,
      'cid' => $datos->id,
      'msj' => 'Los datos no se pudieron procesar'
    ];
    $cliret = $this->clienteBuscarCrear($datos->relCliente);
    if ($cliret->res) {
      // Crear nuevo paciente
      $pac = new Pacientes();
      $pac->id = 0;
      $pac->cliente_id = $cliret->cid;
      $pac->foto = '';
      $pac->fecha_nacimiento = $datos->fecha_nacimiento;
      $pac->sexo = $datos->sexo;
      $pac->estado_civil = $datos->estado_civil;
      $pac->grupo_sanguineo = $datos->grupo_sanguineo;
      $pac->alergias = '';
      $pac->antecedentes_familiares = '';
      $pac->antecedentes_personales = '';
      $pac->estado = 0;
      if ($pac->create()) {
        $ret->res = true;
        $ret->cid = $pac->id;
        $ret->msj = "Se registro correctamente el nuevo paciente";
        $this->response->setStatusCode(201, 'Created');  
      } else {
        $msj = "No se pudo crear el nuevo paciente: " . "\n";
        foreach ($pac->getMessages() as $m) {
          $msj .= $m . "\n";
        }
        $ret->res = false;
        $ret->cid = 0;
        $ret->msj = $msj;
      }
    } else {
      $ret->res = false;
      $ret->cid = 0;
      $ret->msj = $cliret->msj;
    }
    return $ret;
  }

  // MEDICOS
  public function medicosBuscarAction() {
    $this->view->disable();
    $tipoBusca = $this->dispatcher->getParam('tipo');
    $estado = $this->dispatcher->getParam('estado');
    $filtro = $this->dispatcher->getParam('filtro');
    $empresa = $this->dispatcher->getParam('emp');
    $atrib = $this->dispatcher->getParam('atrib');
    $filtro = str_replace('%20', ' ', $filtro);
    if ($atrib == 0) {
      if ($tipoBusca == 0) {
        // Comenzando por
        $filtro .= '%';
      } else {
        $filtroSP = str_replace('  ', ' ',trim($filtro));
        $filtro = '%' . str_replace(' ' , '%',$filtroSP) . '%';
      }
    }

    $campo = 'upper(nombres) like';
    switch($atrib) {
      case 1: {
        $campo = 'identificacion =';
        break;
      };
      case 2: {
        $campo = 'codigo =';
        break;
      }
    };

    $condicion = 'empresa_id = :emp: AND ' . $campo . ' :fil:';
    if ($estado == 0) {
        $condicion .= ' AND estado = 0';
    }
    $res = Medicos::find([
      'conditions' => $condicion,
      'bind' => [ 'emp' => $empresa, 'fil' => $filtro ]
    ]);

    if ($res->count() > 0) {
      $this->response->setStatusCode(200, 'Ok');
    } else {
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function medicoPorIdAction() {
    $id = $this->dispatcher->getParam('id');
    $res = Medicos::findFirstById($id);
    if ($res != null) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $res = [];
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function medicoPorUsuarioAction() {
    $id = $this->dispatcher->getParam('id');
    $res = Medicos::find([
      'conditions' => 'usuario_id = :id:',
      'bind' => ['id' => $id]
    ]);
    if ($res->count() > 0) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function medicosPorEstadoAction() {
    $this->view->disable();
    $est = $this->dispatcher->getParam('estado');

    $res = Medicos::find([
        'conditions' => 'estado = :est:',
        'bind' => ['est' => $est],
        'order' => 'nombres'
    ]);
      // Actualizar estado
    if ($res->count() > 0) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function medicosPorEspecialidadEstadoAction() {
    $this->view->disable();
    $est = $this->dispatcher->getParam('estado');
    $esp = $this->dispatcher->getParam('especialidad');
    $meds = [];
    $res = MedicosEspecialidades::find([
      'conditions' => 'especialidad_id = :esp:',
      'bind' => ['esp' => $esp]
    ]);
      // Actualizar estado
    if ($res->count() > 0) {
      $this->response->setStatusCode(404, 'Not found');
      $idsMedicos = [];
      foreach($res as $med) {
        array_push($idsMedicos, $med->medico_id);
      }
      if (count($idsMedicos) > 0) {
        $this->response->setStatusCode(200, 'Ok');
        $csvIdsMedicos = implode(', ', $idsMedicos);
        $filtroEst = "";
        if ($est == 0) {
          $filtroEst = "estado = 0 AND ";
        }
        $meds = Medicos::find([
          'conditions' => /*$filtroEst .*/ 'id in (' . $csvIdsMedicos . ')',
          'order' => 'nombres'
        ]);
      }
    } else {
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($meds));
    $this->response->send();
  }

  public function medicoPorCedulaAction() {
    $ced = $this->dispatcher->getParam('ced');
    $ret = (object) [
      'res' => false,
      'cid' => 0,
      'data' => "",
      'msj' => 'No se encontro esta cedula'
    ];
    $rows = Medicos::find([
      'conditions' => 'identificacion = :ced:',
      'bind' => [ 'ced' => $ced ]
    ]);
    if ($rows->count() > 0) {
      $existe = true;
        $ret->res = true;
        $ret->cid = $rows[0]->id;
        $ret->data = $rows[0];
        $ret->msj = "Encontrado";
      $this->response->setStatusCode(200, 'Ok');
    }  else {
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  public function medicoGuardarAction() {
    try {
      $datos = $this->request->getJsonRawBody();
      $ret = (object) [
        'res' => false,
        'cid' => $datos->id,
        'msj' => 'Los datos no se pudieron procesar'
      ];
      if ($datos->id > 0) {
        // Traer medico por id para actualizar
        $med = Medicos::findFirstById($datos->id);
        $med->codigo = $datos->codigo;
        $med->titulo = $datos->titulo;
        $med->nombres = $datos->nombres;
        $med->identificacion = $datos->identificacion;
        $med->registro_profesional = $datos->registro_profesional;
        $med->direccion = $datos->direccion;
        $med->telefonos = $datos->telefonos;
        $med->email = $datos->email;
        $med->usuario_id = $datos->usuario_id;
        $med->empresa_id = 1;
        if($med->update()) {
          $ret->msj = "Se actualizo correctamente los datos del Medico";
          $idsNoEliminados = [];
          foreach ($datos->relEspecialidades as $esp) {
            if ($esp->id > 0) {
              array_push($idsNoEliminados, $esp->id);
            } else {
              // crear especialidades agregadas
              $ins = new MedicosEspecialidades();
              $ins->medico_id = $esp->medico_id;
              $ins->especialidad_id = $esp->especialidad_id;
              $ins->descripcion = "";
              if ($ins->create()) {
                array_push($idsNoEliminados, $ins->id);
              }
            }
          }
          if (count($idsNoEliminados) > 0) {
            $lista = implode(', ', $idsNoEliminados);
            $di = Di::getDefault();
            $qry = new Query('DELETE FROM 
              Pointerp\Modelos\Medicos\MedicosEspecialidades 
              WHERE medico_id = ' . $med->id . ' AND id not in (' . $lista . ')' , $di 
            );
            $res = $qry->execute();
          }
          $ret->res = true;
          $ret->cid = $datos->id;
          $this->response->setStatusCode(200, 'Ok');
        } else {
          $this->response->setStatusCode(500, 'Error');
          $msj = "No se puede actualizar los datos: " . "\n";
          foreach ($med->getMessages() as $m) {
            $msj .= $m . "\n";
          }
          $ret->res = false;
          $ret->cid = $datos->id;
          $ret->msj = $msj;
        }
      } else {
        // Crear medico nuevo
        $med = new Medicos();
        $med->codigo = $datos->codigo;
        $med->codigo = $datos->codigo;
        $med->titulo = $datos->titulo;
        $med->nombres = $datos->nombres;
        $med->identificacion = $datos->identificacion;
        $med->registro_profesional = $datos->registro_profesional;
        $med->direccion = $datos->direccion;
        $med->telefonos = $datos->telefonos;
        $med->email = $datos->email;
        $med->usuario_id = $datos->usuario_id;
        $med->empresa_id = $datos->empresa_id;
        $med->estado = 0;
        if ($med->create()) {
          foreach ($datos->relEspecialidades as $esp) {
            $ins = new MedicosEspecialidades();
            $ins->medico_id = $esp->medico_id;
            $ins->especialidad_id = $esp->especialidad_id;
            $ins->descripcion = "";
            $ins->create();
          }
          $ret->res = true;
          $ret->cid = $med->id;
          $ret->msj = "Se registro correctamente el nuevo Profesional";
          $this->response->setStatusCode(201, 'Created');  
        } else {
          $this->response->setStatusCode(500, 'Error');  
          $msj = "No se pudo crear el nuevo Profesional: " . "\n";
          foreach ($med->getMessages() as $m) {
            $msj .= $m . "\n";
          }
          $ret->res = false;
          $ret->cid = 0;
          $ret->msj = $msj;
        }
      }
    } catch (Exception $e) {
      $this->response->setStatusCode(500, 'Error');  
      $ret->res = false;
      $ret->cid = 0;
      $ret->msj = $e->getMessage();
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  public function medicoModificarEstadoAction() {
    $id = $this->dispatcher->getParam('id');
    $est = $this->dispatcher->getParam('estado');
    $res = Medicos::findFirstById($id);
    $this->response->setStatusCode(406, 'Not Acceptable');
    if ($res != null) {
      $res->estado = $est;
      if($res->update()) {
        $msj = "Opeeracion ejecutada exitosamente";
        $this->response->setStatusCode(200, 'Ok');
      } else {
        $this->response->setStatusCode(500, 'Error');
        foreach ($res->getMessages() as $m) {
          $msj .= $m . "\n";
        }
      }
    } else {
      $res = [];
      $msj = "No se encontro el Medico";
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($msj));
    $this->response->send();
  }

  public function medicoRegistradoAction() {
    $ced = $this->dispatcher->getParam('ced');
    $nom = $this->dispatcher->getParam('nom');
    $id = $this->dispatcher->getParam('id');
    $nom = str_replace('%20', ' ', $nom);
    $params = [];
    $params += [ 'nom' => $nom ];
    $condicion = 'nombres = :nom:';
    if (strlen($ced) >= 10) {
      $condicion = 'identificacion = :ced: OR ' . $condicion;
      $params += [ 'ced' => $ced ];
    }
    $rows = Medicos::find([
      'conditions' => $condicion,
      'bind' => $params
    ]);
    $existe = false;
    $res = 'Se puede registrar los nuevos datos';
    if ($rows->count() > 0) {
      $res = 'Estos datos ya estan registrados busquelo como ' . $rows[0]->nombres;
      $this->response->setStatusCode(406, 'Not Acceptable');
    } else {
      $this->response->setStatusCode(200, 'Ok');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function medicoCedulaRegistradaAction() {
    $ced = $this->dispatcher->getParam('ced');
    $id = $this->dispatcher->getParam('id');
    $rows = Medicos::find([
      'conditions' => 'identificacion = :ced: and id != :id:',
      'bind' => [ 'id' => $id, 'ced' => $ced ]
    ]);
    $ret = (object) [
      'res' => false,
      'cid' => 0,
      'data' => "",
      'msj' => 'No se encontro esta cedula'
    ];
    if ($rows->count() > 0) {
      $ret->res = true;
      $ret->cid = $rows[0]->id;
      $ret->data = $rows[0];
      $ret->msj = 'Este numero de cedula ya esta registrado';
      $this->response->setStatusCode(406, 'Not Acceptable');
    } else {
      $this->response->setStatusCode(200, 'Ok');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  // CONSULTAS 

  public function consultasPorPacienteAction() {
    $this->view->disable();
    $id = $this->dispatcher->getParam('id');
    $es = $this->dispatcher->getParam('estado');
    $res = $this->traerConsultasFiltro("paciente_id", $id, $es);
    if ($res->count() > 0) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function historialPacienteAction() {
    $this->view->disable();
    $id = $this->dispatcher->getParam('id');
    $conId = $this->dispatcher->getParam('consulta');
    $limite = $this->dispatcher->getParam('limite');
    $res = Consultas::find([
      'conditions' => 'paciente_id = '. $id . ' AND estado = 3 AND id != ' . $conId,
      'order' => 'fecha DESC',
      'limit' => $limite
    ]);
    if ($res->count() > 0) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function consultasPorMedicoAction() {
    $this->view->disable();
    $id = $this->dispatcher->getParam('id');
    $es = $this->dispatcher->getParam('estado');
    $res = $this->traerConsultasFiltro("medico_id", $id, $es);
    if ($res->count() > 0) {
      $this->response->setStatusCode(200, 'Ok');
    } else {
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  private function traerConsultasFiltro($tipo, $id, $es) {
    $condicion = $tipo . ' = :id:';
    if ($es == 0) {
      $condicion .= ' and estado != 2';
    }
    return Consultas::find([
        'conditions' => $condicion,
        'bind' => ['id' => $id],
        'order' => 'fecha DESC'
    ]);
  }

  public function consultasPorFechaAction() {
    $this->view->disable();
    $des = $this->dispatcher->getParam('desde');
    $has = $this->dispatcher->getParam('hasta');
    $est = $this->dispatcher->getParam('estado');
    $condicion = 'fecha >= :desde: and fecha <= :hasta:';
    if ($est == 0) {
      $condicion .= ' and estado != 2';
    }
    $res = Consultas::find([
        'conditions' => $condicion,
        'bind' => ['desde' => $des, 'hasta' => $has],
        'order' => 'fecha DESC'
    ]);
    if ($res->count() > 0) {
      $this->response->setStatusCode(200, 'Ok');
    } else {
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function consultasPorMedicoFechaAction() {
    $this->view->disable();
    $med = $this->dispatcher->getParam('id');
    $des = $this->dispatcher->getParam('desde');
    $has = $this->dispatcher->getParam('hasta');
    $est = $this->dispatcher->getParam('estado');
    $condicion = 'medico_id = :id: and fecha >= :desde: and fecha <= :hasta:';
    if ($est == 0) {
      $condicion .= ' and estado != 2';
    }
    $res = Consultas::find([
        'conditions' => $condicion,
        'bind' => [ 'id' => $med, 'desde' => $des, 'hasta' => $has],
        'order' => 'numero'
    ]);
    if ($res->count() > 0) {
      $this->response->setStatusCode(200, 'Ok');
    } else {
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function consultasPorMotivoAction() {
    $this->view->disable();
    $filtro = $this->dispatcher->getParam('filtro');
    $tipo = $this->dispatcher->getParam('tipo');
    $est = $this->dispatcher->getParam('estado');
    $filtro = str_replace('%20', ' ', $filtro);
    $filtro = str_replace('%C3%91' , 'Ñ',$filtro);
    $filtro = str_replace('%C3%B1' , 'ñ',$filtro);
    if ($atrib == 0) {
      if ($tipoBusca == 0) {
        // Comenzando por
        $filtro .= '%';
      } else {
        $filtroSP = str_replace('  ', ' ',trim($filtro));
        $filtro = '%' . str_replace(' ' , '%',$filtroSP) . '%';
      }
    }
    $condicion = 'motivo like :hasta:';
    if ($es != 9) {
      $condicion .= ' and estado = :estado:';
    }
    $res = Consultas::find([
        'conditions' => $condicion,
        'bind' => ['desde' => $des, 'hasta' => $has, 'estado' => $est],
        'order' => 'fecha DESC'
    ]);
    if ($res->count() > 0) {
      $this->response->setStatusCode(200, 'Ok');
    } else {
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function consultaPorIdAction() {
    $id = $this->dispatcher->getParam('id');
    $res = Consultas::findFirstById($id);
    if ($res != null) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $res = [];
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function consultaGuardarAction() {
    try {
      $datos = $this->request->getJsonRawBody();
      $ret = (object) [
        'res' => false,
        'cid' => $datos->id,
        'msj' => 'Los datos no se pudieron procesar',
        'num' => 0
      ];
      $this->response->setStatusCode(406, 'Not Acceptable');
      if ($datos->id > 0) {
        $con = Consultas::findFirstById($datos->id);
        if ($con != null) {
          $con->sucursal_id = $datos->sucursal_id;
          $con->paciente_id = $datos->paciente_id;
          $con->medico_id = $datos->medico_id;
          $con->servicio_id = $datos->servicio_id;
          $con->fecha = $datos->fecha;
          $con->factura_id = $con->factura_id;
          $con->motivo = $con->motivo;
          $con->sintomas_subjetivos = $datos->sintomas_subjetivos;
          $con->exploracion_fisica = $datos->exploracion_fisica;
          $con->diagnostico_cie = $datos->diagnostico_cie;
          $con->diagnostico_clase = $datos->diagnostico_clase;
          $con->diagnostico_descripcion = $datos->diagnostico_descripcion;
          $con->tratamiento = $datos->tratamiento;
          $con->proxima = $datos->proxima;
          $con->laboratorio = $datos->laboratorio;
          $con->antecedentes = $datos->antecedentes;
          $con->numero = $datos->numero;
          $con->examenes = $datos->examenes;
          $con->estado = $datos->estado;
          if($con->update()) {
            // Eliminar los items anteriores en la receta por consulta
            foreach ($con->recetaItems as $it) {
              $enc = RecetaItems::findFirstById($it->id); 
              if ($enc != false) {
                $enc->delete();
              }
            };
            // Insertar nuevos items de receta
            foreach ($datos->recetaItems as $i) {
              $ins = new RecetaItems();
              $ins->id = 0;
              $ins->consulta_id = $datos->id;
              if ($i->producto_id > 0) {
                $ins->producto_id = $i->producto_id ;
              }
              $ins->cantidad = $i->cantidad;
              $ins->descripcion = $i->descripcion;
              $ins->dosis = $i->dosis;
              $ins->create();
            }
            // Eliminar los items anteriores en la receta por consulta
            foreach ($con->relExamenes as $exi) {
              $enc = RecetaItems::findFirstById($exi->id); 
              if ($enc != false) {
                $enc->delete();
              }
            };
            // Guardar examenes
            foreach ($datos->examenesSel as $i) {
              $exi = new Examenes();
              $exi->consulta_id = $datos->id; 
              $exi->examen_id = $i->examen_id;
              $exi->seleccionados = $i->seleccionados;
              $exi->create();
            }
            $ret->res = true;
            $ret->cid = $con->id;
            $ret->msj = "Se actualizo correctamente los datos de la consulta";
            $this->response->setStatusCode(200, 'Ok');
          } else {
            $msj = "No se puede actualizar los datos: " . "\n";
            foreach ($con->getMessages() as $m) {
              $msj .= $m . "\n";
            }
            $ret->res = false;
            $ret->cid = $datos->id;
            $ret->msj = $msj;
          }
        }
      } else {
        // Traer numero de cita
        $num = Consultas::maximum([
          'column' => 'numero',
          'conditions' => "fecha = '" . $datos->fecha . "' AND medico_id = " . $datos->medico_id
        ]) ?? 0;
        // Crear paciente si no esta creado
        $pacienteId = $datos->paciente_id;
        if ($pacienteId <= 0) {
          $pacRet = $this->pacienteCrear($datos->relPaciente);
          if ($pacRet->res) {
            $pacienteId = $pacRet->cid;
          }
        }
        $con = new Consultas();
        $con->sucursal_id = $datos->sucursal_id;
        $con->paciente_id = $pacienteId;
        $con->medico_id = $datos->medico_id;
        $con->servicio_id = $datos->servicio_id;
        $con->fecha = $datos->fecha;
        $con->factura_id = 0;
        $con->motivo = '';
        $con->sintomas_subjetivos = '';
        $con->exploracion_fisica = '';
        $con->diagnostico_cie = '';
        $con->diagnostico_clase = 0;
        $con->diagnostico_descripcion = '';
        $con->tratamiento = '';
        $con->mediciones = '';
        $con->examenes = '';
        $con->laboratorio = '';
        $con->antecedentes = '';
        $con->numero = $num + 1;
        $con->estado = 0;
        if ($con->create()) {
          $ret->res = true;
          $ret->cid = $con->id;
          $ret->num = $con->numero;
          $ret->msj = "Se registro correctamente la consulta";
          $this->response->setStatusCode(201, 'Created');  
        } else {
          $msj = "No se pudo crear la nueva consulta: " . "\n";
          foreach ($con->getMessages() as $m) {
            $msj .= $m . "\n";
          }
          $ret->res = false;
          $ret->cid = 0;
          $ret->msj = $msj;
        }
      }
    } catch (Exception $e) {
      $ret->res = false;
      $ret->cid = 0;
      $ret->msj = $e->getMessage();
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  private function RecetaItemsPorConsulta($id) {
    return RecetaItems::find([
      'conditions' => 'consulta_id = :id:',
      'bind'       => [
        'id' => $id,
      ]
    ]);
  } 

  public function consultaGuardarMedicionesAction() {
    $id = $this->dispatcher->getParam('id');
    $datos = $this->request->getRawBody();
    $ret = (object) [
      'res' => false,
      'cid' => $id,
      'msj' => 'Los datos no se pudieron procesar'
    ];
    $con = Consultas::findFirstById($id);
    if ($con != null) {
      $con->mediciones = $datos;
      try {
        if($con->update()) {
          $ret->res = true;
          $ret->cid = $id;
          $ret->msj = "Se actualizo correctamente los datos de la consulta";
          $this->response->setStatusCode(200, 'Ok');
        } else {
          $msj = "No se puede actualizar los datos: " . "\n";
          foreach ($con->getMessages() as $m) {
            $msj .= $m . "\n";
          }
          $ret->res = false;
          $ret->cid = $id;
          $ret->msj = $msj;
        }
        $this->response->setStatusCode(200, 'Ok');
      } catch (Exception $e) {
        $ret->res = false;
        $ret->cid = 0;
        $ret->msj = $e->getMessage();
      }
    } else {
      $ret->msj = "No se encontro la consulta para actualizar";
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  public function consultaGuardarFacturaAction() {
    $id = $this->dispatcher->getParam('id');
    $fac = $this->dispatcher->getParam('fac');
    $ret = (object) [
      'res' => false,
      'cid' => $id,
      'msj' => 'Los datos no se pudieron procesar'
    ];
    $con = Consultas::findFirstById($id);
    if ($con != null) {
      $con->factura_id = $fac;
      try {
        if($con->update()) {
          $ret->res = true;
          $ret->cid = $con->id;
          $ret->msj = "Se actualizo correctamente los datos de la consulta";
          $this->response->setStatusCode(200, 'Ok');
        } else {
          $msj = "No se puede actualizar los datos: " . "\n";
          foreach ($con->getMessages() as $m) {
            $msj .= $m . "\n";
          }
          $ret->res = false;
          $ret->cid = $id;
          $ret->msj = $msj;
        }
        $this->response->setStatusCode(200, 'Ok');
      } catch (Exception $e) {
        $ret->res = false;
        $ret->cid = $id;
        $ret->msj = $e->getMessage();
      }
    } else {
      $ret->msj = "No se encontro la consulta para actualizar";
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  public function consultaGuardarAtencionAction() {
    $id = $this->dispatcher->getParam('id');
    $datos = $this->request->getJsonRawBody();
    $ret = (object) [
      'res' => false,
      'cid' => $id,
      'msj' => 'Los datos no se pudieron procesar'
    ];
    $res = Consultas::findFirstById($id);
    if ($res != null) {
      $con->mediciones = $datos->mediciones;
      $con->sintomas_subjetivos = $datos->sintomas_subjetivos;
      $con->exploracion_fisica = $datos->exploracion_fisica;
      $con->diagnostico_cie = $datos->diagnostico_cie;
      $con->diagnostico_clase = $datos->diagnostico_clase;
      $con->diagnostico_descripcion = $datos->diagnostico_descripcion;
      $con->tratamiento = $datos->tratamiento;
      $con->examenes = $datos->examenes;
      try {
        if($con->update()) {
          $ret->res = true;
          $ret->cid = $id;
          $ret->msj = "Se actualizo correctamente los datos de la consulta";
          $this->response->setStatusCode(200, 'Ok');
        } else {
          $msj = "No se puede actualizar los datos: " . "\n";
          foreach ($con->getMessages() as $m) {
            $msj .= $m . "\n";
          }
          $ret->res = false;
          $ret->cid = $id;
          $ret->msj = $msj;
        }
        $this->response->setStatusCode(200, 'Ok');
      } catch (Exception $e) {
        $ret->res = false;
        $ret->cid = 0;
        $ret->msj = $e->getMessage();
      }
    } else {
      $ret->msj = "No se encontro la consulta para actualizar";
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  public function consultaGuardarEstadoAction() {
    $id = $this->dispatcher->getParam('id');
    $est = $this->dispatcher->getParam('est');
    $ret = (object) [
      'res' => false,
      'cid' => $id,
      'msj' => 'Los datos no se pudieron procesar'
    ];
    $con = Consultas::findFirstById($id);
    if ($con != null) {
      $con->estado = $est;
      try {
        if($con->update()) {
          $ret->res = true;
          $ret->cid = $id;
          $ret->msj = "Se actualizo correctamente los datos de la consulta";
          $this->response->setStatusCode(200, 'Ok');
        } else {
          $msj = "No se puede actualizar los datos: " . "\n";
          foreach ($con->getMessages() as $m) {
            $msj .= $m . "\n";
          }
          $ret->res = false;
          $ret->cid = $id;
          $ret->msj = $msj;
        }
        $this->response->setStatusCode(200, 'Ok');
      } catch (Exception $e) {
        $ret->res = false;
        $ret->cid = 0;
        $ret->msj = $e->getMessage();
      }
    } else {
      $ret->msj = "No se encontro la consulta para actualizar";
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  public function consultasUnificarPacienteAction() {
    $pid = $this->dispatcher->getParam('id');
    $otros = $this->request->getJsonRawBody();
    $lista = implode(', ', $otros);
    $di = Di::getDefault();
    $qry = new Query('UPDATE 
      Pointerp\Modelos\Medicos\Consultas  
      SET paciente_id = ' . $pid .
      'WHERE paciente_id in (' . $lista . ')' , $di 
    );
    $res = $qry->execute();
    foreach($otros as $p) {
      $con = Pacientes::findFirstById($p);
      if ($con != null) {
        $con->estado = 2;
        $con->update();
      }
    }
    $this->response->setStatusCode(200, 'Ok');
    $msj = "La operacion se ejecuto exitosamente";
    if ($res->success() === false) {
      $this->response->setStatusCode(501, 'Error');
      $msj = "Error: " . "\n";
      foreach ($res->getMessages() as $m) {
        $msj .= $m . "\n";
      }
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($msj));
    $this->response->send();
  }

  // SERVICIOS MEDICOS

  public function especialidadesTodasAction() {
    $this->view->disable();
    $res = Especialidades::find();

    if ($res->count() > 0) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }
 
  public function serviciosPorEspecialAction() {
    $this->view->disable();
    $esp = $this->dispatcher->getParam('id');

    $res = Servicios::find([
        'conditions' => 'especialidad_id = :esp: and estado = 0',
        'bind' => ['esp' => $esp],
        'order' => 'descripcion'
    ]);

    if ($res->count() > 0) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function serviciosBuscarAction() {
    $this->view->disable();
    $tipoBusca = $this->dispatcher->getParam('tipo');
    $filtro = $this->dispatcher->getParam('filtro');
    $estado = $this->dispatcher->getParam('estado');
    $filtro = str_replace('%20', ' ', $filtro);
    if ($tipoBusca == 0) {
      // Comenzando por
      $filtro .= '%';
    } else {
      // Conteniendo
      $filtroSP = str_replace('  ', ' ',trim($filtro));
      $filtro = '%' . str_replace(' ' , '%',$filtroSP) . '%';
    }
    $condicion = "";
    if ($estado == 0) {
      $condicion = ' AND estado = 0';
    }
    $res = Servicios::find([
        'conditions' => 'UPPER(descripcion) LIKE UPPER(:fil:)' . $condicion,
        'bind' => ['fil' => $filtro],
        'order' => 'descripcion'
    ]);

    if ($res->count() > 0) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function servicioGuardarAction() {
    try {
      $datos = $this->request->getJsonRawBody();
      $ret = (object) [
        'res' => false,
        'cid' => $datos->id,
        'msj' => 'Los datos no se pudieron procesar'
      ];
      $this->response->setStatusCode(406, 'Not Acceptable');
      if ($datos->id > 0) {
        // Traer servicio por id
        $srv = Servicios::findFirstById($datos->id);
        $srv->especialidad_id = $datos->especialidad_id;
        $srv->codigo = $datos->codigo;
        $srv->descripcion = $datos->descripcion;
        $srv->producto_id = $datos->producto_id;
        $srv->valor = $datos->valor;
        $srv->estado = $datos->estado;
        if($srv->update()) {
          $ret->res = true;
          $ret->cid = $datos->id;
          $ret->msj = "Se actualizo correctamente los datos del servicio";
          $this->response->setStatusCode(200, 'Ok');
        } else {
          $msj = "No se puede actualizar los datos: " . "\n";
          foreach ($srv->getMessages() as $m) {
            $msj .= $m . "\n";
          }
          $ret->res = false;
          $ret->cid = $datos->id;
          $ret->msj = $msj;
        }
      } else {
        // Crear servicio nuevo
        // Crear codigo
        $cod = Servicios::maximum([
          'column' => 'codigo',
          'conditions' => 'especialidad_id = ' . $datos->especialidad_id
        ]) ?? 0;
        $num = intval($cod);
        $num += 1;
        $cod = str_pad($num, 4, "0", STR_PAD_LEFT);
        // str_pad
        $srv = new Servicios();
        $srv->especialidad_id = $datos->especialidad_id;
        $srv->codigo = $cod;
        $srv->descripcion = $datos->descripcion;
        $srv->producto_id = $datos->producto_id;
        $srv->valor = $datos->valor;
        $srv->estado = 0;
        if ($srv->create()) {
          $ret->res = true;
          $ret->cid = $srv->id;
          $ret->msj = "Se registro correctamente el nuevo servicio";
          $this->response->setStatusCode(201, 'Created');  
        } else {
          $msj = "No se pudo crear el nuevo servicio: " . "\n";
          foreach ($srv->getMessages() as $m) {
            $msj .= $m . "\n";
          }
          $ret->res = false;
          $ret->cid = 0;
          $ret->msj = $msj;
        }
      }
    } catch (Exception $e) {
      $this->response->setStatusCode(500, 'Error');
      $ret->res = false;
      $ret->cid = 0;
      $ret->msj = $e->getMessage();
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  public function servicioPorIdAction() {
    $id = $this->dispatcher->getParam('id');
    $res = Servicios::findFirstById($id);
    if ($res != null) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $res = [];
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function servicioRegistradoAction() {
    $cod = $this->dispatcher->getParam('cod');
    $des = $this->dispatcher->getParam('des');
    $id = $this->dispatcher->getParam('id');
    $des = str_replace('%20', ' ', $des);
    $rows = Servicios::find([
      'conditions' => 'codigo = :cod: OR descripcion = :des:',
      'bind' => [ 'des' => $des, 'cod' => $cod ]
    ]);
    $existe = false;
    $res = 'Se puede registrar los nuevos datos';
    if ($rows->count() > 0) {
      $existe = true;
      $res = 'Estos datos ya estan registrados busquelo como ' . $rows[0]->descripcion;
      $this->response->setStatusCode(406, 'Not Acceptable');
    }
    if (!$existe) {
      $this->response->setStatusCode(200, 'Ok');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function servicioModificarEstadoAction() {
    $id = $this->dispatcher->getParam('id');
    $est = $this->dispatcher->getParam('estado');
    $srv = Servicios::findFirstById($id);
    if ($srv != null) {
      $srv->estado = $est;
      if($srv->update()) {
        $msj = "La operacion se ejecuto exitosamente";
        $this->response->setStatusCode(200, 'Ok');
      } else {
        $this->response->setStatusCode(404, 'Error');
        $msj = "No se puede actualizar los datos: " . "\n";
        foreach ($srv->getMessages() as $m) {
          $msj .= $m . "\n";
        }
      }
    } else {
      $msj = "No se encontro el servicio";
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($msj));
    $this->response->send();
  }

  // PLANTILLAS

  public function plantillasPorIdAction() {
    $this->view->disable();
    $id = $this->dispatcher->getParam('id');
    $res = PlantillaInformes::findFirstById($id);
    if ($res != null) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $res = [];
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send(); 
  }

  public function plantillaCamposPorIdAction() {
    $this->view->disable();
    $id = $this->dispatcher->getParam('id');

    $res = PlantillaCampos::find([
        'conditions' => 'plantilla_id = :pid:',
        'bind' => ['pid' => $id]
    ]);

    if ($res->count() > 0) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send(); 
  }

  public function plantillasPorEstadoAction() {
    $this->view->disable();
    $id = $this->dispatcher->getParam('id');
    $res = PlantillaInformes::find();
    if ($res != null) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $res = [];
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  // TABLAS

  public function clavePorIdAction() {
    $this->view->disable();
    $id = $this->dispatcher->getParam('id');
    $res = MdTablasRegistros::findFirstById($id);

    if ($res != null) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $res = [];
        $this->response->setStatusCode(404, 'Not found');
    }
    
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function clavesPorTablaAction() {
    $this->view->disable();
    $tabla = $this->dispatcher->getParam('tabla');
    $res = MdTablasRegistros::find([
        'conditions' => 'tabla_id = :tid:',
        'bind' => ['tid' => $tabla,],
        'order' => 'indice'
    ]);

    if ($res->count() > 0) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function examenesListaAction() {
    $this->view->disable();
    $cats = 11;
    $ret = [];
    $rows = MdTablasRegistros::find([
        'conditions' => 'tabla_id = 11',
        'order' => 'indice'
    ]);

    if ($rows->count() > 0) {
      foreach ($rows as $k => $o) {
        $ins = $o->toArray();
        $ins["seleccionado"] = "";
        $ins["items"] = MdTablasRegistros::find([
          'conditions' => 'tabla_id = 12 AND contenedor = ' . $o->indice,
          'order' => 'indice'
        ])->toArray();
        array_push($ret, $ins);
      }
      $this->response->setStatusCode(200, 'Ok');
    } else {
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }
}
