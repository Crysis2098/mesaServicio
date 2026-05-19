<?php
function obtener_empleado_mesa($conn, $id_empleado)
{
    $sql = "SELECT id_empleado, nombre, id_perfil, id_area, callcenter, estatus
            FROM dbo.tbl_empleados
            WHERE id_empleado = ? AND ISNULL(estatus, 1) = 1";
    $stmt = sqlsrv_query($conn, $sql, [(int)$id_empleado]);
    if ($stmt === false) {
        return null;
    }
    return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: null;
}

function obtener_superior_mesa($conn, $id_empleado)
{
    $sql = "SELECT TOP 1 id_super
            FROM dbo.tbl_empleados_super
            WHERE id_empleado = ? AND ISNULL(estatus, 1) = 1";
    $stmt = sqlsrv_query($conn, $sql, [(int)$id_empleado]);
    if ($stmt === false) {
        return null;
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row ? (int)$row['id_super'] : null;
}

function es_supervisor_mesa($conn, $id_empleado)
{
    $sql = "SELECT TOP 1 1 AS existe
            FROM dbo.tbl_empleados_super
            WHERE id_super = ? AND ISNULL(estatus, 1) = 1";
    $stmt = sqlsrv_query($conn, $sql, [(int)$id_empleado]);
    if ($stmt === false) {
        return false;
    }
    return (bool)sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}

function obtener_subordinados_mesa($conn, $id_supervisor)
{
    $sql = "SELECT id_empleado
            FROM dbo.tbl_empleados_super
            WHERE id_super = ? AND ISNULL(estatus, 1) = 1";
    $stmt = sqlsrv_query($conn, $sql, [(int)$id_supervisor]);
    if ($stmt === false) {
        return [];
    }

    $ids = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $ids[] = (int)$row['id_empleado'];
    }
    return $ids;
}

function obtener_jerarquia_mesa($conn, $id_empleado)
{
    $jerarquia = [];
    $empleado = obtener_empleado_mesa($conn, $id_empleado);

    if (!$empleado) {
        return $jerarquia;
    }

    $jerarquia['ejecutivo'] = [
        'id' => $empleado['id_empleado'],
        'nombre' => $empleado['nombre'],
        'id_perfil' => $empleado['id_perfil']
    ];

    $id_superior = obtener_superior_mesa($conn, $id_empleado);
    if ($id_superior) {
        $supervisor = obtener_empleado_mesa($conn, $id_superior);
        if ($supervisor) {
            $jerarquia['supervisor'] = [
                'id' => $supervisor['id_empleado'],
                'nombre' => $supervisor['nombre'],
                'id_perfil' => $supervisor['id_perfil']
            ];
        }
    }

    return $jerarquia;
}

function construir_where_visibilidad_mesa($conn, $id_empleado, $solo_propias, &$params)
{
    if ($solo_propias) {
        $params[] = (int)$id_empleado;
        return "id_empleado_captura = ?";
    }

    $ids = obtener_subordinados_mesa($conn, $id_empleado);
    $ids[] = (int)$id_empleado;
    $ids = array_values(array_unique($ids));

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    foreach ($ids as $id) {
        $params[] = $id;
    }

    return "id_empleado_captura IN ($placeholders)";
}

function listar_incidencias_mesa($conn, $id_empleado, $solo_propias)
{
    $params = [];
    $where_visibilidad = construir_where_visibilidad_mesa($conn, $id_empleado, $solo_propias, $params);

    $sql = "SELECT *
            FROM dbo.tbl_bitacora_incidencias_desarrollo
            WHERE ISNULL(eliminado, 0) = 0
              AND $where_visibilidad
            ORDER BY fecha_reporte_inci DESC, id_inci DESC";

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    return $stmt;
}

function insertar_incidencia_mesa($conn, $data)
{
    $sql = "INSERT INTO dbo.tbl_bitacora_incidencias_desarrollo (
                id_empleado_captura,
                nombre_captura,
                fecha_captura,
                fecha_reporte_inci,
                fecha_resol_inci,
                tiempo_resolucion,
                incidencia_reportada,
                descripcion_incidencia,
                nombre_quien_reporta,
                fuente_solicitud,
                captura_o_imagen,
                solucionado,
                avance_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $params = [
        $data['id_empleado_captura'],
        $data['nombre_captura'],
        $data['fecha_captura'],
        $data['fecha_reporte_inci'],
        $data['fecha_resol_inci'],
        $data['tiempo_resolucion'],
        $data['incidencia_reportada'],
        $data['descripcion_incidencia'],
        $data['nombre_quien_reporta'],
        $data['fuente_solicitud'],
        $data['captura_o_imagen'],
        $data['solucionado'],
        $data['avance_status']
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        return [
            'ok' => false,
            'errores' => sqlsrv_errors()
        ];
    }

    return ['ok' => true];
}

