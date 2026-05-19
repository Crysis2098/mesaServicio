<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../../CONEXION_CON_UTF8.PHP';
require_once __DIR__ . '/../../FUNCIONES/roles.php';
require_once __DIR__ . '/../../MODELS/mesa_trabajo_model.php';

function responder_ticket($ok, $mensaje, $extra = [])
{
    echo json_encode(array_merge(['ok' => $ok, 'mensaje' => $mensaje], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_ticket(false, 'Método no permitido.');
}

$id_empleado = isset($_SESSION['id_empleado']) ? (int)$_SESSION['id_empleado'] : 0;
if ($id_empleado <= 0) {
    responder_ticket(false, 'No se identificó al usuario.');
}

$empleado = obtener_empleado_mesa($conn, $id_empleado);
if (!$empleado) {
    responder_ticket(false, 'El empleado no existe o está inactivo.');
}

$titulo = trim($_POST['titulo_ticket'] ?? '');
$descripcion = trim($_POST['descripcion_ticket'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$prioridad = trim($_POST['prioridad'] ?? 'Media');
$modulo = trim($_POST['modulo_sistema'] ?? '');
$fuente = trim($_POST['fuente_solicitud'] ?? 'Mesa de Servicio');
$area = trim($_POST['area_reporta'] ?? '');
$nombre_reporta = trim($_POST['nombre_reporta'] ?? $empleado['nombre']);

if ($titulo === '' || $descripcion === '' || $categoria === '' || $prioridad === '') {
    responder_ticket(false, 'Completa título, descripción, categoría y prioridad.');
}

$permitidas = ['Baja', 'Media', 'Alta'];
if (!in_array($prioridad, $permitidas, true)) {
    $prioridad = 'Media';
}

$nombre_archivo = null;
if (isset($_FILES['captura_ticket']) && $_FILES['captura_ticket']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['captura_ticket']['tmp_name'];
    $original = $_FILES['captura_ticket']['name'];
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $permitidasExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($ext, $permitidasExt, true)) {
        responder_ticket(false, 'La evidencia debe ser imagen JPG, PNG, WEBP o GIF.');
    }

    $nombre_archivo = 'ticket_' . date('Ymd_His') . '_' . $id_empleado . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destino_dir = __DIR__ . '/../../UPLOADS/tickets/';
    if (!is_dir($destino_dir)) {
        mkdir($destino_dir, 0775, true);
    }

    if (!move_uploaded_file($tmp, $destino_dir . $nombre_archivo)) {
        responder_ticket(false, 'No se pudo guardar la evidencia.');
    }
}

$resultado = insertar_ticket_servicio_mesa($conn, [
    'id_empleado_reporta' => $id_empleado,
    'nombre_reporta' => $nombre_reporta,
    'area_reporta' => $area,
    'titulo_ticket' => $titulo,
    'descripcion_ticket' => $descripcion,
    'modulo_sistema' => $modulo,
    'categoria' => $categoria,
    'prioridad' => $prioridad,
    'fuente_solicitud' => $fuente,
    'captura_o_imagen' => $nombre_archivo
]);

echo json_encode($resultado);
?>
