<?php
// OWNER: Claude Rollins | saves and reads the traffic and emergency history
class History
{
    public static function logTraffic(int $userId, int $queue, int $minutes, int $before, int $after): void
    {
        $st = db()->prepare('INSERT INTO traffic_log (user_id, queue_m, minutes, green_before, green_after) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$userId, $queue, $minutes, $before, $after]);
    }

    public static function logEmergency(int $userId, string $vehicle, string $event, ?int $distance = null, ?int $speed = null, ?int $eta = null): void
    {
        $st = db()->prepare('INSERT INTO emergency_log (user_id, vehicle_id, event, distance_m, speed_kmh, eta_seconds) VALUES (?, ?, ?, ?, ?, ?)');
        $st->execute([$userId, $vehicle, $event, $distance, $speed, $eta]);
    }

    public static function recentTraffic(int $limit = 5): array
    {
        return db()->query('SELECT t.*, u.username FROM traffic_log t JOIN users u ON u.id = t.user_id ORDER BY t.id DESC LIMIT ' . (int) $limit)->fetchAll();
    }

    public static function recentEmergency(int $limit = 5): array
    {
        return db()->query('SELECT e.*, u.username FROM emergency_log e JOIN users u ON u.id = e.user_id ORDER BY e.id DESC LIMIT ' . (int) $limit)->fetchAll();
    }
}