function listar_registro_semanal_mesa($conn, $id_empleado, $solo_propias, $anio, $mes)
{
    $params_base = [];
    $where_visibilidad = construir_where_visibilidad_mesa($conn, $id_empleado, $solo_propias, $params_base);
    $expr_semana_mes = "DATEPART(WEEK, fecha_reporte_inci) - DATEPART(WEEK, DATEADD(MONTH, DATEDIFF(MONTH, 0, fecha_reporte_inci), 0)) + 1";
    $where = "ISNULL(eliminado, 0) = 0
              AND $where_visibilidad
              AND YEAR(fecha_reporte_inci) = ?
              AND MONTH(fecha_reporte_inci) = ?";

    $params = array_merge($params_base, [(int)$anio, (int)$mes]);

    $sql_suma = "SELECT $expr_semana_mes AS semana_mes,
                        SUM(ISNULL(tiempo_resolucion, 0)) AS tiempo_semana_min
                 FROM dbo.tbl_bitacora_incidencias_desarrollo
                 WHERE $where
                 GROUP BY $expr_semana_mes";

    $stmt_suma = sqlsrv_query($conn, $sql_suma, $params);
    if ($stmt_suma === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $tiempos = [];
    while ($row = sqlsrv_fetch_array($stmt_suma, SQLSRV_FETCH_ASSOC)) {
        $tiempos[(int)$row['semana_mes']] = (int)$row['tiempo_semana_min'];
    }

    $sql_detalle = "SELECT *, $expr_semana_mes AS semana_mes
                    FROM dbo.tbl_bitacora_incidencias_desarrollo
                    WHERE $where
                    ORDER BY semana_mes ASC, avance_status ASC, fecha_reporte_inci DESC, id_inci DESC";

    $stmt_detalle = sqlsrv_query($conn, $sql_detalle, $params);
    if ($stmt_detalle === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    return [
        'stmt' => $stmt_detalle,
        'tiempos' => $tiempos
    ];
}

function puede_editar_incidencia_mesa($conn, $id_incidencia, $id_empleado, $solo_propias)
{
    $params = [(int)$id_incidencia];
    $where_visibilidad = construir_where_visibilidad_mesa($conn, $id_empleado, $solo_propias, $params);

    $sql = "SELECT TOP 1 id_inci
            FROM dbo.tbl_bitacora_incidencias_desarrollo
            WHERE id_inci = ?
              AND ISNULL(eliminado, 0) = 0
              AND $where_visibilidad";

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        return false;
    }

    return (bool)sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}

function actualizar_inline_mesa($conn, $id_incidencia, $avance, $fecha_resolucion, $actualizado_por)
{
    $campos = [];
    $params = [];

    if ($avance !== null) {
        $avance = max(0, min(100, (int)$avance));
        $campos[] = "avance_status = ?";
        $params[] = $avance;
        $campos[] = "solucionado = ?";
        $params[] = ($avance === 100) ? 1 : 0;
    }

    if ($fecha_resolucion !== null) {
        $campos[] = "fecha_resol_inci = ?";
        $params[] = ($fecha_resolucion === '') ? null : $fecha_resolucion;
    }

    if (!$campos) {
        return [
            'ok' => false,
            'mensaje' => 'No se recibió información para actualizar.'
        ];
    }

    $campos[] = "fecha_actualizacion = GETDATE()";
    $campos[] = "actualizado_por = ?";
    $params[] = (int)$actualizado_por;
    $params[] = (int)$id_incidencia;

    $sql = "UPDATE dbo.tbl_bitacora_incidencias_desarrollo
            SET " . implode(', ', $campos) . "
            WHERE id_inci = ?";

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        return [
            'ok' => false,
            'mensaje' => 'Error al actualizar en base de datos.',
            'errores' => sqlsrv_errors()
        ];
    }

    return [
        'ok' => true,
        'mensaje' => 'Actualización guardada correctamente.'
    ];
}


