<?php

namespace Pointerp\Modelos;

use Phalcon\Mvc\Model;

class Autorizaciones extends Modelo {

    protected $relReferencia;

    public function getRelReferencia() {
        return $this->relReferencia;
    }

    public function setRelReferencia($v) {
        $this->relReferencia = $v;
    }

    public function initialize() {
        $this->setSource('autorizaciones');

        $this->hasOne('usuario', Usuarios::class, 'id', [
            'alias'    => 'relUsuario',
        ]);

        $this->hasOne('rol', Roles::class, 'id', [
            'alias'    => 'relRol',
        ]);
    }

    public function cargarReferencia() {
        $datos = [
            'referencia' => 'No se ha establecido',
        ];
        /*if ($this->relPermiso != null) {
            switch ($this->relPermiso->tipo) {
                case 1: {// Referencia de catastro
                    switch ($this->relPermiso->origen) {
                        case 1: { // Catastro de cuentas de agua potable
                            $cta = AguaCuentas::finbById($this->referencia);
                            if ($cta != null) {
                                $con = 'No registrado';
                                if ($cta->relContribuyente != null)
                                    $con = $cta->relContribuyente->nombres;
                                $ctg = 'No registrado';
                                if ($cta->relCategoria != null)
                                    $ctg = $cta->relCategoria->denominacion;
                                $datos = [
                                    'codigo' => $cta->codigo,
                                    'contribuyente' => $con,
                                    'categoria' => $ctg,
                                ];
                            }
                            break;
                        }
                        case 2: { // Catastro de contribuyentes
                            $con = Contribuyentes::findById($this->referencia);
                            if ($con->count() > 0) {
                                $con = $con->getFirst();
                                $datos = [
                                    'origen' => 'Contribuyente',
                                    'cedula' => $con->identificacion,
                                    'nombres' => $con->nombres,
                                ];
                            }
                            break;
                        } // TODO: Agregar a autorizaciones: Catastro de usuarios, de tablas de validacion, etc.
                    }
                    break;
                }
                case 2: {// Referencia de emisiones
                    $datos =[
                        'tipoPermiso' => $this->relPermiso->tipo,
                        'tituloid' => 1,
                        'numero' => 100,
                        'concepto' => 'Rubro de prueba',
                        'valor' => 10,
                        'fecha' => '2020/04/28',
                        'liquidador' => 3,
                        'nombres' => 'Martin Barberan',
                    ];
                    break;
                }
                case 3: {// Referencia de recaudaciones
                    $datos = [
                        'tipoPermiso' => $this->relPermiso->tipo,
                        'tituloid' => 1,
                        'numero' => 100,
                        'concepto' => 'Concepto de prueba',
                        'valor' => 10,
                        'cajero' => 1,
                        'nombre' => 'Martin Barberan',
                        'fecha' => '2020/04/29',
                    ];
                    break;
                }
                // TODO (Boost) Agrergar referencia de contabilidad, maquinarias, equipos, etc
            }
        }*/
        $this->setRelReferencia($datos);
    }

    public function jsonSerialize () : array {
        $res = $this->toArray();
        if ($this->relUsuario != null) {   
            $res['relUsuario'] = $this->relUsuario->toArray();
        }

        if ($this->relPermiso != null) {   
            $res['relRol'] = $this->relRol->toArray();
            /*if ($this->relRol->relFuncion != null) {
                $res['relPermiso']['relFuncion'] = $this->relPermiso->relFuncion->toArray();
            }*/
        }

        if ($this->getRelReferencia() != null) {
            $res['relReferencia'] = $this->getRelReferencia();
        }

        return $res;
    }
}