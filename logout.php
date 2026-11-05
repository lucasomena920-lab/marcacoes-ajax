<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

allowMethods('POST');
checkCsrf();

$_SESSION = [];
session_destroy();

jsonResponse(['ok' => true]);
