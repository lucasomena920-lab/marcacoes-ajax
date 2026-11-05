<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

allowMethods('POST');
checkCsrf();

if (RateLimiter::tooMany('admin_login', 5, 15)) {
    jsonError('Demasiadas tentativas. Espera 15 minutos.', 429);
}

$input = jsonInput();
$username = cleanString($input['username'] ?? '', 50);
$password = is_string($input['password'] ?? null) ? $input['password'] : '';

$stmt = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = ?');
$stmt->execute([$username]);
$admin = $stmt->fetch();

// password_verify corre sempre, mesmo sem utilizador, para o tempo de resposta
// não denunciar se o username existe
$hash = $admin['password_hash'] ?? '$2y$10$JXKZFpodEAHLFhc2cS7eNu66GRudAjLsEHMv7yLaWXOfQkfPjs1mW';
$valid = password_verify($password, $hash) && $admin;

if (!$valid) {
    RateLimiter::hit('admin_login');
    jsonError('Credenciais inválidas.', 401);
}

RateLimiter::clear('admin_login');

session_regenerate_id(true);
$_SESSION['admin_id'] = (int) $admin['id'];
$_SESSION['admin_name'] = $admin['username'];
// novo token CSRF depois do login
$_SESSION['csrf'] = bin2hex(random_bytes(32));

jsonResponse(['ok' => true, 'csrf' => $_SESSION['csrf']]);
