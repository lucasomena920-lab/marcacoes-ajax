<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

$today = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+' . config('max_days_ahead') . ' days'));
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <title>Marcar · <?= e(config('salon_name')) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="hero">
    <div class="wrap">
        <p class="hero__brand"><?= e(config('salon_name')) ?></p>
        <h1>Marca o teu horário</h1>
        <p class="hero__text">Escolhe o serviço, o dia e a hora. Demora menos de um minuto.</p>
    </div>
</header>

<main class="wrap">
    <form id="booking-form" class="booking" novalidate>
        <section class="step">
            <h2><span class="step__n">1</span> Serviço</h2>
            <div id="services" class="services" role="radiogroup" aria-label="Serviços">
                <p class="muted">A carregar serviços...</p>
            </div>
        </section>

        <section class="step">
            <h2><span class="step__n">2</span> Dia e hora</h2>
            <label for="date">Dia</label>
            <input type="date" id="date" name="data" min="<?= $today ?>" max="<?= $maxDate ?>" required>

            <div id="slots" class="slots" aria-live="polite">
                <p class="muted">Escolhe primeiro um serviço e um dia.</p>
            </div>
        </section>

        <section class="step">
            <h2><span class="step__n">3</span> Os teus dados</h2>

            <div class="field">
                <label for="name">Nome</label>
                <input type="text" id="name" name="nome" maxlength="80" autocomplete="name" required>
                <small class="field__error" data-error-for="nome"></small>
            </div>

            <div class="grid-2">
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" maxlength="190" autocomplete="email" required>
                    <small class="field__error" data-error-for="email"></small>
                </div>
                <div class="field">
                    <label for="phone">Telemóvel</label>
                    <input type="tel" id="phone" name="telefone" maxlength="20" autocomplete="tel" placeholder="912 345 678" required>
                    <small class="field__error" data-error-for="telefone"></small>
                </div>
            </div>

            <div class="field">
                <label for="notes">Notas <span class="muted">(opcional)</span></label>
                <textarea id="notes" name="notas" rows="2" maxlength="300"></textarea>
            </div>

            <!-- honeypot: escondido por CSS, só bots preenchem -->
            <div class="hp" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>
        </section>

        <div id="form-message" class="message" role="alert" hidden></div>

        <button type="submit" id="submit-btn" class="btn btn--big">Confirmar marcação</button>
    </form>

    <section id="success" class="success" hidden>
        <h2>Marcação feita!</h2>
        <p id="success-text"></p>
        <p class="muted">Se não puderes vir, liga-nos para avisar.</p>
        <button type="button" id="new-booking" class="btn">Fazer outra marcação</button>
    </section>
</main>

<footer class="footer wrap">
    <small>Projeto de demonstração · o salão não existe · <a href="/admin/">Área do salão</a></small>
</footer>

<script src="/assets/js/api.js"></script>
<script src="/assets/js/marcar.js"></script>
</body>
</html>
