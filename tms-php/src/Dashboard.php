<?php
// OWNER: Del Sambu | Real-time dashboard (console)
class Dashboard
{
    public static function show(SignalController $s, ViolationService $v, EmergencyService $e, AlertService $a): void
    {
        echo "\n========== TRAFFIC DASHBOARD ==========\n";
        echo "Signal: {$s->getSignal()} | Green time: {$s->getGreenSeconds()}s | Queue: {$s->getQueueMeters()}m\n";
        echo "--- Violations ---\n";
        if (!$v->getViolations()) echo "(none)\n";
        foreach ($v->getViolations() as $x) echo $x . "\n";
        echo "--- Emergency audit log ---\n";
        if (!$e->getAuditLog()) echo "(none)\n";
        foreach ($e->getAuditLog() as $l) echo $l . "\n";
        echo "--- Alerts sent: " . count($a->getAlerts()) . " ---\n";
        echo "=======================================\n\n";
    }
}
