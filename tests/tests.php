<?php
// OWNER: Anthony Kariuki (runner) | each member adds their own tests below
// Run: php tests/tests.php
require_once __DIR__ . '/../src/SignalController.php';
require_once __DIR__ . '/../src/AlertService.php';
require_once __DIR__ . '/../src/EmergencyService.php';
require_once __DIR__ . '/../src/ViolationService.php';
require_once __DIR__ . '/../src/Security.php';

$passed = 0; $failed = 0;

function check(string $name, string $how, $expected, $actual): void
{
    global $passed, $failed;
    $e = var_export($expected, true); $a = var_export($actual, true);
    $ok = ($e === $a);
    $ok ? $passed++ : $failed++;
    echo ($ok ? "PASS" : "FAIL") . " | $name\n     How: $how\n     Expected: $e | Actual: $a\n";
}

function quietAlerts(): AlertService { $a = new AlertService(); $a->verbose = false; return $a; }

// ---- Daniel: Signal control (UNIT) ----
$s = new SignalController();
$s->updateTraffic(85, 4);
check("T1 Adaptive green increase (BR-01)", "queue 85m for 4 min, default green 30s", 45, $s->getGreenSeconds());

$s = new SignalController();
$s->updateTraffic(70, 5);
check("T2 No increase when queue under 80m", "queue 70m for 5 min", 30, $s->getGreenSeconds());

$s = new SignalController();
for ($i = 0; $i < 20; $i++) $s->updateTraffic(100, 10);
check("T3 Green capped at 120s (BR-01)", "20 consecutive congested updates", 120, $s->getGreenSeconds());

// ---- Muti: Emergency override (INTEGRATION: EmergencyService + SignalController + AlertService) ----
$sig = new SignalController();
$em = new EmergencyService($sig, quietAlerts());
$eta = $em->requestPriority("AMB-01", 500, 60);
check("T4 Emergency override gives green (BR-02)", "ambulance 500m away at 60 km/h", "GREEN", $sig->getSignal());
check("T5 Override is audit-logged (BR-02)", "check audit log has 1 entry", 1, count($em->getAuditLog()));
check("T6 ETA calculated", "500m at 60km/h = 30s", 30, $eta);
$em->vehicleCleared("AMB-01");
check("T7 Signal restored after clearance", "vehicle cleared", "RED", $sig->getSignal());

// ---- Tama: Violations and payment (INTEGRATION with AlertService) ----

// T8: Record a violation and check it is UNPAID
$alertsForTama = quietAlerts();
$vs = new ViolationService($alertsForTama);
$v = $vs->record("KDA 123A", "speeding", 3000, "0712345678");
check("T8 New violation is UNPAID (UC-02)", "record violation", "UNPAID", $v->status);

// T9: M-PESA payment marks it paid
$paidSuccess = $vs->pay($v->id, "0712345678");
check("T9 Fine marked PAID after payment (UC-04)", "pay violation #1", true, $paidSuccess);
check("T9b Status is updated to PAID", "check violation status", "PAID", $v->status);

// T10: double payment is blocked
$doublePay = $vs->pay($v->id, "0712345678");
check("T10 Cannot pay a fine twice", "pay violation #1 again", false, $doublePay);

// ---- Anthony: Security (UNIT) ----
check("T11 Login succeeds with correct MFA (BR-03)", "operator/traffic123/123456", true, Security::login("operator", "traffic123", "123456"));
check("T12 Login fails with wrong MFA (BR-03)", "operator/traffic123/000000", false, Security::login("operator", "traffic123", "000000"));

// ---- Del: Alerts (UNIT) ----
$a2 = quietAlerts();
$a2->sendSms("0700000000", "Test");
check("T13 SMS alert recorded", "send 1 SMS", 1, count($a2->getAlerts()));

echo "\nRESULT: $passed passed, $failed failed\n";
exit($failed > 0 ? 1 : 0);
