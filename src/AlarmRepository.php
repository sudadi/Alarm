<?php

declare(strict_types=1);

final class AlarmRepository
{
    public function createDevice(array $data): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare(
                'INSERT INTO btn_devices (btn_code, location, ip, status) VALUES (:btn_code, :location, :ip, :status)'
            );
            $statement->execute([
                'btn_code' => $data['btn_code'],
                'location' => $data['location'],
                'ip' => $data['ip'],
                'status' => $data['status'],
            ]);

            $stateStatement = $pdo->prepare(
                'INSERT INTO btn_state (btn_code, btn_type, state) VALUES (:btn_code, :btn_type, :state)'
            );
            foreach (['Red', 'Blue'] as $btnType) {
                $stateStatement->execute([
                    'btn_code' => $data['btn_code'],
                    'btn_type' => $btnType,
                    'state' => 'OFF',
                ]);
            }

            $pdo->commit();
        } catch (Throwable $throwable) {
            $pdo->rollBack();
            throw $throwable;
        }
    }

    public function devices(): array
    {
        $sql = <<<SQL
            SELECT d.id, d.btn_code, d.location, d.ip, d.last_ping, d.status,
                   COALESCE(MAX(CASE WHEN s.btn_type = 'Red' AND s.state = 'ON' THEN 1 ELSE 0 END), 0) AS red_active,
                   COALESCE(MAX(CASE WHEN s.btn_type = 'Blue' AND s.state = 'ON' THEN 1 ELSE 0 END), 0) AS blue_active
            FROM btn_devices d
            LEFT JOIN btn_state s ON s.btn_code = d.btn_code
            GROUP BY d.id, d.btn_code, d.location, d.ip, d.last_ping, d.status
            ORDER BY d.location ASC
        SQL;

        return Database::pdo()->query($sql)->fetchAll();
    }

    public function deviceByCode(string $btnCode): ?array
    {
        $statement = Database::pdo()->prepare('SELECT id, btn_code, location, ip, last_ping, status FROM btn_devices WHERE btn_code = :btn_code LIMIT 1');
        $statement->execute(['btn_code' => $btnCode]);
        $device = $statement->fetch();

        return $device ?: null;
    }

    public function activeAlarms(): array
    {
        $sql = <<<SQL
            SELECT d.btn_code, d.location, d.status, s.btn_type, s.state, d.last_ping
            FROM btn_devices d
            INNER JOIN btn_state s ON s.btn_code = d.btn_code
            WHERE s.state = 'ON'
            ORDER BY d.location ASC, s.btn_type ASC
        SQL;

        return Database::pdo()->query($sql)->fetchAll();
    }

    public function logs(int $limit = 100): array
    {
        $statement = Database::pdo()->prepare(
            'SELECT l.id, l.btn_code, l.btn_type, l.create_at, d.location
             FROM btn_log l
             LEFT JOIN btn_devices d ON d.btn_code = l.btn_code
             ORDER BY l.create_at DESC, l.id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function counts(): array
    {
        $pdo = Database::pdo();
        return [
            'devices' => (int) $pdo->query('SELECT COUNT(*) FROM btn_devices')->fetchColumn(),
            'connected' => (int) $pdo->query("SELECT COUNT(*) FROM btn_devices WHERE status = 'Connected'")->fetchColumn(),
            'active_red' => (int) $pdo->query("SELECT COUNT(*) FROM btn_state WHERE btn_type = 'Red' AND state = 'ON'")->fetchColumn(),
            'active_blue' => (int) $pdo->query("SELECT COUNT(*) FROM btn_state WHERE btn_type = 'Blue' AND state = 'ON'")->fetchColumn(),
        ];
    }
}
