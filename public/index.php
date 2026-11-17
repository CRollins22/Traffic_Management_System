<?php
// OWNER: Claude Rollins | front controller: routing, page layout, ties all panels together
require __DIR__ . '/../src/bootstrap.php';
foreach (['login', 'signal', 'emergency', 'violations', 'dashboard'] as $p) require __DIR__ . "/../panels/$p.php";

$S = $_SESSION['tms'];   // objects are shared by reference, so changes persist in the session

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'login') {
        panel_login_handle();
    } elseif (isset($_SESSION['user'])) {            // everything below needs a logged-in operator
        match (true) {
            $action === 'logout'                   => session_destroy(),
            $action === 'reset'                    => (function () { reset_state(); flash("Demo data reset."); })(),
            $action === 'traffic'                  => panel_signal_handle($S['signals']),
            str_starts_with($action, 'emergency_') => panel_emergency_handle($S['emergency'], $action),
            str_starts_with($action, 'violation_') => panel_violation_handle($S['violations'], $action),
            default                                => flash("Unknown action.", "err"),
        };
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
    <?php if (isset($_SESSION['user'])): ?>
    <div class="actions">
        <span class="muted">Operator: <?= h($_SESSION['user']) ?></span>
        <form method="post"><input type="hidden" name="action" value="reset"><button class="secondary small">Reset demo</button></form>
        <form method="post"><input type="hidden" name="action" value="logout"><button class="secondary small">Log out</button></form>
    </div>
    <?php endif; ?>
</header>
<main>
    <?php if ($flash): ?><div class="flash <?= h($flash[1]) ?>" role="status"><?= h($flash[0]) ?></div><?php endif; ?>

    <?php if (!isset($_SESSION['user'])): ?>
        <?php panel_login_render(); ?>
    <?php else: ?>
        <?php panel_dashboard_render($S['signals'], $S['emergency'], $S['alerts'], $S['violations']); ?>
        <div class="cols">
            <?php panel_signal_render($S['signals']); ?>
            <?php panel_emergency_render($S['emergency']); ?>
        </div>
        <?php panel_violation_render($S['violations']); ?>
    <?php endif; ?>
</main>
</body>
</html>
