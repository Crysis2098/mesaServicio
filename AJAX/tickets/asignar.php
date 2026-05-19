<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../../CONEXION_CON_UTF8.PHP';
require_once __DIR__ . '/../../FUNCIONES/roles.php';
require_once __DIR__ . '/../../MODELS/mesa_trabajo_model.php';

function responder_asignar($ok, $mensaje, $extra = [])
{
    echo json_encode(array_merge(['ok' => $ok, 'mensaje' => $mensaje], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_asignar(false, 'Método no permitido.');
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['id_ticket'])) {
    responder_asignar(false, 'Datos inválidos.');
}

$id_empleado = isset($_SESSION['id_empleado']) ? (int)$_SESSION['id_empleado'] : 0;
$tipo_usuario = obtener_tipo_usuario_mesa_trabajo($conn, $id_empleado);
if (!puede_entrar_desarrollo_mesa($tipo_usuario, $id_empleado)) {
    responder_asignar(false, 'No tienes permiso para asignar tickets.');
}

$id_ticket = (int)$data['id_ticket'];
$id_desarrollador = isset($data['id_desarrollador']) ? (int)$data['id_desarrollador'] : 0;
$auto = isset($data['auto']) && (int)$data['auto'] === 1;
$comentario = trim($data['comentario_asignacion'] ?? '');

if ($auto) {
    $id_desarrollador = $id_empleado;
}

if ($id_desarrollador <= 0) {
    responder_asignar(false, 'Selecciona un desarrollador.');
}

$resultado = asignar_ticket_mesa($conn, $id_ticket, $id_desarrollador, $id_empleado, $comentario);
echo json_encode($resultado);
?>
