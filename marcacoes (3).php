<?php

declare(strict_types=1);

// POST /api/marcacoes.php  (JSON)
// { servico, data, hora, nome, email, telefone, notas, website }

require dirname(__DIR__, 2) . '/src/bootstrap.php';

allowMethods('POST');
checkCsrf();

$input = jsonInput();

// "website" é um campo escondido (honeypot). Pessoas não o veem, bots preenchem.
if (!empty($input['website'])) {
    jsonResponse(['ok' => true]);
}

if (RateLimiter::tooMany('booking', 5, 60)) {
    jsonError('Fizeste demasiadas marcações seguidas. Tenta mais tarde ou liga-nos.', 429);
}

$errors = [];

$service = Agenda::findService((int) ($input['servico'] ?? 0));
$day = parseDate($input['data'] ?? null);
$time = is_string($input['hora'] ?? null) ? $input['hora'] : '';

$name = cleanString($input['nome'] ?? '', 80);
$email = mb_strtolower(cleanString($input['email'] ?? '', 190));
$phone = preg_replace('/[\s-]/', '', cleanString($input['telefone'] ?? '', 20));
$notes = cleanString($input['notas'] ?? '', 300);

if ($service === null) {
    $errors['servico'] = 'Escolhe um serviço.';
}
if ($day === null || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
    $errors['hora'] = 'Escolhe um dia e uma hora.';
}
if (mb_strlen($name) < 2) {
    $errors['nome'] = 'Indica o teu nome.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'O email não parece válido.';
}
// aceita números portugueses (9 dígitos) com ou sem +351
if (!preg_match('/^(\+351)?[29]\d{8}$/', $phone)) {
    $errors['telefone'] = 'Indica um número de telefone português válido.';
}

if ($errors) {
    jsonError('Há campos por corrigir.', 422, ['fields' => $errors]);
}

$start = new DateTimeImmutable($day->format('Y-m-d') . ' ' . $time);

$bookingId = Agenda::book($service, $start, [
    'name'  => $name,
    'email' => $email,
    'phone' => $phone,
    'notes' => $notes,
]);

if ($bookingId === null) {
    jsonError('Esse horário já não está disponível. Escolhe outro, por favor.', 409);
}

RateLimiter::hit('booking');

jsonResponse([
    'ok'      => true,
    'booking' => [
        'id'      => $bookingId,
        'service' => $service['name'],
        'date'    => $start->format('d/m/Y'),
        'time'    => $start->format('H:i'),
    ],
], 201);