function actualizar_control_mensual_mesa($conn, $id_incidencia, $data, $actualizado_por)
{
    $campos = [];
    $params = [];

    $mapa = [
        'fecha_reporte_inci' => 'fecha_reporte_inci',
        'nombre_captura' => 'nombre_captura',
        'incidencia_reportada' => 'incidencia_reportada',
        'descripcion_incidencia' => 'descripcion_incidencia',
        'nombre_quien_reporta' => 'nombre_quien_reporta',
        'fuente_solicitud' => 'fuente_solicitud',
        'tiempo_resolucion' => 'tiempo_resolucion',
        'fecha_resol_inci' => 'fecha_resol_inci',
        'avance_status' => 'avance_status',
        'solucionado' => 'solucionado'
    ];

    foreach ($mapa as $key => $columna) {
        if (!array_key_exists($key, $data)) {
            continue;
        }

        $valor = $data[$key];

        if ($key === 'fecha_reporte_inci' || $key === 'fecha_resol_inci') {
            $valor = ($valor === '') ? null : $valor;
        }

        if ($key === 'tiempo_resolucion') {
            $valor = max(0, (int)$valor);
        }

        if ($key === 'avance_status') {
            $valor = max(0, min(100, (int)$valor));
        }

        if ($key === 'solucionado') {
            $valor = ((int)$valor === 1) ? 1 : 0;
        }

        $campos[] = "$columna = ?";
        $params[] = $valor;
    }

    if (array_key_exists('avance_status', $data) && (int)$data['avance_status'] === 100) {
        $campos[] = "solucionado = ?";
        $params[] = 1;
    }

    if (!$campos) {
        return ['ok' => false, 'mensaje' => 'No se recibió información para actualizar.'];
    }

    $campos[] = "fecha_actualizacion = GETDATE()";
    $campos[] = "actualizado_por = ?";
    $params[] = (int)$actualizado_por;
    $params[] = (int)$id_incidencia;

    $sql = "UPDATE dbo.tbl_bitacora_incidencias_desarrollo
            SET " . implode(', ', $campos) . "
            WHERE id_inci = ?";

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        return ['ok' => false, 'mensaje' => 'Error al actualizar control mensual.', 'errores' => sqlsrv_errors()];
    }

    return ['ok' => true, 'mensaje' => 'Registro actualizado correctamente.'];
}

function listar_desarrolladores_mesa_servicio($conn)
{
    $ids = obtener_desarrolladores_mesa_servicio();
    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT id_empleado, nombre
            FROM dbo.tbl_empleados
            WHERE id_empleado IN ($placeholders)
              AND ISNULL(estatus, 1) = 1
            ORDER BY nombre";
    $stmt = sqlsrv_query($conn, $sql, $ids);
    if ($stmt === false) {
        return [];
    }

    $devs = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $devs[] = $row;
    }
    return $devs;
}

function insertar_ticket_servicio_mesa($conn, $data)
{
    $sql = "INSERT INTO dbo.tbl_mesa_servicio_tickets (
                id_empleado_reporta,
                nombre_reporta,
                area_reporta,
                fecha_reporte,
                titulo_ticket,
                descripcion_ticket,
                modulo_sistema,
                categoria,
                prioridad,
                fuente_solicitud,
                captura_o_imagen,
                estatus_ticket,
                avance_status
            ) VALUES (?, ?, ?, GETDATE(), ?, ?, ?, ?, ?, ?, ?, 'NUEVO', 0)";

    $params = [
        $data['id_empleado_reporta'],
        $data['nombre_reporta'],
        $data['area_reporta'],
        $data['titulo_ticket'],
        $data['descripcion_ticket'],
        $data['modulo_sistema'],
        $data['categoria'],
        $data['prioridad'],
        $data['fuente_solicitud'],
        $data['captura_o_imagen']
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        return ['ok' => false, 'mensaje' => 'No se pudo registrar el ticket.', 'errores' => sqlsrv_errors()];
    }

    return ['ok' => true, 'mensaje' => 'Ticket enviado correctamente al equipo de desarrollo.'];
}

function listar_tickets_para_asignar_mesa($conn)
{
    $sql = "SELECT *
            FROM dbo.tbl_mesa_servicio_tickets
            WHERE ISNULL(eliminado, 0) = 0
              AND estatus_ticket IN ('NUEVO', 'ASIGNADO', 'EN_PROCESO')
            ORDER BY
                CASE prioridad WHEN 'Alta' THEN 1 WHEN 'Media' THEN 2 WHEN 'Baja' THEN 3 ELSE 4 END,
                fecha_reporte DESC,
                id_ticket DESC";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    return $stmt;
}

