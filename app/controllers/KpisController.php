<?php

namespace Pointerp\Controladores;

use Phalcon\Db;
use Phalcon\Db\Enum;

class KpisController extends ControllerBase
{
  public function kpisAction() {
    $this->view->disable();
    $desde = $this->dispatcher->getParam('desde');
    $hasta = $this->dispatcher->getParam('hasta');

    if (!$this->fechaValida($desde) || !$this->fechaValida($hasta)) {
      $this->responder(400, ['error' => 'Fechas inválidas, formato esperado: YYYY-MM-DD']);
      return;
    }

    try {
      $resultado = [
        'periodo' => ['desde' => $desde, 'hasta' => $hasta],
        'medicos' => $this->kpisMedicos($desde, $hasta),
        'farmacia' => $this->kpisFarmacia($desde, $hasta),
        'laboratorio' => $this->kpisLaboratorio($desde, $hasta),
        'equipos' => null,
        'costos_fijos' => null,
      ];
      $this->responder(200, $resultado);
    } catch (\Exception $e) {
      $this->responder(500, ['error' => 'Error calculando KPIs: ' . $e->getMessage()]);
    }
  }

  private function kpisMedicos(string $desde, string $hasta): array {
    $db = $this->db;

    $volumen = $db->fetchAll(
      "SELECT c.medico_id, m.codigo, CONCAT(m.titulo, ' ', m.nombres) AS medico, COUNT(*) AS consultas
       FROM mdconsultas c
       LEFT JOIN medicos m ON m.id = c.medico_id
       WHERE c.fecha BETWEEN :desde AND :hasta
       GROUP BY c.medico_id, m.codigo, m.nombres, m.titulo
       ORDER BY consultas DESC",
      Enum::FETCH_ASSOC,
      ['desde' => $desde, 'hasta' => $hasta]
    );

    $serieMensual = $db->fetchAll(
      "SELECT DATE_FORMAT(c.fecha, '%Y-%m') AS mes, COUNT(*) AS consultas
       FROM mdconsultas c
       WHERE c.fecha BETWEEN :desde AND :hasta
       GROUP BY mes ORDER BY mes",
      Enum::FETCH_ASSOC,
      ['desde' => $desde, 'hasta' => $hasta]
    );

    $tieneTarifa = $this->existeColumna('medicos', 'tarifa');
    $costos = [];
    if ($tieneTarifa) {
      $rows = $db->fetchAll(
        "SELECT c.medico_id, m.codigo, CONCAT(m.titulo, ' ', m.nombres) AS medico,
                m.tarifa AS tarifa_pct,
                AVG(s.valor) AS valor_promedio_consulta,
                AVG(s.valor) * (m.tarifa / 100.0) AS costo_medico_por_consulta,
                COUNT(*) AS consultas
         FROM mdconsultas c
         LEFT JOIN medicos m ON m.id = c.medico_id
         LEFT JOIN mdservicios s ON s.id = c.servicio_id
         WHERE c.fecha BETWEEN :desde AND :hasta
         GROUP BY c.medico_id, m.codigo, m.nombres, m.titulo, m.tarifa
         ORDER BY costo_medico_por_consulta DESC",
        Enum::FETCH_ASSOC,
        ['desde' => $desde, 'hasta' => $hasta]
      );
      foreach ($rows as $r) {
        $valor = (float)$r['valor_promedio_consulta'];
        $pct = (float)$r['tarifa_pct'];
        $costos[] = [
          'medico_id' => (int)$r['medico_id'],
          'codigo' => $r['codigo'],
          'medico' => $r['medico'],
          'consultas' => (int)$r['consultas'],
          'tarifa_pct' => $pct,
          'valor_promedio_consulta' => round($valor, 2),
          'costo_medico_por_consulta' => round((float)$r['costo_medico_por_consulta'], 2),
          'porcentaje_sobre_precio' => $valor > 0 ? round($pct, 2) : null,
          'tarifa_registrada' => $pct > 0,
        ];
      }
    }

    $totalesConsultas = $db->fetchOne(
      "SELECT COUNT(*) AS total,
              SUM(CASE WHEN estado = 3 THEN 1 ELSE 0 END) AS terminadas
       FROM mdconsultas
       WHERE fecha BETWEEN :desde AND :hasta",
      Enum::FETCH_ASSOC,
      ['desde' => $desde, 'hasta' => $hasta]
    );

    $estadosConsultas = $db->fetchAll(
      "SELECT estado, COUNT(*) AS cantidad
       FROM mdconsultas
       WHERE fecha BETWEEN :desde AND :hasta
       GROUP BY estado",
      Enum::FETCH_ASSOC,
      ['desde' => $desde, 'hasta' => $hasta]
    );

    $totalConsultas = (int)($totalesConsultas['total'] ?? 0);
    $terminadas = (int)($totalesConsultas['terminadas'] ?? 0);
    $meses = $this->mesesEnPeriodo($desde, $hasta);
    $promedioMensual = $meses > 0 ? round($totalConsultas / $meses, 2) : null;

    return [
      'volumen_por_medico' => array_map(function ($v) {
        return [
          'medico_id' => (int)$v['medico_id'],
          'codigo' => $v['codigo'],
          'medico' => $v['medico'],
          'consultas' => (int)$v['consultas'],
        ];
      }, $volumen),
      'consultas_serie_mensual' => $serieMensual,
      'costo_medico_por_consulta' => $costos,
      'tarifa_configurada' => $tieneTarifa,
      'resumen_consultas' => [
        'total_consultas' => $totalConsultas,
        'promedio_mensual' => $promedioMensual,
        'terminadas_pct' => $totalConsultas > 0 ? round($terminadas / $totalConsultas * 100, 2) : null,
        'distribucion_estados' => $estadosConsultas,
      ],
    ];
  }

