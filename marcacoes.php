<?php

declare(strict_types=1);

// GET  /api/admin/marcacoes.php?data=2026-11-20  -> lista do dia
// POST /api/admin/marcacoes.php  { id, estado }   -> muda o estado

require dirname(__DIR__, 3) . '/src/bootstrap.php';

allowMethods('GET', 'POST');
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $day = parseDate($_GET['data'] ?? null);

    if ($day === null) {
        jsonError('Data inválida.', 422);
    }

    jsonResponse(['bookings' => Agenda::bookingsForDay($day)]);
}

checkCsrf();

$input = jsonInput();
$id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
$status = $input['estado'] ?? '';

if (!$id || !in_array($status, ['confirmada', 'concluida', 'cancelada'], true)) {
    jsonError('Pedido inválido.', 422);
}

Agenda::updateStatus($id, $status);

jsonResponse(['ok' => true]);
