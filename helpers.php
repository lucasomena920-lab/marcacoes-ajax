<?php

declare(strict_types=1);

function loadEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (getenv($key) === false) {
            putenv($key . '=' . trim($value, "\"'"));
        }
    }
}

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function config(string $key): mixed
{
    static $config = null;
    $config ??= require BASE_PATH . '/src/config.php';
    return $config[$key] ?? null;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $status = 400, array $extra = []): never
{
    jsonResponse(['error' => $message] + $extra, $status);
}

function allowMethods(string ...$methods): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        jsonError('Método não permitido.', 405);
    }
}

/** Lê o corpo do pedido em JSON. Só aceita Content-Type application/json. */
function jsonInput(): array
{
    $type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (!str_starts_with($type, 'application/json')) {
        jsonError('Content-Type tem de ser application/json.', 415);
    }

    $raw = file_get_contents('php://input', length: 10_000);
    $data = json_decode($raw ?: '', true);

    if (!is_array($data)) {
        jsonError('JSON inválido.');
    }

    return $data;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Nos pedidos fetch o token vai no cabeçalho X-CSRF-Token */
function checkCsrf(): void
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        jsonError('Sessão expirada. Atualiza a página.', 419);
    }
}

function isAdmin(): bool
{
    return isset($_SESSION['admin_id']);
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        jsonError('Não autorizado.', 401);
    }
}

function clientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Valida uma data no formato AAAA-MM-DD e devolve-a como objeto (ou null) */
function parseDate(mixed $value): ?DateTimeImmutable
{
    if (!is_string($value)) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return ($date && $date->format('Y-m-d') === $value) ? $date : null;
}

function cleanString(mixed $value, int $max): string
{
    if (!is_string($value)) {
        return '';
    }
    // tira caracteres de controlo e espaços a mais
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
    return mb_substr(trim($value), 0, $max);
}
