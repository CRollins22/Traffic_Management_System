<?php
// OWNER: Claude Rollins | front controller: routing, page layout, ties all panels together
require __DIR__ . '/../src/bootstrap.php';
foreach (['login', 'motorist', 'incidents', 'signal', 'emergency', 'dashboard'] as $p) require __DIR__ . "/../panels/$p.php";

try { db(); }                                   // is the database reachable?
catch (PDOException $e) {
    http_response_code(503);
    exit('<h1>Cannot connect to the database</h1><p>Start <b>MySQL</b> in the XAMPP Control Panel, then import <code>database/schema.sql</code> in phpMyAdmin.</p>');
}

junction_load();                                // the junction is shared: operators and motorists see the same one
$S = $JUNCTION['tms'];                          // objects are shared by reference, so changes are saved at the end of the request
$role = $_SESSION['role'] ?? null;              // 'operator', 'road_user' or null (not signed in)

$light = light_state($S['signals'], $S['emergency']);   // moves the live light on before anything else

if (isset($_GET['view'])) $_SESSION['view'] = $_GET['view'] === 'operator' ? 'operator' : 'motorist';   // which login page to show
if (isset($_GET['tab']))  $_SESSION['tab']  = $_GET['tab'] === 'signup' ? 'signup' : 'signin';          // sign in or sign up

if (isset($_GET['state'])) {                    // the page asks for this every 2 s to stay in step
    header('Content-Type: application/json');
    [$condition, $conditionClass] = Motorist::condition($S['signals']->getQueueMeters());
    echo json_encode($role === null ? null : $light + [
        'queue' => $S['signals']->getQueueMeters(), 'condition' => $condition, 'conditionClass' => $conditionClass,
        'alerts' => Motorist::roadAlerts($S['alerts']),
        'incidents' => $role === 'operator' ? panel_incidents_html() : null,   // operators see new reports without refreshing
        'openIncidents' => $role === 'road_user' ? panel_motorist_open_html() : null,                       // motorists see new incidents live
        'myReports' => $role === 'road_user' ? panel_motorist_reports_html((int) $_SESSION['user_id']) : null,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($role === 'operator') {                      // signed in as an operator
            match (true) {
                $action === 'logout'                   => session_destroy(),
                $action === 'reset'                    => (function () { reset_state(); flash("Demo data reset."); })(),
                $action === 'traffic'                  => panel_signal_handle($S['signals']),
                str_starts_with($action, 'emergency_') => panel_emergency_handle($S['emergency'], $action),
                str_starts_with($action, 'incident_')  => panel_incident_handle($action),
                default                                => flash("Unknown action.", "err"),
            };
        } elseif ($role === 'road_user') {               // signed in as a motorist
            match ($action) {
                'logout'          => session_destroy(),
                'report_incident' => panel_motorist_report_handle(),
                default           => flash("Unknown action.", "err"),
            };
        } else {                                         // not signed in: only the login pages work
            match ($action) {
                'login'             => panel_login_handle(),              // operator login
                'motorist_login'    => panel_motorist_login_handle(),
                'motorist_register' => panel_motorist_register_handle(),
                default             => null,
            };
        }
    } catch (PDOException $e) {
        flash("Database error. Is MySQL running in XAMPP?", "err");
    }
    header('Location: index.php');   // redirect so refreshing does not resubmit the form
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Traffic Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="top">
    <h1>Traffic Management System</h1>
    <?php if ($role !== null): ?>
    <div class="actions">
        <span class="muted"><?= $role === 'operator' ? 'Operator' : 'Motorist' ?>: <?= h($_SESSION['user']) ?></span>
        <?php if ($role === 'operator'): ?>
        <form method="post"><input type="hidden" name="action" value="reset"><button class="secondary small">Reset demo</button></form>
        <?php endif; ?>
        <form method="post"><input type="hidden" name="action" value="logout"><button class="secondary small">Log out</button></form>
    </div>
    <?php endif; ?>
</header>
<main>
    <?php if ($flash): ?><div class="flash <?= h($flash[1]) ?>" role="status"><?= h($flash[0]) ?></div><?php endif; ?>

    <?php if ($role === 'operator'): ?>
        <?php panel_dashboard_render($S['signals'], $S['emergency'], $S['alerts']); ?>
        <div class="cols">
            <?php panel_signal_render($S['signals']); ?>
            <?php panel_emergency_render($S['emergency']); ?>
        </div>
        <?php panel_incidents_render(); ?>
    <?php elseif ($role === 'road_user'): ?>
        <?php panel_motorist_render($S['signals'], $S['alerts']); ?>
    <?php elseif (($_SESSION['view'] ?? 'motorist') === 'operator'): ?>
        <?php panel_login_render(); ?>
        <p class="hint center"><a href="index.php?view=motorist">&larr; Back to motorist sign in</a></p>
    <?php else: ?>
        <?php panel_motorist_auth_render(); ?>
    <?php endif; ?>
</main>
<?php if ($role !== null): ?><script src="light.js"></script><?php endif; ?>
</body>
</html>