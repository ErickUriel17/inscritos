<?php
// ============================================================
// API: Aspirantes — CRUD via AJAX
// Archivo: assets/api/aspirantes_api.php
// NUEVO: endpoints JSON para operaciones desde JavaScript
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

// Protección básica: requiere sesión activa
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require_once __DIR__ . '/../sentenciasSQL/Aspirante.php';

$model = new Aspirante();

// GET: obtener un aspirante por ID
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accion = $_GET['accion'] ?? '';
    if ($accion === 'obtener') {
        $id = (int)($_GET['id'] ?? 0);
        $aspirante = $model->obtenerPorId($id);
        if ($aspirante) {
            echo json_encode(['ok' => true, 'aspirante' => $aspirante]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No encontrado']);
        }
    } elseif ($accion === 'listar') {
        $filtros = [
            'etapa'     => $_GET['etapa']    ?? '',
            'id_carrera'=> $_GET['carrera']  ?? '',
            'busqueda'  => $_GET['busqueda'] ?? '',
        ];
        $lista = $model->listar(array_filter($filtros));
        echo json_encode(['ok' => true, 'aspirantes' => $lista]);
    }
    exit();
}

// POST: crear, actualizar, eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true);
    $accion = $body['accion'] ?? '';

    switch ($accion) {
        case 'crear':
            $id = $model->crear([
                'nombre'     => trim($body['nombre']    ?? ''),
                'email'      => trim($body['email']     ?? ''),
                'telefono'   => trim($body['telefono']  ?? ''),
                'id_carrera' => $body['id_carrera']     ?: null,
                'etapa'      => $body['etapa']          ?? 'Contacto',
                'id_beca'    => $body['id_beca']        ?: null,
                'descuento'  => (float)($body['descuento'] ?? 0),
                'notas'      => trim($body['notas']     ?? ''),
            ]);
            echo json_encode($id
                ? ['ok' => true, 'id' => $id]
                : ['ok' => false, 'mensaje' => 'Error al crear el aspirante']
            );
            break;

        case 'actualizar':
            $id = (int)($body['id'] ?? 0);
            $ok = $model->actualizar($id, [
                'nombre'     => trim($body['nombre']    ?? ''),
                'email'      => trim($body['email']     ?? ''),
                'telefono'   => trim($body['telefono']  ?? ''),
                'id_carrera' => $body['id_carrera']     ?: null,
                'etapa'      => $body['etapa']          ?? 'Contacto',
                'id_beca'    => $body['id_beca']        ?: null,
                'descuento'  => (float)($body['descuento'] ?? 0),
                'notas'      => trim($body['notas']     ?? ''),
            ]);
            echo json_encode($ok
                ? ['ok' => true]
                : ['ok' => false, 'mensaje' => 'Error al actualizar']
            );
            break;

        case 'eliminar':
            $id = (int)($body['id'] ?? 0);
            $ok = $model->eliminar($id);
            echo json_encode(['ok' => $ok]);
            break;

        default:
            echo json_encode(['ok' => false, 'mensaje' => 'Acción desconocida']);
    }
    exit();
}

echo json_encode(['ok' => false, 'mensaje' => 'Método no soportado']);
