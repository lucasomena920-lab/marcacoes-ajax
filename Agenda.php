<?php

declare(strict_types=1);

/**
 * Calcula os horários livres de um dia e grava marcações.
 * O salão tem só uma cadeira/profissional, por isso duas marcações
 * nunca se podem sobrepor.
 */
final class Agenda
{
    public static function activeServices(): array
    {
        return db()->query(
            'SELECT id, name, duration_min, price_cents FROM services WHERE active = 1 ORDER BY id'
        )->fetchAll();
    }

    public static function findService(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM services WHERE id = ? AND active = 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** O dia está dentro do intervalo em que é permitido marcar? */
    public static function isBookableDay(DateTimeImmutable $day): bool
    {
        $today = new DateTimeImmutable('today');
        $limit = $today->modify('+' . config('max_days_ahead') . ' days');

        return $day >= $today && $day <= $limit;
    }

    /**
     * Devolve a lista de horas livres ("09:00", "09:15", ...) para um serviço num dia.
     */
    public static function freeSlots(DateTimeImmutable $day, int $durationMin): array
    {
        if (!self::isBookableDay($day)) {
            return [];
        }

        $blocks = config('opening_hours')[(int) $day->format('N')] ?? [];
        if (!$blocks) {
            return [];
        }

        $busy = self::busyPeriods($day);
        $earliest = new DateTimeImmutable('+' . config('min_notice_min') . ' minutes');
        $step = config('slot_step_min');
        $slots = [];

        foreach ($blocks as [$open, $close]) {
            $start = new DateTimeImmutable($day->format('Y-m-d') . ' ' . $open);
            $blockEnd = new DateTimeImmutable($day->format('Y-m-d') . ' ' . $close);

            while (($end = $start->modify("+$durationMin minutes")) <= $blockEnd) {
                if ($start >= $earliest && !self::overlaps($start, $end, $busy)) {
                    $slots[] = $start->format('H:i');
                }
                $start = $start->modify("+$step minutes");
            }
        }

        return $slots;
    }

    /**
     * Grava a marcação. Usa um lock do MySQL para que dois pedidos ao mesmo
     * tempo não consigam ficar com o mesmo horário (race condition).
     * Devolve o id da marcação ou null se o horário já não estiver livre.
     */
    public static function book(array $service, DateTimeImmutable $start, array $client): ?int
    {
        $pdo = db();
        $lock = $pdo->query("SELECT GET_LOCK('salao_marcacoes', 5)")->fetchColumn();

        if ((int) $lock !== 1) {
            throw new RuntimeException('Não foi possível obter o lock das marcações.');
        }

        try {
            $day = $start->setTime(0, 0);
            $free = self::freeSlots($day, (int) $service['duration_min']);

            if (!in_array($start->format('H:i'), $free, true)) {
                return null;
            }

            $end = $start->modify('+' . $service['duration_min'] . ' minutes');

            $stmt = $pdo->prepare(
                'INSERT INTO bookings (service_id, client_name, client_email, client_phone, notes, start_at, end_at)
                 VALUES (:service, :name, :email, :phone, :notes, :start, :end)'
            );
            $stmt->execute([
                'service' => $service['id'],
                'name'    => $client['name'],
                'email'   => $client['email'],
                'phone'   => $client['phone'],
                'notes'   => $client['notes'] ?: null,
                'start'   => $start->format('Y-m-d H:i:s'),
                'end'     => $end->format('Y-m-d H:i:s'),
            ]);

            return (int) $pdo->lastInsertId();
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('salao_marcacoes')");
        }
    }

    public static function bookingsForDay(DateTimeImmutable $day): array
    {
        $stmt = db()->prepare(
            "SELECT b.id, b.client_name, b.client_email, b.client_phone, b.notes, b.status,
                    DATE_FORMAT(b.start_at, '%H:%i') AS start_time,
                    DATE_FORMAT(b.end_at, '%H:%i') AS end_time,
                    s.name AS service_name, s.price_cents
               FROM bookings b
               JOIN services s ON s.id = b.service_id
              WHERE b.start_at >= ? AND b.start_at < ?
              ORDER BY b.start_at"
        );
        $stmt->execute([$day->format('Y-m-d 00:00:00'), $day->modify('+1 day')->format('Y-m-d 00:00:00')]);
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status): bool
    {
        $stmt = db()->prepare('UPDATE bookings SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        return $stmt->rowCount() > 0;
    }

    /** Marcações (não canceladas) desse dia, como pares [início, fim] */
    private static function busyPeriods(DateTimeImmutable $day): array
    {
        $stmt = db()->prepare(
            "SELECT start_at, end_at FROM bookings
              WHERE status <> 'cancelada' AND start_at >= ? AND start_at < ?"
        );
        $stmt->execute([$day->format('Y-m-d 00:00:00'), $day->modify('+1 day')->format('Y-m-d 00:00:00')]);

        return array_map(
            fn ($row) => [new DateTimeImmutable($row['start_at']), new DateTimeImmutable($row['end_at'])],
            $stmt->fetchAll()
        );
    }

    private static function overlaps(DateTimeImmutable $start, DateTimeImmutable $end, array $busy): bool
    {
        foreach ($busy as [$busyStart, $busyEnd]) {
            if ($start < $busyEnd && $end > $busyStart) {
                return true;
            }
        }
        return false;
    }
}
