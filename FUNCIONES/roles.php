<?php
/*
    FUNCIONES/roles.php
    Mesa de Servicio

    Patrón basado en ACTIVACIONES_SL:
    - usuarios.php obtiene arreglos desde BD.
    - roles.php interpreta esos arreglos y arma el menú.
*/

function obtener_tipo_usuario_mesa_servicio(
    $id_empleado,
    $desarrolladores,
    $supervisores,
    $ejecutivos,
    $administrativos,
    $usuarios_activos = []
) {
    $id_empleado = (int)$id_empleado;

    if (!empty($usuarios_activos) && !in_array($id_empleado, $usuarios_activos, true)) {
        return 'sin_acceso';
    }

    if (in_array($id_empleado, $supervisores, true)) {
        return 'supervisor';
    }

    if (in_array($id_empleado, $desarrolladores, true)) {
        return 'desarrollador';
    }

    if (in_array($id_empleado, $ejecutivos, true)) {
        return 'ejecutivo';
    }

    if (in_array($id_empleado, $administrativos, true)) {
        return 'administrativo';
    }

    return 'usuario';
}

function puede_entrar_desarrollo_mesa($tipo_usuario, $id_empleado = 0)
{
    return in_array($tipo_usuario, ['supervisor', 'desarrollador'], true);
}

function obtener_menu_por_tipo_mesa_servicio($tipo_usuario)
{
    $base = 'index.php';

    if ($tipo_usuario === 'sin_acceso') {
        return [];
    }

    $menu = [
        [
            'label' => 'Reportar Ticket',
            'url' => $base . '?vista=reportar_ticket',
            'icono' => '📝'
        ]
    ];

    if (puede_entrar_desarrollo_mesa($tipo_usuario)) {
        $menu[] = [
            'label' => 'Bitácora - Desarrollo',
            'url' => $base . '?vista=bitacora_desarrollo',
            'icono' => '▦'
        ];
        $menu[] = [
            'label' => 'Registro Semanal',
            'url' => $base . '?vista=registro_semanal',
            'icono' => '▤'
        ];
        $menu[] = [
            'label' => 'Asignaciones',
            'url' => $base . '?vista=asignaciones',
            'icono' => '☑'
        ];
        $menu[] = [
            'label' => 'Asignar',
            'url' => $base . '?vista=asignar',
            'icono' => '✎'
        ];
    }

    return $menu;
}


function obtener_vistas_permitidas_mesa_servicio($tipo_usuario)
{
    switch ($tipo_usuario) {
        case 'supervisor':
        case 'desarrollador':
            return [
                'reportar_ticket',
                'bitacora',
                'bitacora_desarrollo',
                'registro_semanal',
                'asignaciones',
                'asignar'
            ];

        case 'ejecutivo':
        case 'administrativo':
        case 'usuario':
            return [
                'reportar_ticket'
            ];

        default:
            return [];
    }
}

function obtener_vista_inicial_mesa_servicio($tipo_usuario)
{
    switch ($tipo_usuario) {
        case 'supervisor':
        case 'desarrollador':
            return 'asignaciones';

        case 'ejecutivo':
        case 'administrativo':
        case 'usuario':
            return 'reportar_ticket';

        default:
            return 'sin_acceso';
    }
}

/* Compatibilidad con AJAX de versiones anteriores. */
function obtener_desarrolladores_mesa_servicio()
{
    return [5274];
}

function es_desarrollador_mesa_servicio($id_empleado)
{
    return in_array((int)$id_empleado, obtener_desarrolladores_mesa_servicio(), true);
}

function obtener_tipo_usuario_mesa_trabajo($conn, $id_empleado)
{
    $id_empleado = (int)$id_empleado;

    $sqlEmpleado = "SELECT TOP 1 id_empleado, id_perfil
                    FROM dbo.tbl_empleados
                    WHERE id_empleado = ? AND ISNULL(estatus, 1) = 1";
    $stmtEmpleado = sqlsrv_query($conn, $sqlEmpleado, [$id_empleado]);

    if ($stmtEmpleado === false) {
        return 'sin_acceso';
    }

    $empleado = sqlsrv_fetch_array($stmtEmpleado, SQLSRV_FETCH_ASSOC);
    if (!$empleado) {
        return 'sin_acceso';
    }

    $sqlSupervisor = "SELECT TOP 1 1 AS existe
                      FROM dbo.tbl_empleados_super
                      WHERE id_super = ? AND ISNULL(estatus, 1) = 1";
    $stmtSupervisor = sqlsrv_query($conn, $sqlSupervisor, [$id_empleado]);
    if ($stmtSupervisor !== false && sqlsrv_fetch_array($stmtSupervisor, SQLSRV_FETCH_ASSOC)) {
        return 'supervisor';
    }

    if ((int)$empleado['id_perfil'] === 15) {
        return 'desarrollador';
    }

    if ((int)$empleado['id_perfil'] === 1) {
        return 'ejecutivo';
    }

    return 'administrativo';
}

function obtener_menu_mesa_trabajo($tipo_usuario, $id_empleado = 0)
{
    return obtener_menu_por_tipo_mesa_servicio($tipo_usuario);
}
?>
