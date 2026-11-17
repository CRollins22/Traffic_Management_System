<?php
// OWNER: Claude Rollins | loads classes, starts the session, holds the shared state
require_once __DIR__ . '/SignalController.php';
require_once __DIR__ . '/AlertService.php';
require_once __DIR__ . '/EmergencyService.php';
require_once __DIR__ . '/ViolationService.php';
require_once __DIR__ . '/Security.php';

date_default_timezone_set('Africa/Nairobi');
session_start();   # must come AFTER the classes are loaded

function reset_state(): void
{
    $signals = new SignalController();
    $alerts = new AlertService();
    $_SESSION['tms'] = [
        'signals'    => $signals,
        'alerts'     => $alerts,
        'emergency'  => new EmergencyService($signals, $alerts),
        'violations' => new ViolationService($alerts),
    ];
}
if (!isset($_SESSION['tms'])) reset_state();

function h($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

function flash(string $msg, string $type = "ok"): void { $_SESSION['flash'] = [$msg, $type]; }

/** Read a whole-number form field; null if missing or not a number. */
function int_field(string $name): ?int
{
    $v = filter_var($_POST[$name] ?? null, FILTER_VALIDATE_INT);
    return $v === false ? null : $v;
}
