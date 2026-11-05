<?php

declare(strict_types=1);

// Cria (ou atualiza a password de) um administrador.
// Uso: php bin/criar-admin.php nome_utilizador
//
// Não há nenhum admin por defeito de propósito: assim não fica
// nenhuma password conhecida tipo "admin/admin" no repositório.

if (PHP_SAPI !== 'cli') {
    exit('Só pode ser corrido na linha de comandos.');
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/src/helpers.php';
require BASE_PATH . '/src/Database.php';

loadEnv(BASE_PATH . '/.env');

$username = $argv[1] ?? '';

if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
    fwrite(STDERR, "Uso: php bin/criar-admin.php nome_utilizador\n");
    fwrite(STDERR, "(3 a 50 caracteres: letras, números, _ . -)\n");
    exit(1);
}

echo 'Password (mín. 10 caracteres): ';
// esconde o que se escreve no terminal (Linux/macOS)
$hidden = stripos(PHP_OS, 'WIN') !== 0 && stream_isatty(STDIN);
if ($hidden) {
    shell_exec('stty -echo');
}
$password = trim((string) fgets(STDIN));
if ($hidden) {
    shell_exec('stty echo');
}
echo PHP_EOL;

if (strlen($password) < 10 || strlen($password) > 72) {
    fwrite(STDERR, "A password tem de ter entre 10 e 72 caracteres.\n");
    exit(1);
}

$stmt = db()->prepare(
    'INSERT INTO admins (username, password_hash) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)'
);
$stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);

echo "Admin \"$username\" guardado.\n";
