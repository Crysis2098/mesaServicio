<?php
/*
    MESA DE SERVICIO
    Patrón basado en ACTIVACIONES_SL:
    - index.php funciona como controlador/routing simple.
    - MODELS contiene funciones de BD.
    - VIEWS contiene pantallas.
    - AJAX contiene endpoints para Fetch API.
    - CSS/styleadmon.css contiene headeringo.
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* SOLO PRUEBAS LOCALES
   En producción, estas variables vendrán heredadas del index global de TVTAS.
*/
$id_empleado = 5274;
$callcenter = 1;
$id_perfil = 15;

/*
   IMPORTANTE:
   No se permite cambiar usuario por URL.
   Para pruebas locales modifica únicamente las variables anteriores.
   En producción serán heredadas del index global de TVTAS.
*/

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../CONEXION_CON_UTF8.PHP';

if (file_exists('detectar_plantilla.php')) {
    include 'detectar_plantilla.php';
}

include 'FUNCIONES/usuarios.php';
include 'FUNCIONES/roles.php';
include 'MODELS/mesa_trabajo_model.php';

/*
    Variables globales heredadas del sistema principal.
    En producción no se declaran aquí; ya existirán desde TVTAS/index.php.
*/
$id_empleado = (int)$id_empleado;
$callcenter = (int)$callcenter;
$id_perfil = (int)$id_perfil;

/*
    Se guardan en sesión para que los endpoints AJAX puedan identificar
    al mismo usuario durante la simulación local.
*/
$_SESSION['id_empleado'] = $id_empleado;
$_SESSION['callcenter'] = $callcenter;
$_SESSION['id_perfil'] = $id_perfil;

/*
    Arrays de permisos desde BD.
*/
$usuarios_activos = obtener_usuarios_activos_mesa_servicio($conn);
$desarrolladores_mesa = obtener_desarrolladores_mesa_servicio_bd($conn);
$supervisores_mesa = obtener_supervisores_mesa_servicio($conn);
$ejecutivos_mesa = obtener_ejecutivos_mesa_servicio($conn);
$administrativos_mesa = obtener_administrativos_mesa_servicio($conn);

/*
    Tipo de usuario dentro del módulo.
*/
$tipo_usuario = obtener_tipo_usuario_mesa_servicio(
    $id_empleado,
    $desarrolladores_mesa,
    $supervisores_mesa,
    $ejecutivos_mesa,
    $administrativos_mesa,
    $usuarios_activos
);

/*
    Temporal: visibilidad local mientras no está en producción.
    Puedes ampliar este arreglo si agregas más usuarios de prueba.
*/
$usuarios_prueba_permitidos = [5274, 5279, 6000];
if (!in_array($id_empleado, $usuarios_prueba_permitidos, true)) {
    include 'VIEWS/LAYOUTS/header.php';
    include 'VIEWS/LAYOUTS/acceso_denegado.php';
    echo '</body></html>';
    sqlsrv_close($conn);
    exit;
}

$empleado_actual = obtener_empleado_mesa($conn, $id_empleado);
$menu_botones = obtener_menu_por_tipo_mesa_servicio($tipo_usuario);
$puede_desarrollo = puede_entrar_desarrollo_mesa($tipo_usuario, $id_empleado);
$solo_propias = ($tipo_usuario !== 'supervisor');
$es_supervisor = ($tipo_usuario === 'supervisor');
$es_desarrollador = ($tipo_usuario === 'desarrollador');

/*
    Vista actual.
*/
$vista = isset($_GET['vista']) ? trim($_GET['vista']) : '';

if ($vista === '') {
    $vista = obtener_vista_inicial_mesa_servicio($tipo_usuario);
}

$vistas_permitidas = obtener_vistas_permitidas_mesa_servicio($tipo_usuario);
$vista_permitida = in_array($vista, $vistas_permitidas, true);

include 'VIEWS/LAYOUTS/header.php';

if ($tipo_usuario === 'sin_acceso' || $vista === 'sin_acceso' || !$vista_permitida) {
    include 'VIEWS/LAYOUTS/acceso_denegado.php';
    echo '</body></html>';
    sqlsrv_close($conn);
    exit;
}

include 'VIEWS/LAYOUTS/menu_principal.php';

echo '<br><br>';

switch ($vista) {
    case 'reportar_ticket':
        $nombre_reporta = isset($empleado_actual['nombre']) ? $empleado_actual['nombre'] : 'Usuario no detectado';
        include 'VIEWS/TICKETS/reportar.php';
        break;

    case 'bitacora':
    case 'bitacora_desarrollo':
        if (!$puede_desarrollo) {
            include 'VIEWS/LAYOUTS/acceso_denegado.php';
            break;
        }
        $jerarquia = obtener_jerarquia_mesa($conn, $id_empleado);
        $nombre_captura = isset($empleado_actual['nombre']) ? $empleado_actual['nombre'] : 'Usuario no detectado';
        $fecha_captura = date('Y-m-d H:i:s');
        $stmt_incidencias = listar_incidencias_mesa($conn, $id_empleado, $solo_propias);
        include 'VIEWS/BITACORA/index.php';
        break;

    case 'registro_semanal':
        if (!$puede_desarrollo) {
            include 'VIEWS/LAYOUTS/acceso_denegado.php';
            break;
        }
        $anio_actual = (int)date('Y');
        $mes_actual = (int)date('n');
        $year = isset($_GET['year']) ? (int)$_GET['year'] : $anio_actual;
        $month = isset($_GET['month']) ? (int)$_GET['month'] : $mes_actual;

        if ($year < 2000 || $year > 2100) {
            $year = $anio_actual;
        }
        if ($month < 1 || $month > 12) {
            $month = $mes_actual;
        }

        $resultado_semanal = listar_registro_semanal_mesa($conn, $id_empleado, $solo_propias, $year, $month);
        $stmt_incidencias = $resultado_semanal['stmt'];
        $tiempos_por_semana = $resultado_semanal['tiempos'];
        include 'VIEWS/BITACORA/registro_semanal.php';
        break;

    case 'asignaciones':
        if (!$puede_desarrollo) {
            include 'VIEWS/LAYOUTS/acceso_denegado.php';
            break;
        }
        $stmt_asignaciones = listar_tickets_asignados_mesa($conn, $id_empleado, $es_supervisor);
        include 'VIEWS/TICKETS/asignaciones.php';
        break;

    case 'asignar':
        if (!$puede_desarrollo) {
            include 'VIEWS/LAYOUTS/acceso_denegado.php';
            break;
        }
        $desarrolladores_mesa = listar_desarrolladores_mesa_servicio($conn);
        $stmt_tickets = listar_tickets_para_asignar_mesa($conn);
        include 'VIEWS/TICKETS/asignar.php';
        break;

    default:
        echo '<div id="cam_titulo"><span>Vista no encontrada</span></div>';
        echo '<div id="contenido_formulario"><section class="form-card"><div class="form-card-header"><h2>La vista solicitada no existe</h2><p>Regresa al menú principal.</p></div></section></div>';
        break;
}

echo '</body></html>';

// sqlsrv_close($conn);
?>