  private function kpisFarmacia(string $desde, string $hasta): array {
    $db = $this->db;

    $margenes = $db->fetchAll(
      "SELECT vi.producto_id, p.nombre AS medicamento,
              SUM(vi.cantidad) AS unidades_vendidas,
              AVG(vi.precio) AS precio_venta_promedio,
              AVG(NULLIF(vi.costo, 0)) AS costo_promedio_venta,
              p.ultimo_costo AS costo_ultima_compra,
              SUM(vi.precio * vi.cantidad) - SUM(COALESCE(NULLIF(vi.costo, 0), p.ultimo_costo, 0) * vi.cantidad) AS margen_total
       FROM ventas_items vi
       INNER JOIN ventas v ON v.id = vi.venta_id
       INNER JOIN productos p ON p.id = vi.producto_id
       WHERE v.fecha BETWEEN :desde AND :hasta
         AND p.tipo = 1
       GROUP BY vi.producto_id, p.nombre, p.ultimo_costo
       ORDER BY margen_total DESC",
      Enum::FETCH_ASSOC,
      ['desde' => $desde, 'hasta' => $hasta]
    );

    $listaMargenes = array_map(function ($m) {
      $precio = (float)$m['precio_venta_promedio'];
      $costo = (float)(($m['costo_promedio_venta'] !== null && (float)$m['costo_promedio_venta'] > 0)
        ? $m['costo_promedio_venta'] : $m['costo_ultima_compra']);
      $margenUnitario = $precio - $costo;
      return [
        'producto_id' => (int)$m['producto_id'],
        'medicamento' => $m['medicamento'],
        'unidades_vendidas' => (float)$m['unidades_vendidas'],
        'costo' => round($costo, 4),
        'precio_venta' => round($precio, 4),
        'margen_unitario' => round($margenUnitario, 4),
        'margen_pct' => $precio > 0 ? round($margenUnitario / $precio * 100, 2) : null,
        'margen_total_periodo' => round((float)$m['margen_total'], 2),
      ];
    }, $margenes);

    $totalesMargen = $db->fetchOne(
      "SELECT SUM(vi.precio * vi.cantidad) AS venta_total,
              SUM(COALESCE(NULLIF(vi.costo, 0), p.ultimo_costo, 0) * vi.cantidad) AS costo_total,
              COUNT(DISTINCT v.id) AS num_ventas_farmacia
       FROM ventas_items vi
       INNER JOIN ventas v ON v.id = vi.venta_id
       INNER JOIN productos p ON p.id = vi.producto_id
       WHERE v.fecha BETWEEN :desde AND :hasta
         AND p.tipo = 1",
      Enum::FETCH_ASSOC,
      ['desde' => $desde, 'hasta' => $hasta]
    );

    $ventaTotal = (float)($totalesMargen['venta_total'] ?? 0);
    $costoTotal = (float)($totalesMargen['costo_total'] ?? 0);
    $numVentas = (int)($totalesMargen['num_ventas_farmacia'] ?? 0);

    $inventario = $this->db->fetchOne(
      "SELECT SUM(k.ingresos - k.egresos) AS unidades_stock,
              SUM((k.ingresos - k.egresos) * COALESCE(p.ultimo_costo, 0)) AS valorizado
       FROM kardex k
       INNER JOIN productos p ON p.id = k.producto_id
       WHERE p.tipo = 1",
      Enum::FETCH_ASSOC
    );
    $stockValorizado = (float)($inventario['valorizado'] ?? 0);
    $meses = $this->mesesEnPeriodo($desde, $hasta);
    $rotacion = $stockValorizado > 0 ? ($costoTotal / $meses) / $stockValorizado : null;

    return [
      'margen_ganancia_bruto_por_medicamento' => $listaMargenes,
      'resumen_margen' => [
        'venta_total' => round($ventaTotal, 2),
        'costo_total' => round($costoTotal, 2),
        'margen_total' => round($ventaTotal - $costoTotal, 2),
        'margen_pct' => $ventaTotal > 0 ? round(($ventaTotal - $costoTotal) / $ventaTotal * 100, 2) : null,
      ],
      'rotacion_inventario' => [
        'costo_ventas_periodo' => round($costoTotal, 2),
        'inventario_valorizado_actual' => round($stockValorizado, 2),
        'rotacion_mensual' => $rotacion !== null ? round($rotacion, 4) : null,
        'nota' => 'Rotación mensual estimada: costo de ventas promedio del periodo dividido para el inventario valorizado actual',
      ],
      'ticket_promedio' => [
        'venta_total_farmacia' => round($ventaTotal, 2),
        'num_ventas_con_farmacia' => $numVentas,
        'ticket_promedio' => $numVentas > 0 ? round($ventaTotal / $numVentas, 2) : null,
      ],
    ];
  }

