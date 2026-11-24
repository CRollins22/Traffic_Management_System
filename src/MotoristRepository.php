<?php
// OWNER: Claude Rollins | saves and reads motorist accounts and incident reports
class MotoristRepository
{
    /** Creates a motorist account. Returns null if it worked, or a message saying what is already taken. */
    public static function register(string $username, string $hash, string $name, string $phone, string $plate): ?string
    {
        $pdo = db();
        foreach ([
            ['SELECT 1 FROM users WHERE username = ?',    $username, 'That username is already taken.'],
            ['SELECT 1 FROM motorists WHERE phone = ?',   $phone,    'That phone number is already registered.'],
            ['SELECT 1 FROM motorists WHERE plate = ?',   $plate,    'That number plate is already registered.'],
        ] as [$sql, $value, $message]) {
            $st = $pdo->prepare($sql);
            $st->execute([$value]);
            if ($st->fetchColumn()) return $message;
        }
        $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'road_user')")->execute([$username, $hash]);
        $id = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO motorists (user_id, full_name, phone, plate) VALUES (?, ?, ?, ?)')->execute([$id, $name, $phone, $plate]);
        return null;
    }

    public static function profile(int $userId): ?array
    {
        $st = db()->prepare('SELECT full_name, phone, plate FROM motorists WHERE user_id = ?');
        $st->execute([$userId]);
        return $st->fetch() ?: null;
    }

    public static function reportIncident(int $userId, string $type, string $location, string $details): void
    {
        $st = db()->prepare('INSERT INTO incident_reports (user_id, type, location, details) VALUES (?, ?, ?, ?)');
        $st->execute([$userId, $type, $location, $details === '' ? null : $details]);
    }

    /** A motorist's own reports, newest first. */
    public static function myIncidents(int $userId, int $limit = 5): array
    {
        $st = db()->prepare('SELECT type, location, status, created_at FROM incident_reports WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int) $limit);
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    /** Open incidents anyone on the road should know about (no personal details). */
    public static function openIncidents(int $limit = 5): array
    {
        return db()->query("SELECT type, location, created_at FROM incident_reports WHERE status = 'OPEN' ORDER BY id DESC LIMIT " . (int) $limit)->fetchAll();
    }

    /** For operators: newest reports with the reporter's plate. */
    public static function recentIncidents(int $limit = 8): array
    {
        return db()->query('SELECT i.*, m.plate FROM incident_reports i JOIN motorists m ON m.user_id = i.user_id ORDER BY i.id DESC LIMIT ' . (int) $limit)->fetchAll();
    }

    public static function resolveIncident(int $id): void
    {
        db()->prepare("UPDATE incident_reports SET status = 'RESOLVED' WHERE id = ?")->execute([$id]);
    }
}