function listar_tickets_asignados_mesa($conn, $id_empleado, $ver_todos = false)
{
    $params = [];
    $where = "ISNULL(eliminado, 0) = 0";

    if (!$ver_todos) {
        $where .= " AND id_desarrollador_asignado = ?";
        $params[] = (int)$id_empleado;
    }

    $sql = "SELECT *
            FROM dbo.tbl_mesa_servicio_tickets
            WHERE $where
              AND estatus_ticket IN ('ASIGNADO', 'EN_PROCESO', 'RESUELTO')
            ORDER BY fecha_asignacion DESC, fecha_reporte DESC, id_ticket DESC";
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    return $stmt;
}

function obtener_ticket_mesa($conn, $id_ticket)
{
    $sql = "SELECT TOP 1 *
            FROM dbo.tbl_mesa_servicio_tickets
            WHERE id_ticket = ? AND ISNULL(eliminado, 0) = 0";
    $stmt = sqlsrv_query($conn, $sql, [(int)$id_ticket]);
    if ($stmt === false) {
        return null;
    }
    return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: null;
}

function asignar_ticket_mesa($conn, $id_ticket, $id_desarrollador, $asignado_por, $comentario = '')
{
    $dev = obtener_empleado_mesa($conn, $id_desarrollador);
    if (!$dev) {
        return ['ok' => false, 'mensaje' => 'El desarrollador seleccionado no existe o no está activo.'];
    }

    if (!es_desarrollador_mesa_servicio($id_desarrollador)) {
        return ['ok' => false, 'mensaje' => 'El empleado seleccionado no está configurado como desarrollador de Mesa de Servicio.'];
    }

    $sql = "UPDATE dbo.tbl_mesa_servicio_tickets
            SET id_desarrollador_asignado = ?,
                nombre_desarrollador_asignado = ?,
                estatus_ticket = 'ASIGNADO',
                fecha_asignacion = GETDATE(),
                asignado_por = ?,
                comentario_asignacion = ?,
                fecha_actualizacion = GETDATE(),
                actualizado_por = ?
            WHERE id_ticket = ?
              AND ISNULL(eliminado, 0) = 0";

    $params = [
        (int)$id_desarrollador,
        $dev['nombre'],
        (int)$asignado_por,
        $comentario,
        (int)$asignado_por,
        (int)$id_ticket
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        return ['ok' => false, 'mensaje' => 'No se pudo asignar el ticket.', 'errores' => sqlsrv_errors()];
    }

    return ['ok' => true, 'mensaje' => 'Ticket asignado correctamente.'];
}

function puede_actualizar_ticket_asignado_mesa($conn, $id_ticket, $id_empleado, $es_supervisor)
{
    $ticket = obtener_ticket_mesa($conn, $id_ticket);
    if (!$ticket) {
        return false;
    }

    if ($es_supervisor) {
        return true;
    }

    return ((int)($ticket['id_desarrollador_asignado'] ?? 0) === (int)$id_empleado);
}

function actualizar_ticket_asignado_mesa($conn, $id_ticket, $data, $actualizado_por)
{
    $estatus = isset($data['estatus_ticket']) ? trim($data['estatus_ticket']) : 'ASIGNADO';
    $permitidos = ['ASIGNADO', 'EN_PROCESO', 'RESUELTO', 'CANCELADO'];
    if (!in_array($estatus, $permitidos, true)) {
        $estatus = 'ASIGNADO';
    }

    $avance = isset($data['avance_status']) ? max(0, min(100, (int)$data['avance_status'])) : 0;
    if ($estatus === 'RESUELTO') {
        $avance = 100;
    }

    $solucion = isset($data['solucion_ticket']) ? trim($data['solucion_ticket']) : '';

    $sql = "UPDATE dbo.tbl_mesa_servicio_tickets
            SET estatus_ticket = ?,
                avance_status = ?,
                solucion_ticket = ?,
                fecha_resolucion = CASE WHEN ? = 'RESUELTO' THEN ISNULL(fecha_resolucion, GETDATE()) ELSE fecha_resolucion END,
                fecha_actualizacion = GETDATE(),
                actualizado_por = ?
            WHERE id_ticket = ?
              AND ISNULL(eliminado, 0) = 0";

    $params = [$estatus, $avance, $solucion, $estatus, (int)$actualizado_por, (int)$id_ticket];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        return ['ok' => false, 'mensaje' => 'No se pudo actualizar el ticket.', 'errores' => sqlsrv_errors()];
    }

    return ['ok' => true, 'mensaje' => 'Ticket actualizado correctamente.'];
}
?>