  private function kpisLaboratorio(string $desde, string $hasta): array {
    $db = $this->db;

    $volumen = $db->fetchOne(
      "SELECT COUNT(*) AS examenes_derivados,
              COUNT(DISTINCT ce.consulta_id) AS consultas_con_examen
       FROM mdconsultas_examenes ce
       INNER JOIN mdconsultas c ON c.id = ce.consulta_id
       WHERE c.fecha BETWEEN :desde AND :hasta",
      Enum::FETCH_ASSOC,
      ['desde' => $desde, 'hasta' => $hasta]
    );

    $serieMensual = $db->fetchAll(
      "SELECT DATE_FORMAT(c.fecha, '%Y-%m') AS mes, COUNT(*) AS examenes
       FROM mdconsultas_examenes ce
       INNER JOIN mdconsultas c ON c.id = ce.consulta_id
       WHERE c.fecha BETWEEN :desde AND :hasta
       GROUP BY mes ORDER BY mes",
      Enum::FETCH_ASSOC,
      ['desde' => $desde, 'hasta' => $hasta]
    );

    $topExamenes = $db->fetchAll(
      "SELECT r.denominacion AS examen, COUNT(*) AS cantidad
       FROM mdconsultas_examenes ce
       INNER JOIN mdconsultas c ON c.id = ce.consulta_id
       LEFT JOIN mdtablas_registros r ON r.id = ce.examen_id
       WHERE c.fecha BETWEEN :desde AND :hasta
       GROUP BY r.denominacion
       ORDER BY cantidad DESC
       LIMIT 15",
      Enum::FETCH_ASSOC,
      ['desde' => $desde, 'hasta' => $hasta]
    );

    return [
      'volumen_examenes_derivados' => [
        'total_periodo' => (int)($volumen['examenes_derivados'] ?? 0),
        'consultas_con_examen' => (int)($volumen['consultas_con_examen'] ?? 0),
        'serie_mensual' => $serieMensual,
        'top_examenes' => array_map(function ($e) {
          return ['examen' => $e['examen'], 'cantidad' => (int)$e['cantidad']];
        }, $topExamenes),
      ],
      'margen_intermediacion_laboratorio' => null,
      'tiempo_entrega_resultados' => null,
      'pendiente' => [
        'margen_intermediacion' => 'Requiere registrar el costo que cobra el laboratorio externo por examen',
        'tat' => 'Requiere registrar fecha de toma de muestra y fecha de entrega de resultados',
      ],
    ];
  }

  private function existeColumna(string $tabla, string $columna): bool {
    $row = $this->db->fetchOne(
      "SELECT COUNT(*) AS n FROM INFORMATION_SCHEMA.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla AND COLUMN_NAME = :col",
      Enum::FETCH_ASSOC,
      ['tabla' => $tabla, 'col' => $columna]
    );
    return (int)($row['n'] ?? 0) > 0;
  }

  private function mesesEnPeriodo(string $desde, string $hasta): float {
    $d1 = new \DateTime($desde);
    $d2 = new \DateTime($hasta);
    $dias = max($d1->diff($d2)->days, 1);
    return max($dias / 30.44, 1);
  }

  private function fechaValida(?string $fecha): bool {
    if ($fecha === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
      return false;
    }
    $d = \DateTime::createFromFormat('Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha;
  }

  private function responder(int $codigo, array $datos) {
    $this->response->setStatusCode($codigo, $codigo === 200 ? 'Ok' : 'Error');
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setContent(json_encode($datos, JSON_UNESCAPED_UNICODE));
    $this->response->send();
  }
}
