<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/src/helpers.php';
require BASE_PATH . '/src/Database.php';
require BASE_PATH . '/src/RateLimiter.php';
require BASE_PATH . '/src/Agenda.php';

date_default_timezone_set('Europe/Lisbon');

loadEnv(BASE_PATH . '/.env');

$isProduction = env('APP_ENV', 'local') === 'production';

error_reporting(E_ALL);
ini_set('display_errors', '0'); // a API responde sempre em JSON, nunca com erros em HTML
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('salao_sess');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");

set_exception_handler(function (Throwable $e) use ($isProduction): void {
    error_log((string) $e);

    $message = $isProduction ? 'Erro interno. Tenta novamente.' : $e->getMessage();
    jsonResponse(['error' => $message], 500);
});
