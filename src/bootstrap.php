<?php
// OWNER: Claude Rollins | loads classes, starts the session, holds the shared junction state
require_once __DIR__ . '/SignalController.php';
require_once __DIR__ . '/AlertService.php';
require_once __DIR__ . '/EmergencyService.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/UserRepository.php';
require_once __DIR__ . '/History.php';
require_once __DIR__ . '/Motorist.php';
require_once __DIR__ . '/MotoristRepository.php';

date_default_timezone_set('Africa/Nairobi');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);   // JavaScript cannot read the session cookie
session_start();   # must come AFTER the classes are loaded

const YELLOW_SECONDS = 3;    // how long the yellow light lasts
const RED_SECONDS = 20;      // how long the red light lasts (green time comes from SignalController)

$JUNCTION = null;            // the shared junction state, loaded from the database on every request

/** Builds a fresh junction: signal on RED, default green time, no emergency. */
function reset_state(): void
{
    global $JUNCTION;
    $signals = new SignalController();
    $alerts = new AlertService();
    $JUNCTION = [
        'tms'   => ['signals' => $signals, 'alerts' => $alerts, 'emergency' => new EmergencyService($signals, $alerts)],
        'light' => ['phase' => 'RED', 'start' => microtime(true), 'pausedAt' => null],   // 'pausedAt' is set during an emergency
    ];
}

/** Loads the junction from the database and locks it, so operators and motorists always see the same one. */
function junction_load(): void
{
    global $JUNCTION;
    $pdo = db();
    $pdo->exec("INSERT IGNORE INTO junction_state (id, data) VALUES (1, '')");   // first run: create the row
    $pdo->beginTransaction();
    $data = $pdo->query('SELECT data FROM junction_state WHERE id = 1 FOR UPDATE')->fetchColumn();   // lock: one request at a time
    $loaded = $data === '' ? false : @unserialize($data, ['allowed_classes' => ['SignalController', 'AlertService', 'EmergencyService']]);
    if (is_array($loaded) && isset($loaded['tms'], $loaded['light'])) $JUNCTION = $loaded;
    else reset_state();
    register_shutdown_function('junction_save');   // runs when the request ends, even after exit
}

/** Writes the junction back to the database and releases the lock. */
function junction_save(): void
{
    global $JUNCTION;
    try {
        $st = db()->prepare('UPDATE junction_state SET data = ? WHERE id = 1');
        $st->execute([serialize($JUNCTION)]);
        db()->commit();
    } catch (Throwable $e) { /* nothing more can be done while shutting down */ }
}

/** How long each light lasts. Green uses the adaptive time, so BR-01 changes it live. */
function light_duration(string $phase, SignalController $s): int
{
    return match ($phase) {
        'GREEN'  => max(1, $s->getGreenSeconds()),
        'YELLOW' => YELLOW_SECONDS,
        default  => RED_SECONDS,
    };
}

/** Works out the current light from the clock: which light is on and how many seconds are left. */
function light_state(SignalController $s, EmergencyService $e): array
{
    global $JUNCTION;
    $L = &$JUNCTION['light'];
    $now = microtime(true);

    if ($e->getActiveVehicle() !== null) {        // BR-02: emergency freezes the cycle on GREEN
        $L['pausedAt'] ??= $now;
        $s->setSignal('GREEN');
        return ['phase' => 'GREEN', 'remaining' => null, 'emergency' => true, 'green' => $s->getGreenSeconds()];
    }
    if ($L['pausedAt'] !== null) {                // emergency over: carry on where the cycle stopped
        $L['start'] += $now - $L['pausedAt'];
        $L['pausedAt'] = null;
    }

    $next = ['GREEN' => 'YELLOW', 'YELLOW' => 'RED', 'RED' => 'GREEN'];
    while ($now - $L['start'] >= light_duration($L['phase'], $s)) {   // time is up: move to the next light
        $L['start'] += light_duration($L['phase'], $s);
        $L['phase'] = $next[$L['phase']];
    }
    $s->setSignal($L['phase']);                   // keep SignalController in step with the light

    $left = light_duration($L['phase'], $s) - ($now - $L['start']);
    return ['phase' => $L['phase'], 'remaining' => round($left, 2), 'emergency' => false, 'green' => $s->getGreenSeconds()];
}

function h($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

function flash(string $msg, string $type = "ok"): void { $_SESSION['flash'] = [$msg, $type]; }

/** Read a whole-number form field; null if missing or not a number. */
function int_field(string $name): ?int
{
    $v = filter_var($_POST[$name] ?? null, FILTER_VALIDATE_INT);
    return $v === false ? null : $v;
}