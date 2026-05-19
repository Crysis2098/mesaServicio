<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../../CONEXION_CON_UTF8.PHP';
require_once __DIR__ . '/../../FUNCIONES/roles.php';
require_once __DIR__ . '/../../MODELS/mesa_trabajo_model.php';

function responder($ok, $mensaje, $extra = [])
{
    echo json_encode(array_merge([
        'ok' => $ok,
        'mensaje' => $mensaje
    ], $extra));
    exit;
}

function limpiar_texto($valor)
{
    return trim((string)$valor);
}

function subir_imagen_incidencia($archivo)
{
    if (!isset($archivo) || !isset($archivo['name']) || $archivo['name'] === '') {
        return null;
    }

    if (!isset($archivo['tmp_name']) || $archivo['tmp_name'] === '') {
        return null;
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $extensiones_permitidas, true)) {
        return false;
    }

    $carpeta_base = '../../UPLOADS/incidencias/';
    if (!is_dir($carpeta_base)) {
        mkdir($carpeta_base, 0777, true);
    }

    $nombre_nuevo = 'incidencia_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $extension;
    $ruta_destino = $carpeta_base . $nombre_nuevo;

    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        return false;
    }

    return $nombre_nuevo;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Método no permitido.');
}

$id_empleado = isset($_SESSION['id_empleado']) ? (int)$_SESSION['id_empleado'] : 0;

if ($id_empleado <= 0) {
    responder(false, 'No se identificó al usuario.');
}

$empleado = obtener_empleado_mesa($conn, $id_empleado);
if (!$empleado) {
    responder(false, 'El usuario no existe o no está activo.');
}

$tipo_usuario = obtener_tipo_usuario_mesa_trabajo($conn, $id_empleado);
if (!puede_entrar_desarrollo_mesa($tipo_usuario, $id_empleado)) {
    responder(false, 'No tienes permiso para registrar en Bitácora - Desarrollo.');
}

$incidencia = limpiar_texto($_POST['incidenciaReportada'] ?? '');
$descripcion = limpiar_texto($_POST['descripcion'] ?? '');
$reporta = limpiar_texto($_POST['nombreReportaInci'] ?? '');
$fuente = limpiar_texto($_POST['fuenteSolicitud'] ?? '');
$fecha_reporte = limpiar_texto($_POST['fecha_reporte'] ?? date('Y-m-d'));
$fecha_resolucion = limpiar_texto($_POST['fecha_resolucion'] ?? '');
$tiempo_resolucion = isset($_POST['tiempo_resolucion']) && $_POST['tiempo_resolucion'] !== '' ? (int)$_POST['tiempo_resolucion'] : 0;
$avance = isset($_POST['porcentaje_avance']) ? (int)$_POST['porcentaje_avance'] : 0;
$avance = max(0, min(100, $avance));
$solucionado = (isset($_POST['solucionado']) || $avance === 100) ? 1 : 0;

if ($incidencia === '') {
    responder(false, 'La incidencia o actividad es obligatoria.');
}

if ($reporta === '') {
    responder(false, 'El nombre de quien reporta es obligatorio.');
}

if ($fuente === '') {
    responder(false, 'La fuente de solicitud es obligatoria.');
}

if ($tiempo_resolucion < 0) {
    responder(false, 'El tiempo de resolución no puede ser menor a cero.');
}

$nombre_imagen = null;
if (isset($_FILES['capturaSolicitanteImg'])) {
    $subida = subir_imagen_incidencia($_FILES['capturaSolicitanteImg']);
    if ($subida === false) {
        responder(false, 'La imagen no pudo subirse o tiene un formato no permitido.');
    }
    $nombre_imagen = $subida;
}

$data = [
    'id_empleado_captura' => $id_empleado,
    'nombre_captura' => $empleado['nombre'],
    'fecha_captura' => date('Y-m-d H:i:s'),
    'fecha_reporte_inci' => $fecha_reporte,
    'fecha_resol_inci' => $fecha_resolucion !== '' ? $fecha_resolucion : null,
    'tiempo_resolucion' => $tiempo_resolucion,
    'incidencia_reportada' => $incidencia,
    'descripcion_incidencia' => $descripcion,
    'nombre_quien_reporta' => $reporta,
    'fuente_solicitud' => $fuente,
    'captura_o_imagen' => $nombre_imagen,
    'solucionado' => $solucionado,
    'avance_status' => $avance
];

$resultado = insertar_incidencia_mesa($conn, $data);

if (!$resultado['ok']) {
    responder(false, 'No fue posible guardar la incidencia.', [
        'debug' => $resultado['errores'] ?? null
    ]);
}

responder(true, 'Incidencia registrada correctamente.');
?>
