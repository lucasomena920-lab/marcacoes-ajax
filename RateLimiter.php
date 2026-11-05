<?php

declare(strict_types=1);

/**
 * Limite simples por IP guardado na base de dados.
 * Ex.: no máximo 5 marcações por hora vindas do mesmo IP.
 */
final class RateLimiter
{
    public static function tooMany(string $action, int $max, int $minutes): bool
    {
        $since = (new DateTimeImmutable("-$minutes minutes"))->format('Y-m-d H:i:s');

        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM rate_limits WHERE action = ? AND ip_address = ? AND hit_at > ?'
        );
        $stmt->execute([$action, clientIp(), $since]);

        return (int) $stmt->fetchColumn() >= $max;
    }

    public static function hit(string $action): void
    {
        db()->prepare('INSERT INTO rate_limits (action, ip_address, hit_at) VALUES (?, ?, ?)')
            ->execute([$action, clientIp(), date('Y-m-d H:i:s')]);

        // de vez em quando limpa registos antigos para a tabela não crescer
        if (random_int(1, 50) === 1) {
            db()->exec("DELETE FROM rate_limits WHERE hit_at < (NOW() - INTERVAL 1 DAY)");
        }
    }

    public static function clear(string $action): void
    {
        db()->prepare('DELETE FROM rate_limits WHERE action = ? AND ip_address = ?')
            ->execute([$action, clientIp()]);
    }
}
