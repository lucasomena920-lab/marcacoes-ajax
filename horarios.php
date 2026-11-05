<?php

declare(strict_types=1);

// GET /api/horarios.php?servico=1&data=2026-11-20

require dirname(__DIR__, 2) . '/src/bootstrap.php';

allowMethods('GET');

$serviceId = filter_input(INPUT_GET, 'servico', FILTER_VALIDATE_INT) ?: 0;
$day = parseDate($_GET['data'] ?? null);

$service = Agenda::findService($serviceId);

if ($service === null) {
    jsonError('Serviço inválido.', 422);
}
if ($day === null) {
    jsonError('Data inválida.', 422);
}
if (!Agenda::isBookableDay($day)) {
    jsonError('Só é possível marcar a partir de hoje e até ' . config('max_days_ahead') . ' dias.', 422);
}

jsonResponse([
    'date'  => $day->format('Y-m-d'),
    'slots' => Agenda::freeSlots($day, (int) $service['duration_min']),
]);
