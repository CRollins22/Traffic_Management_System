<?php
// OWNER: Claude Rollins | motorist rules that need no database (easy to unit test)
class Motorist
{
    const INCIDENT_TYPES = [
        'CRASH'      => 'Crash',
        'BREAKDOWN'  => 'Broken-down vehicle',
        'HAZARD'     => 'Road hazard',
        'CONGESTION' => 'Heavy congestion',
    ];

    /** "0712 345 678" or "+254712345678" -> "0712345678". Returns null if it is not a Kenyan mobile number. */
    public static function cleanPhone(string $raw): ?string
    {
        $p = preg_replace('/[\s-]/', '', $raw);
        if (preg_match('/^(?:\+?254)([17]\d{8})$/', $p, $m)) return '0' . $m[1];
        return preg_match('/^0[17]\d{8}$/', $p) ? $p : null;
    }

    /** "kda123a" -> "KDA 123A". Returns null if it does not look like a Kenyan number plate. */
    public static function cleanPlate(string $raw): ?string
    {
        $p = strtoupper(preg_replace('/\s+/', '', $raw));
        return preg_match('/^([A-Z]{2,3})(\d{3})([A-Z]?)$/', $p, $m) ? "$m[1] $m[2]$m[3]" : null;
    }

    /** Turns the last reported queue length into [label, css class]. 80 m is the BR-01 limit. */
    public static function condition(int $queueMeters): array
    {
        if ($queueMeters > 80)  return ['Heavy', 'heavy'];
        if ($queueMeters >= 40) return ['Moderate', 'moderate'];
        return ['Free flowing', 'free'];
    }

    /** The newest road-display messages, without the "ROAD DISPLAY:" prefix. Motorists never see SMS lines. */
    public static function roadAlerts(AlertService $alerts, int $limit = 5): array
    {
        $road = array_filter($alerts->getAlerts(), fn($a) => str_contains($a, 'ROAD DISPLAY: '));
        return array_slice(array_map(fn($a) => str_replace('ROAD DISPLAY: ', '', $a), array_values($road)), 0, $limit);
    }
}