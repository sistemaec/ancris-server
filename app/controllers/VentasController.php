<?php

namespace Pointerp\Controladores;

use Phalcon\Di;
use Phalcon\Mvc\Model\Query;
use Pointerp\Modelos\Inventarios\Kardex;
use Pointerp\Modelos\Ventas\Ventas;
use Pointerp\Modelos\Ventas\VentasItems;
use Pointerp\Modelos\Maestros\Registros;
use Pointerp\Modelos\Medicos\Consultas;

class VentasController extends ControllerBase  {

  public function ventasBuscarAction() {
    $this->view->disable();
    $suc = $this->dispatcher->getParam('sucursal'); // Solo se consulta una sucursal
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
        $condicion .= " AND observaciones like '" . $filtro . "'";
      }
    } else {
      $condicion .= 'numero = ' . $filtro;
    }

    if ($estado == 0) {
      // Solo activos
      if (strlen($condicion) > 0) {
        $condicion .= ' AND ';
      }  
      $condicion .= 'estado != 2';
    }
    
    $res = Ventas::find([
      'conditions' => $condicion,
      'order' => 'fecha'
    ]);

    if ($res->count() > 0) {
        $this->response->setStatusCode(200, 'Ok ' . $clase);
    } else {
        $this->response->setStatusCode(404, 'Not found');
    }
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($res));
    $this->response->send();
  }

  public function ventaGuardarAction() {
    try {
      $datos = $this->request->getJsonRawBody();
      $ret = (object) [
        'res' => false,
        'cid' => $datos->id,
        'num' => $datos->numero,
        'msj' => 'Los datos no se pudieron procesar'
      ];
      $this->response->setStatusCode(406, 'Not Acceptable');
      $signo = $this->signoPorTipo($datos->tipo);
      if ($datos->id > 0) {
        // Traer movimiento por id y acualizar
        $ven = Ventas::findFirstById($datos->id);
        // Traer los items anteriores, reversar el inventario y eliminar estos items 
        foreach ($ven->relItems as $mie) {
          if ($signo != 0 && !is_null($mie->relProducto->relTipo) && $mie->relProducto->relTipo->contenedor > 0) { // Signo 0 no afecta el inventario
            $msx = $this->afectarInventario($mie, $ven->movimiento_id, -1, $signo);
          }
          $eli = VentasItems::findFirstById($mie->id);
          if ($eli != false) {
            $eli->delete();
          }
        }
        $ven->fecha = $datos->fecha;
        $ven->sucursal_id = $datos->sucursal_id;
        $ven->movimiento_id = $datos->movimiento_id; // BodegaId
        $ven->plazo = $datos->plazo;
        $ven->cliente_id = $datos->cliente_id;
        $ven->vendedor_id = $datos->vendedor_id;
        $ven->observaciones = $datos->observaciones;
        $ven->descuento_porcentaje = $datos->descuento_porcentaje;
        $ven->porcentaje_venta = $datos->porcentaje_venta;
        $ven->subtotal = $datos->subtotal;
        $ven->subtotalex = $datos->subtotalex;
        $ven->descuento = $datos->descuento;
        $ven->recargo = $datos->recargo;
        $ven->flete = $datos->flete;
        $ven->impuestos = $datos->impuestos;
        $ven->abonos = $datos->abonos;
        $ven->estado = $datos->estado;
        $ven->especie = $datos->especie; // receta, servicio medico
        $ven->ecomprobante_id = $datos->ecomprobante_id;
        $ven->operador = $datos->operador;
        if($ven->update()) {
          $ret->res = true;
          $ret->cid = $datos->id;
          // crear los items actuales
          foreach ($datos->relItems as $mi) {
            $ins = new VentasItems();
            $ins->venta_id = $ven->id;
            $ins->movitem_id = $mi->movitem_id; // Bodega
            $ins->producto_id = $mi->producto_id;
            $ins->cantidad = $mi->cantidad;
            $ins->pedido = $mi->pedido;
            $ins->precio = $mi->precio;
            $ins->costo = $mi->costo;
            $ins->descuento = $mi->descuento;
            $ins->adicional = $mi->adicional;
            $ins->observacion = $mi->observacion;
            $ins->presentacion_id = $mi->presentacion_id;
            $ins->lote_id = $mi->lote_id;
            $ins->create();
            // Afectar el inventario
            if ($signo != 0 && !is_null($mi->relProducto->relTipo) && $mi->relProducto->relTipo->contenedor > 0) { // Signo 0 no afecta el inventario
              $msx = $this->afectarInventario($mi, $ven->movimiento_id, 0, $signo);
            }
          }
          $ret->msj = "Se actualizo correctamente los datos del registro";
          $this->response->setStatusCode(200, 'Ok');
        } else {
          $msj = "No se puede actualizar los datos: " . "\n";
          foreach ($ven->getMessages() as $m) {
            $msj .= $m . "\n";
          }
          $ret->res = false;
          $ret->cid = $datos->id;
          $ret->msj = $msj;
        }
      } else {
        // Crear factura nuevo
        $num = $this->ultimoNumeroVenta($datos->tipo);
        $ven = new Ventas();
        $ven->tipo = $datos->tipo;
        $ven->numero = $num + 1;
        $ven->fecha = $datos->fecha;
        $ven->sucursal_id = $datos->sucursal_id;
        $ven->movimiento_id = $datos->movimiento_id; // BodegaId
        $ven->plazo = $datos->plazo;
        $ven->cliente_id = $datos->cliente_id;
        $ven->vendedor_id = $datos->vendedor_id;
        $ven->observaciones = $datos->observaciones;
        $ven->descuento_porcentaje = $datos->descuento_porcentaje;
        $ven->porcentaje_venta = $datos->porcentaje_venta;
        $ven->subtotal = $datos->subtotal;
        $ven->subtotalex = $datos->subtotalex;
        $ven->descuento = $datos->descuento;
        $ven->recargo = $datos->recargo;
        $ven->flete = $datos->flete;
        $ven->impuestos = $datos->impuestos;
        $ven->abonos = $datos->abonos;
        $ven->estado = $datos->estado;
        $ven->especie = $datos->especie; // receta, servicio medico
        $ven->ecomprobante_id = $datos->ecomprobante_id;
        $ven->operador = $datos->operador;
        if ($ven->create()) {
          $ret->res = true;
          $ret->cid = $ven->id;
          $ret->num = $ven->numero;
          $ret->msj = "Se registro correctamente la nueva transaccion";  
          // Crear items y afectar el inventario
          foreach ($datos->relItems as $mi) {
            if ($signo != 0 && !is_null($mi->relProducto->relTipo) && $mi->relProducto->relTipo->contenedor > 0) { // Signo 0 no afecta el inventario
              $msx = $this->afectarInventario($mi, $ven->movimiento_id, 0, $signo);
            }
            $ins = new VentasItems();
            $ins->venta_id = $ven->id;
            $ins->movitem_id = $mi->movitem_id;
            $ins->producto_id = $mi->producto_id;
            $ins->cantidad = $mi->cantidad;
            $ins->pedido = $mi->pedido;
            $ins->precio = $mi->precio;
            $ins->costo = $mi->costo;
            $ins->descuento = $mi->descuento;
            $ins->adicional = $mi->adicional;
            $ins->observacion = $mi->observacion;
            $ins->presentacion_id = $mi->presentacion_id;
            $ins->lote_id = $mi->lote_id;
            if (!$ins->create()) {
              foreach ($ins->getMessages() as $m) {
                $msj .= $m . "\n";
              }
            }
          }
          // Registrar factura en consulta
          if ($ven->especie > 0) {
            $con = Consultas::findFirstById($ven->especie);
            if ($con != false) {
              $con->factura_id = $ven->id;
              if(!$con->update()) {
                foreach ($ven->getMessages() as $m) {
                  $msj .= $m . "\n";
                }
              }
            }
          }  
          $this->response->setStatusCode(201, 'Created ' . $msj);
        } else {
          $msj = "No se pudo crear el nuevo registro: " . "\n";
          foreach ($ven->getMessages() as $m) {
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

  private function afectarInventario($item, $bod, $origen, $signo) {
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
      $kdx->actualizacion = date('Y-m-d H:i:s');
      if ($kdx->update()) {
        $msj = "Kardex actualizado";
      } else {
        $msj = "No se pudo actualizarar el kardex: " . "\n";
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
        $msj = "Kardex registrado";
      } else {
        $msj = "No se pudo reistrar el kardex: " . "\n";
        foreach ($kdx->getMessages() as $m) {
          $msj .= $m . "\n";
        }
      }
    }
    return $msj;
  }

  private function ultimoNumeroVenta($tipo) {
    return Ventas::maximum([
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
      return 0;
    }
  }

  public function ventaPorIdAction() {
    $id = $this->dispatcher->getParam('id');
    $res = Ventas::findFirstById($id);
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

  public function ventaModificarEstadoAction() {
    $id = $this->dispatcher->getParam('id');
    $est = $this->dispatcher->getParam('estado');
    $ven = Ventas::findFirstById($id);
    if ($ven != false) {
      $ven->estado = $est;
      if($ven->update()) {
        $msj = "La operacion se ejecuto exitosamente";
        $this->response->setStatusCode(200, 'Ok');
      } else {
        $this->response->setStatusCode(404, 'Error');
        $msj = "No se puede actualizar los datos: " . "\n";
        foreach ($ven->getMessages() as $m) {
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