<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../../CONEXION_CON_UTF8.PHP';
require_once __DIR__ . '/../../FUNCIONES/roles.php';
require_once __DIR__ . '/../../MODELS/mesa_trabajo_model.php';

function responder_actualizar_ticket($ok, $mensaje, $extra = [])
{
    echo json_encode(array_merge(['ok' => $ok, 'mensaje' => $mensaje], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_actualizar_ticket(false, 'Método no permitido.');
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['id_ticket'])) {
    responder_actualizar_ticket(false, 'Datos inválidos.');
}

$id_empleado = isset($_SESSION['id_empleado']) ? (int)$_SESSION['id_empleado'] : 0;
$tipo_usuario = obtener_tipo_usuario_mesa_trabajo($conn, $id_empleado);
$es_supervisor = ($tipo_usuario === 'supervisor');

if (!puede_entrar_desarrollo_mesa($tipo_usuario, $id_empleado)) {
    responder_actualizar_ticket(false, 'No tienes permiso para actualizar tickets.');
}

$id_ticket = (int)$data['id_ticket'];
if (!puede_actualizar_ticket_asignado_mesa($conn, $id_ticket, $id_empleado, $es_supervisor)) {
    responder_actualizar_ticket(false, 'Este ticket no está asignado a tu usuario.');
}

$resultado = actualizar_ticket_asignado_mesa($conn, $id_ticket, $data, $id_empleado);
echo json_encode($resultado);
?>
