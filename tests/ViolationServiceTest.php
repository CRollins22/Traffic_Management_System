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
function testT9(): bool // M-PESA payment marks it paid
{
    $s = new ViolationService();
    $v = $s->recordViolation('KDA 123A', 'red_light', 'Uhuru Highway', '0712345678');
    $paid = $s->payViolation($v['id']);
    return $paid['status'] === 'paid'
        && $paid['receipt'] !== null
        && count($s->getSmsLog()) === 2;
}

echo "T9 Payment marks the violation as paid: " . (testT9() ? "PASS" : "FAIL") . "\n";
function testT10(): bool // double payment is blocked
{
    $s = new ViolationService();
    $v = $s->recordViolation('KDA 123A', 'illegal_parking', 'CBD', '0712345678');
    $s->payViolation($v['id']);
    try {
        $s->payViolation($v['id']);
    } catch (RuntimeException $e) {
        return true;
    }
    return false;
}

echo "T10 Paying twice is blocked: " . (testT10() ? "PASS" : "FAIL") . "\n";
function testT11(): bool // empty plate is rejected
{
    $s = new ViolationService();
    try {
        $s->recordViolation('   ', 'speeding', 'Thika Road', '0712345678');
    } catch (InvalidArgumentException $e) {
        return $e->getMessage() === 'Number plate is required';
    }
    return false;
}

echo "T11 Empty plate is rejected: " . (testT11() ? "PASS" : "FAIL") . "\n";
function testT12(): bool // invalid violation type is rejected
{
    $s = new ViolationService();
    try {
        $s->recordViolation('KDA 123A', 'jaywalking', 'Thika Road', '0712345678');
    } catch (InvalidArgumentException $e) {
        return $e->getMessage() === 'Invalid violation type';
    }
    return false;
}

echo "T12 Invalid violation type is rejected: " . (testT12() ? "PASS" : "FAIL") . "\n";
function testT13(): bool // empty location is rejected
{
    $s = new ViolationService();
    try {
        $s->recordViolation('KDA 123A', 'speeding', '   ', '0712345678');
    } catch (InvalidArgumentException $e) {
        return $e->getMessage() === 'Location is required';
    }
    return false;
}

echo "T13 Empty location is rejected: " . (testT13() ? "PASS" : "FAIL") . "\n";
function testT14(): bool // bad phone number is rejected
{
    $s = new ViolationService();
    try {
        $s->recordViolation('KDA 123A', 'speeding', 'Thika Road', '12345');
    } catch (InvalidArgumentException $e) {
        return $e->getMessage() === 'Invalid phone number';
    }
    return false;
}

echo "T14 Bad phone number is rejected: " . (testT14() ? "PASS" : "FAIL") . "\n";
function testT15(): bool // each violation type gets the right fine
{
    $s = new ViolationService();
    $a = $s->recordViolation('KDA 123A', 'speeding', 'Thika Road', '0712345678');
    $b = $s->recordViolation('KDB 456B', 'red_light', 'Uhuru Highway', '0712345678');
    $c = $s->recordViolation('KDC 789C', 'illegal_parking', 'CBD', '0712345678');
    return $a['fine'] === 3000
        && $b['fine'] === 5000
        && $c['fine'] === 2000;
}

echo "T15 Each violation type gets the right fine: " . (testT15() ? "PASS" : "FAIL") . "\n";
function testT16(): bool // badly formatted plate is rejected
{
    $s = new ViolationService();
    try {
        $s->recordViolation('K1', 'speeding', 'Thika Road', '0712345678');
    } catch (InvalidArgumentException $e) {
        return $e->getMessage() === 'Invalid number plate';
    }
    return false;
}

echo "T16 Badly formatted plate is rejected: " . (testT16() ? "PASS" : "FAIL") . "\n";