<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../../CONEXION_CON_UTF8.PHP';
require_once __DIR__ . '/../../FUNCIONES/roles.php';
require_once __DIR__ . '/../../MODELS/mesa_trabajo_model.php';

function responder($ok, $mensaje, $extra = [])
{
    echo json_encode(array_merge(['ok' => $ok, 'mensaje' => $mensaje], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Método no permitido.');
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['id'])) {
    responder(false, 'Datos inválidos.');
}

$id_empleado = isset($_SESSION['id_empleado']) ? (int)$_SESSION['id_empleado'] : 0;
if ($id_empleado <= 0) {
    responder(false, 'No se identificó al usuario.');
}

$tipo_usuario = obtener_tipo_usuario_mesa_trabajo($conn, $id_empleado);
if (!puede_entrar_desarrollo_mesa($tipo_usuario, $id_empleado)) {
    responder(false, 'No tienes permiso para editar este control.');
}

$solo_propias = ($tipo_usuario !== 'supervisor');
$id_incidencia = (int)$data['id'];

if (!puede_editar_incidencia_mesa($conn, $id_incidencia, $id_empleado, $solo_propias)) {
    responder(false, 'No tienes permiso para editar esta incidencia.');
}

$campos = isset($data['campos']) && is_array($data['campos']) ? $data['campos'] : [];

foreach (['fecha_reporte_inci', 'fecha_resol_inci'] as $campoFecha) {
    if (isset($campos[$campoFecha]) && $campos[$campoFecha] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $campos[$campoFecha])) {
        responder(false, 'Fecha inválida en ' . $campoFecha . '.');
    }
}

$resultado = actualizar_control_mensual_mesa($conn, $id_incidencia, $campos, $id_empleado);
echo json_encode($resultado);
?>
