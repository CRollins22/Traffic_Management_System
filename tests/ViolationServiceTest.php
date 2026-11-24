<?php
// OWNER: Tama | tests for ViolationService
// Run: php tests/ViolationServiceTest.php
require_once __DIR__ . '/../src/ViolationService.php';

function testT8(): bool // record a violation and send an SMS
{
    $s = new ViolationService();
    $v = $s->recordViolation('kda 123a', 'speeding', 'Thika Road', '0712345678');
    return $v['plate'] === 'KDA 123A'
        && $v['status'] === 'unpaid'
        && $v['fine'] === 3000
        && count($s->getSmsLog()) === 1;
}

echo "T8 Record a violation and send an SMS: " . (testT8() ? "PASS" : "FAIL") . "\n";