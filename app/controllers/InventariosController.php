<?php

namespace Pointerp\Controladores;

use Phalcon\Di;
use Phalcon\Mvc\Model\Query;
use Pointerp\Modelos\Maestros\Productos;
use Pointerp\Modelos\Inventarios\Bodegas;
use Pointerp\Modelos\Inventarios\Kardex;
use Pointerp\Modelos\Inventarios\Movimientos;
use Pointerp\Modelos\Inventarios\MovimientosItems;
use Pointerp\Modelos\Maestros\Registros;

class InventariosController extends ControllerBase  {

  // PRODUCTOS
  public function productoPorIdAction() {
    $this->view->disable();
    $id = $this->dispatcher->getParam('id');
    $res = Productos::findFirstById($id);

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

  public function productosBuscarAction() {
    $this->view->disable();
    $tipoBusca = $this->dispatcher->getParam('tipo');
    $estado = $this->dispatcher->getParam('estado');
    $filtro = $this->dispatcher->getParam('filtro');
    $empresa = $this->dispatcher->getParam('emp');
    $atrib = $this->dispatcher->getParam('atrib');
    $filtro = str_replace('%20', ' ', $filtro);
    $filtro = str_replace('%C3%91' , 'Ñ',$filtro);
    $filtro = str_replace('%C3%B1' , 'ñ',$filtro);
    if ($atrib == 0) {
      if ($tipoBusca == 0) {
        $filtro .= '%';
      } else {
        $filtroSP = str_replace('  ', ' ',trim($filtro));
        $filtro = '%' . str_replace(' ' , '%',$filtroSP) . '%';
      }
    }

    $campo = 'upper(nombre) like';
    switch($atrib) {
      case 1: {
        $campo = 'codigo =';
        break;
      };
    };

    $condicion = 'empresa_id = :emp: AND ' . $campo . ' :fil:';
    if ($estado == 0) {
        $condicion .= ' AND estado = 0';
    }
    
    $res = Productos::find([
      'conditions' => $condicion,
      'bind' => [ 'emp' => $empresa, 'fil' => $filtro ],
      'order' => 'nombre'
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
  public function productoRegistradoAction() {
    $cod = $this->dispatcher->getParam('cod');
    $nom = $this->dispatcher->getParam('nom');
    $id = $this->dispatcher->getParam('id');
    $nom = str_replace('%20', ' ', $nom);
    $rows = Productos::find([
      'conditions' => 'id != :id: AND (codigo = :cod: OR nombre = :nom:)',
      'bind' => [ 'nom' => $nom, 'cod' => $cod, 'id' => $id ]
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
  public function productoGuardarAction() {
    try {
      $datos = $this->request->getJsonRawBody();
      $ret = (object) [
        'res' => false,
        'cid' => $datos->id,
        'msj' => 'Los datos no se pudieron procesar'
      ];
      if ($datos->id > 0) {
        // Traer medico por id para actualizar
        $prd = Productos::findFirstById($datos->id);
        $prd->codigo = $datos->codigo;
        $prd->nombre = $datos->nombre;
        $prd->barcode = $datos->barcode;
        $prd->grupo = $datos->grupo;
        $prd->descripcion = $datos->descripcion;
        $prd->medida = $datos->medida;
        $prd->tipo = $datos->tipo;
        $prd->ultimo_costo = $datos->ultimo_costo;
        $prd->precio = $datos->precio;
        $prd->adicional = $datos->adicional;
        $prd->referencia = $datos->referencia;
        $prd->marca = $datos->marca;
        $prd->modelo = $datos->modelo;
        $prd->precio_origen = $datos->precio_origen;
        //$prd->empresa_id = $datos->empresa_id;
        if($prd->update()) {
          $ret->res = true;
          $ret->cid = $datos->id;
          $ret->msj = "Se actualizo correctamente los datos del Producto";
          $this->response->setStatusCode(200, 'Ok');
        } else {
          $this->response->setStatusCode(500, 'Error');
          $msj = "No se puede actualizar los datos: " . "\n";
          foreach ($prd->getMessages() as $m) {
            $msj .= $m . "\n";
          }
          $ret->res = false;
          $ret->cid = $datos->id;
          $ret->msj = $msj;
        }
      } else {
        // Crear medico nuevo
        $cod = Productos::maximum([
          'column' => 'codigo'
        ]) ?? 0;
        $num = intval($cod);
        $num += 1;
        $cod = str_pad($num, 8, "0", STR_PAD_LEFT);
        $prd = new Productos();
        $prd->codigo = $cod;
        $prd->nombre = $datos->nombre;
        $prd->barcode = $datos->barcode;
        $prd->grupo = $datos->grupo;
        $prd->descripcion = $datos->descripcion;
        $prd->medida = $datos->medida;
        $prd->tipo = $datos->tipo;
        $prd->ultimo_costo = $datos->ultimo_costo;
        $prd->precio = $datos->precio;
        $prd->adicional = $datos->adicional;
        $prd->referencia = $datos->referencia;
        $prd->marca = $datos->marca;
        $prd->modelo = $datos->modelo;
        $prd->precio_origen = $datos->precio_origen;
        $prd->empresa_id = $datos->empresa_id;
        $prd->estado = 0;
        if ($prd->create()) {
          $ret->res = true;
          $ret->cid = $prd->id;
          $ret->msj = "Se registro correctamente el nuevo producto";
          $this->response->setStatusCode(201, 'Created');  
        } else {
          $this->response->setStatusCode(500, 'Error');  
          $msj = "No se pudo crear el nuevo producto: " . "\n";
          foreach ($prd->getMessages() as $m) {
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
  public function productoModificarEstadoAction() {
    $id = $this->dispatcher->getParam('id');
    $est = $this->dispatcher->getParam('estado');
    $res = Productos::findFirstById($id);
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

  // KARDEX
  public function bodegasPorEstadoAction() {
    $this->view->disable();
    $est = $this->dispatcher->getParam('estado');
    $ops = [];
    if ($est == 0) { 
      $ops += [ 'conditions' => 'estado = :est:' ];
      $ops += [ 'bind' => ['est' => $est] ];
    }
    $ops += [ 'order' => 'nombre' ];
    $res = Bodegas::find($ops);
    if ($res->count() > 0) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }
  
  public function exitenciasProductoAction() {
    $id = $this->dispatcher->getParam('id');
    $bod = $this->dispatcher->getParam('bodega');
    $res = Kardex::find([
      'conditions' => 'producto_id = :pro: AND bodega_id = :bod:',
      'bind' => [ 'pro' => $id, 'bod' => $bod ]
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

  public function exitenciasTodosAction() {
    $di = Di::getDefault();
    $bod = $this->dispatcher->getParam('bodega');
    $zeros = $this->dispatcher->getParam('zeros');
    $ret = [];
    $res = Kardex::find([
      'conditions' => 'bodega_id = :bod:',
      'bind' => [ 'bod' => $bod ]
    ]);
    if ($zeros > 0) {
      foreach ($res as $kdx) {
        array_push($ret, $kdx);
      }
      $qry = new Query('SELECT p.* 
        FROM Pointerp\Modelos\Maestros\Productos p 
        Where id not in (
          Select producto_id from Pointerp\Modelos\Inventarios\Kardex
        )', $di 
      );
      $prs = $qry->execute();
      if ($prs->count() > 0) {
        foreach ($prs as $prd) {
          $k = new Kardex();
          $k->id = $prd->id;
          $k->relProducto = $prd;
          $k->bodega_id = $bod;
          $k->ingresos = 0;
          $k->egresos = 0;
          $k->presentacion_id = '';
          $k->lote_id = 0;
          $k->actualizacion = date('Y-m-d H:i:s');
          array_push($ret, $k);
        }
      }
    } else {
      $ret = $res;
    }
    //$rex = array_merge($res);
    if (count($ret) > 0) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($ret));
    $this->response->send();
  }

  public function productosEnCeroAction() {
    $di = Di::getDefault();
    $bod = $this->dispatcher->getParam('bodega');
    $qry = new Query('SELECT p.* 
      FROM Pointerp\Modelos\Maestros\Productos p 
      Where id not in (
        Select producto_id from Pointerp\Modelos\Inventarios\Kardex
      )', $di 
    );
    $res  =  $qry->execute();
    if ($res->count() > 0) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }

    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  // MOVIMIENTOS
  public function movimientosBuscarAction() {
    $this->view->disable();
    $bod = $this->dispatcher->getParam('bodega');
    $tipoBusca = $this->dispatcher->getParam('tipo');
    $filtro = $this->dispatcher->getParam('filtro');
    $estado = $this->dispatcher->getParam('estado');
    $clase = $this->dispatcher->getParam('clase');
    $desde = $this->dispatcher->getParam('desde');
    $hasta = $this->dispatcher->getParam('hasta');
    $condicion = "";
    $res = [];
    if ($clase < 3) {
      $condicion = "fecha >= '" . $desde . "' AND fecha <= '" . $hasta . "'";
      if (strlen($filtro) > 1) {
        if ($clase == 2) {
          $filtro = str_replace('%20', ' ', $filtro);
          if ($tipoBusca == 0) {
            // Comenzando por
            $filtro .= '%';
          } else {
            // Conteniendo
            $filtroSP = str_replace('  ', ' ',trim($filtro));
            $filtro = '%' . str_replace(' ' , '%',$filtroSP) . '%';
          }
        }
        $condicion .= " AND descripcion like '" . $filtro . "'";
      }
    } else {
      $condicion .= 'numero = ' . $filtro;
    }
    if (strlen($condicion) > 0) {
      $condicion .= ' AND ';
      $condicion .= 'estado = 0';
      $res = Movimientos::find([
        'conditions' => $condicion,
        'order' => 'fecha'
      ]);
    }

    if ($res->count() > 0) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function movimientoGuardarAction() {
    try {
      $datos = $this->request->getJsonRawBody();
      $ret = (object) [
        'res' => false,
        'cid' => $datos->id,
        'msj' => 'Los datos no se pudieron procesar'
      ];
      $this->response->setStatusCode(406, 'Not Acceptable');
      $signo = $this->signoPorTipo($datos->tipo);
      if ($datos->id > 0) {
        // Traer movimiento por id y acualizar
        $mov = Movimientos::findFirstById($datos->id);
        // Traer los items anteriores, reversar el inventario y eliminar estos items 
        foreach ($mov->relItems as $mie) {
          if ($signo != 0 && !is_null($mie->relProducto->relTipo) && $mie->relProducto->relTipo->contenedor > 0) { // Signo 0 no afecta el inventario
            $this->afectarMovimientoInventario($mie, $mov->bodega_id, -1, $signo);
          }
          $eli = MovimientosItems::findFirstById($mie->id);
          if ($eli != false) {
            $eli->delete();
          }
        }
        $mov->fecha = $datos->fecha;
        $mov->bodega_id = $datos->bodega_id;
        $mov->referencia = $datos->referencia;
        $mov->sucursal_id = $datos->sucursal_id;
        $mov->descripcion = $datos->descripcion;
        $mov->concepto = $datos->concepto;
        $mov->subtotal = $datos->subtotal;
        $mov->subtotalex = $datos->subtotalex;
        $mov->descuento = $datos->descuento;
        $mov->impuestos = $datos->impuestos;
        $mov->estado = $datos->estado;
        if($mov->update()) {
          $ret->res = true;
          $ret->cid = $datos->id;
          // crear los items actualues
          foreach ($datos->relItems as $mi) {
            // Afectar el inventario
            if ($signo != 0 && !is_null($mi->relProducto->relTipo) && $mi->relProducto->relTipo->contenedor > 0) { // Signo 0 no afecta el inventario
              $this->afectarMovimientoInventario($mi, $mov->bodega_id, 0, $signo);
            }
            $ins = new MovimientosItems();
            $ins->movimiento_id = $mov->id;
            $ins->producto_id = $mi->producto_id;
            $ins->cantidad = $mi->cantidad;
            $ins->bodega_id = $mov->bodega_id;
            $ins->lote_id = 0;
            $ins->costo = $mi->costo;
            $ins->descuento = $mi->descuento;
            $ins->adicional = 0;
            $ins->observaciones = '';
            $ins->create();
          }
          $ret->msj = "Se actualizo correctamente los datos del registro";
          $this->response->setStatusCode(200, 'Ok');
        } else {
          $msj = "No se puede actualizar los datos: " . "\n";
          foreach ($mov->getMessages() as $m) {
            $msj .= $m . "\n";
          }
          $ret->res = false;
          $ret->cid = $datos->id;
          $ret->msj = $msj;
        }
      } else {
        // Crear movimiento nuevo
        $num = $this->ultimoNumeroMovimiento($datos->tipo);
        $mov = new Movimientos();
        $mov->numero = $num + 1;
        $mov->tipo = $datos->tipo;
        $mov->fecha = $datos->fecha;
        $mov->bodega_id = $datos->bodega_id;
        $mov->referencia = $datos->referencia;
        $mov->sucursal_id = $datos->sucursal_id;
        $mov->descripcion = $datos->descripcion;
        $mov->concepto = $datos->concepto;
        $mov->subtotal = $datos->subtotal;
        $mov->subtotalex = $datos->subtotalex;
        $mov->descuento = $datos->descuento;
        $mov->impuestos = $datos->impuestos;
        $mov->estado = 0;
        if ($mov->create()) {
          $ret->res = true;
          $ret->cid = $mov->id;
          $ret->msj = "Se registro correctamente el nuevo movimiento";  
          // Crear items y afectar el inventario
          foreach ($datos->relItems as $mi) {
            if ($signo != 0 && !is_null($mi->relProducto->relTipo) && $mi->relProducto->relTipo->contenedor > 0) { // Signo 0 no afecta el inventario
              $this->afectarMovimientoInventario($mi, $mov->bodega_id, 0, $signo);
            }
            $ins = new MovimientosItems();
            $ins->movimiento_id = $mov->id;
            $ins->producto_id = $mi->producto_id;
            $ins->cantidad = $mi->cantidad;
            $ins->bodega_id = $mov->bodega_id;
            $ins->lote_id = 0;
            $ins->costo = $mi->costo;
            $ins->descuento = $mi->descuento;
            $ins->adicional = 0;
            $ins->observaciones = '';
            $ins->create();
          }
          $this->response->setStatusCode(201, 'Created');
        } else {
          $msj = "No se pudo crear el nuevo registro: " . "\n";
          foreach ($mov->getMessages() as $m) {
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

  private function afectarMovimientoInventario($item, $bod, $origen, $signo) {
    $res = Kardex::find([
      'conditions' => 'producto_id = :pro: AND bodega_id = :bod:',
      'bind' => [ 'bod' => $bod, 'pro' => $item->producto_id ]
    ]);
    $ing = 0;
    $egr = 0;
    if ($origen == 0) {
      // 1 Es una operacion de aplicacion
      if ($signo < 0) {
        // 1.1 Es operacion negativa egreso
        $egr = $item->cantidad;
      } else {
        // 1.2 Es operacion poitiva ingreso
        $ing = $item->cantidad;
      }
    } else {
      // 2 Es una operacion de reversion
      if ($signo < 0) {
        // 2.1 Es operacion negativa egreso
        $egr = $item->cantidad * -1;
      } else {
        // 2.2 Es operacion poitiva ingreso
        $ing = $item->cantidad * -1;
      }
    }
    $date = date('Y-m-d H:i:s');

    $msj = "No se proceso";
    if ($res->count() > 0) {
      $kdx = $res[0];
      $kdx->ingresos = $kdx->ingresos + $ing;
      $kdx->egresos = $kdx->egresos + $egr;
      $kdxn->actualizacion = date('Y-m-d H:i:s');
      if ($kdx->update()) {
        $msj = "Se actualizo";
      } else {
        $msj = "No se pudo crear el nuevo kardex: " . "\n";
        foreach ($kdx->getMessages() as $m) {
          $msj .= $m . "\n";
        }
      }
    } else {
      $kdxn = new Kardex();
      $kdxn->producto_id = $item->producto_id;
      $kdxn->bodega_id = $bod;
      $kdxn->ingresos = $ing;
      $kdxn->egresos = $egr;
      $kdxn->presentacion_id = '';
      $kdxn->lote_id = 0;
      $kdxn->actualizacion = date('Y-m-d H:i:s');
      if ($kdxn->create()) {
        $msj = "Todo bien";
      } else {
        $msj = "No se pudo crear el nuevo kardex: " . "\n";
        foreach ($kdxn->getMessages() as $m) {
          $msj .= $m . "\n";
        }
      }
    }
    return $msj;
  }

  private function ultimoNumeroMovimiento($tipo) {
    return Movimientos::maximum([
      'column' => 'numero',
      'conditions' => 'tipo = ' . $tipo
    ]) ?? 0;
  }

  private function signoPorTipo($tipo) {
    $this->view->disable();
    $res = Registros::find([
        'conditions' => 'tabla_id = 20 AND indice = :tipo:',
        'bind' => ['tipo' => $tipo]
    ]);
    if ($res->count() > 0) {
      $si = $res[0];
      return $si->valor;
    } else {
      return 9;
    }
  }

  public function movimientoPorIdAction() {
    $id = $this->dispatcher->getParam('id');
    $res = Movimientos::findFirstById($id);
    if ($res != false) {
        $this->response->setStatusCode(200, 'Ok');
    } else {
        $res = [];
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function movimientoModificarEstadoAction() {
    $id = $this->dispatcher->getParam('id');
    $est = $this->dispatcher->getParam('estado');
    $mov = Movimientos::findFirstById($id);
    if ($mov != false) {
      $mov->estado = $est;
      if($mov->update()) {
        $msj = "La operacion se ejecuto exitosamente";
        $this->response->setStatusCode(200, 'Ok');
      } else {
        $this->response->setStatusCode(404, 'Error');
        $msj = "No se puede actualizar los datos: " . "\n";
        foreach ($mov->getMessages() as $m) {
          $msj .= $m . "\n";
        }
      }
    } else {
      $msj = "No se encontro el registro";
      $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($msj));
    $this->response->send();
  }
}