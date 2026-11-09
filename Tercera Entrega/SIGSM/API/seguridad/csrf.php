<?php

require_once __DIR__ . '/../../Backend/config/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {

    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);

    exit;
}

echo json_encode(['ok' => true, 'csrf_token' => obtenerTokenCsrf()]);
