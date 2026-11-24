<?php
// OWNER: Anthony Kariuki (runner) | each member adds their own tests below
// Run: php tests/tests.php
require_once __DIR__ . '/../src/SignalController.php';
require_once __DIR__ . '/../src/AlertService.php';
require_once __DIR__ . '/../src/EmergencyService.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/Motorist.php';

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

// ---- Anthony: Security (UNIT, no database needed) ----
$hash = Security::hashPassword("traffic123");
check("T11 Correct password verifies (BR-03)", "password traffic123 against its hash", true, Security::verifyPassword("traffic123", $hash));
check("T12 Wrong password is rejected (BR-03)", "password wrong999 against the hash", false, Security::verifyPassword("wrong999", $hash));

// ---- Claude: Motorist rules (UNIT, no database needed) ----
check("T14 Phone number is cleaned", "+254 712 345 678", "0712345678", Motorist::cleanPhone("+254 712 345 678"));
check("T15 Bad phone number is rejected", "12345", null, Motorist::cleanPhone("12345"));
check("T16 Number plate is cleaned", "kda123a", "KDA 123A", Motorist::cleanPlate("kda123a"));
check("T17 Bad number plate is rejected", "K1", null, Motorist::cleanPlate("K1"));
check("T18 Queue over 80 m is Heavy traffic", "queue 85 m", "Heavy", Motorist::condition(85)[0]);

// ---- Del: Alerts (UNIT) ----
$a2 = quietAlerts();
$a2->sendSms("0700000000", "Test");
check("T13 SMS alert recorded", "send 1 SMS", 1, count($a2->getAlerts()));

// ---- Anthony: Additional Security edge cases ----
$emptyHash = Security::hashPassword("");
check("T19 Empty password can be hashed and verified", "empty string", true, Security::verifyPassword("", $emptyHash));
// ---- Anthony: Test hash length ----
$shortHash = Security::hashPassword("123");
check("T20 Short password can be hashed", "3-character string", true, strlen($shortHash) > 20);
// ---- Anthony: Test hash uniqueness (salting) ----
$hash1 = Security::hashPassword("password123");
$hash2 = Security::hashPassword("password123");
check("T21 Salting generates unique hashes", "same password hashed twice", false, $hash1 === $hash2);

echo "\nRESULT: $passed passed, $failed failed\n";
exit($failed > 0 ? 1 : 0);