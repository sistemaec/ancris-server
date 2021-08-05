<?php

namespace Pointerp\Rutas;

class MedicosRutas extends \Phalcon\Mvc\Router\Group
{
  public function initialize()
  {
    $controlador = 'medicos';
    $this->setPaths(['namespace' => 'Pointerp\Controladores',]);
    $this->setPrefix('/api/v4/clinica');

    $this->addPost('/prueba/funcion', [
      'controller' => $controlador,
      'action'     => 'pruebaFuncion',
    ]);

    $this->addGet('/tablas/registros/{id}', [
      'controller' => $controlador,
      'action'     => 'clavePorId',
    ]);
    $this->addGet('/tablas/{tabla}/registros', [
      'controller' => $controlador,
      'action'     => 'clavesPorTabla',
    ]);

    $this->addGet('/pacientes/buscar/emp/{emp}/tipo/{tipo}/estado/{estado}/atributo/{atrib}/filtro/{filtro}', [
      'controller' => $controlador,
      'action'     => 'pacientesBuscar',
    ]);
    $this->addGet('/pacientes/{id}', [
      'controller' => $controlador,
      'action'     => 'pacientePorId',
    ]);
    $this->addGet('/paciente/cedula/{ced}', [
      'controller' => $controlador,
      'action'     => 'pacientePorCedula',
    ]);
    $this->addPost('/pacientes/crear', [
      'controller' => $controlador,
      'action'     => 'pacienteCrear',
    ]);
    $this->addPut('/pacientes/{id}/modificar', [
      'controller' => $controlador,
      'action'     => 'pacienteModificar',
    ]);
    $this->addPost('/pacientes/guardar', [
      'controller' => $controlador,
      'action'     => 'pacienteGuardar',
    ]);
    $this->addPut('/pacientes/{id}/modificar/estado/{estado}', [
      'controller' => $controlador,
      'action'     => 'pacienteModificarEstado',
    ]);
    $this->addGet('/pacientes/{id}/existe/{ced}/nombre/{nom}', [
      'controller' => $controlador,
      'action'     => 'pacienteRegistrado',
    ]);
    $this->addGet('/pacientes/{id}/cedula/{ced}/registrada', [
      'controller' => $controlador,
      'action'     => 'cedulaRegistrada',
    ]);

    $this->addGet('/medicos/buscar/emp/{emp}/tipo/{tipo}/estado/{estado}/atributo/{atrib}/filtro/{filtro}', [
      'controller' => $controlador,
      'action'     => 'medicosBuscar',
    ]);
    $this->addGet('/medicos/{id}', [
      'controller' => $controlador,
      'action'     => 'medicoPorId',
    ]);
    $this->addGet('/medicos/usuario/{id}', [
      'controller' => $controlador,
      'action'     => 'medicoPorUsuario',
    ]);
    $this->addGet('/medicos/estado/{estado}', [
      'controller' => $controlador,
      'action'     => 'medicosPorEstado',
    ]);
    $this->addGet('/medicos/cedula/{ced}', [
      'controller' => $controlador,
      'action'     => 'medicoPorCedula',
    ]);
    $this->addPost('/medicos/guardar', [
      'controller' => $controlador,
      'action'     => 'medicoGuardar',
    ]);
    $this->addPut('/medicos/{id}/modificar/estado/{estado}', [
      'controller' => $controlador,
      'action'     => 'medicoModificarEstado',
    ]);
    $this->addGet('/medicos/{id}/existe/{ced}/nombre/{nom}', [
      'controller' => $controlador,
      'action'     => 'medicoRegistrado',
    ]);
    $this->addGet('/medicos/{id}/cedula/{ced}/registrada', [
      'controller' => $controlador,
      'action'     => 'medicoCedulaRegistrada',
    ]);

    $this->addGet('/consultas/paciente/{id}/estado/{estado}', [
      'controller' => $controlador,
      'action'     => 'consultasPorPaciente',
    ]);
    $this->addGet('/consultas/medico/{id}/estado/{estado}', [
      'controller' => $controlador,
      'action'     => 'consultasPorMedico',
    ]);
    $this->addGet('/consultas/medico/{id}/desde/{desde}/hasta/{hasta}/estado/{estado}', [
      'controller' => $controlador,
      'action'     => 'consultasPorMedicoFecha',
    ]);
    $this->addGet('/consultas/desde/{desde}/hasta/{hasta}/estado/{estado}', [
      'controller' => $controlador,
      'action'     => 'consultasPorFecha',
    ]);
    $this->addGet('/consultas/motivo/{motivo}/estado/{estado}', [
      'controller' => $controlador,
      'action'     => 'consultasPorMotivo',
    ]);
    $this->addGet('/consultas/{id}', [
      'controller' => $controlador,
      'action'     => 'consultaPorId',
    ]);
    $this->addPost('/consultas/guardar', [
      'controller' => $controlador,
      'action'     => 'consultaGuardar',
    ]);
    $this->addPost('/consultas/{id}/mediciones/guardar', [
      'controller' => $controlador,
      'action'     => 'consultaGuardarMediciones',
    ]);
    $this->addPost('/consultas/{id}/factura/{fac}/actualizar', [
      'controller' => $controlador,
      'action'     => 'consultaGuardarFactura',
    ]);
    $this->addPost('/consultas/paciente/{id}/unificar', [
      'controller' => $controlador,
      'action'     => 'consultasUnificarPaciente',
    ]);
    $this->addPost('/consultas/{id}/atencion/guardar', [
      'controller' => $controlador,
      'action'     => 'consultaGuardarAtencion',
    ]);
    $this->addPost('/consultas/{id}/estado/{est}/actualizar', [
      'controller' => $controlador,
      'action'     => 'consultaGuardarEstado',
    ]);

    $this->addGet('/especialidades/todas', [
      'controller' => $controlador,
      'action'     => 'especialidadesTodas',
    ]);
    $this->addGet('/servicios/{id}', [
      'controller' => $controlador,
      'action'     => 'servicioPorId',
    ]);
    $this->addGet('/servicios/especialidad/{id}', [
      'controller' => $controlador,
      'action'     => 'serviciosPorEspecial',
    ]);
    $this->addGet('/servicios/buscar/tipo/{tipo}/estado/{estado}/filtro/{filtro}', [
      'controller' => $controlador,
      'action'     => 'serviciosBuscar',
    ]);
    $this->addPut('/servicios/{id}/modificar/estado/{estado}', [
      'controller' => $controlador,
      'action'     => 'servicioModificarEstado',
    ]);
    $this->addGet('/servicios/{id}/existe/{cod}/descripcion/{des}', [
      'controller' => $controlador,
      'action'     => 'servicioRegistrado', 
    ]);
    $this->addPost('/servicios/guardar', [
      'controller' => $controlador,
      'action'     => 'servicioGuardar',
    ]);

    $this->addGet('/consultas/paciente/{id}/limite/{limite}/consulta/{consulta}', [
      'controller' => $controlador,
      'action'     => 'historialPaciente',
    ]);
    $this->addGet('/examenes', [
      'controller' => $controlador,
      'action'     => 'examenesLista',
    ]);
    $this->addGet('/plantillas/{id}', [
      'controller' => $controlador,
      'action'     => 'plantillasPorId',
    ]);
    $this->addGet('/plantillas/{id}/campos', [
      'controller' => $controlador,
      'action'     => 'plantillaCamposPorId',
    ]);
    $this->addGet('/plantillas/estado/{estado}', [
      'controller' => $controlador,
      'action'     => 'plantillasPorEstado',
    ]);
  }
}