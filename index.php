<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <meta name="robots" content="noindex">
    <title>Admin · <?= e(config('salon_name')) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin">

<?php if (!isAdmin()): ?>
    <main class="wrap wrap--narrow">
        <form id="login-form" class="card">
            <h1>Área do salão</h1>
            <div class="field">
                <label for="username">Utilizador</label>
                <input type="text" id="username" autocomplete="username" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" autocomplete="current-password" required>
            </div>
            <div id="login-message" class="message" role="alert" hidden></div>
            <button type="submit" class="btn">Entrar</button>
        </form>
    </main>
<?php else: ?>
    <header class="admin-bar">
        <div class="wrap admin-bar__inner">
            <strong><?= e(config('salon_name')) ?></strong>
            <span>
                <?= e($_SESSION['admin_name']) ?> ·
                <button type="button" id="logout" class="link-btn">Sair</button>
            </span>
        </div>
    </header>

    <main class="wrap">
        <div class="day-nav">
            <button type="button" class="btn btn--ghost" data-shift="-1" aria-label="Dia anterior">&larr;</button>
            <input type="date" id="admin-date" value="<?= date('Y-m-d') ?>">
            <button type="button" class="btn btn--ghost" data-shift="1" aria-label="Dia seguinte">&rarr;</button>
            <button type="button" class="btn btn--ghost" id="today-btn">Hoje</button>
        </div>

        <p id="day-summary" class="muted"></p>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Hora</th>
                    <th>Cliente</th>
                    <th>Serviço</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
                </thead>
                <tbody id="bookings-body"></tbody>
            </table>
        </div>
    </main>
<?php endif; ?>

<script src="/assets/js/api.js"></script>
<script src="/assets/js/admin.js"></script>
</body>
</html>
