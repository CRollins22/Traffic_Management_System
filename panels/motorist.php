<?php
// OWNER: Claude Rollins | motorist side: sign in / sign up page, incident reports, motorist dashboard

/** Signs a motorist in (road_user accounts only). */
function panel_motorist_login_handle(): void
{
    $user = UserRepository::find(trim($_POST['user'] ?? ''));
    $ok = $user && $user['role'] === 'road_user'
          && Security::verifyPassword($_POST['password'] ?? '', $user['password_hash']);
    if (!$ok) { flash("Invalid username or password.", "err"); return; }

    session_regenerate_id(true);          // new session id after sign-in blocks session fixation
    $_SESSION['user'] = $user['username'];
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role'] = 'road_user';
    flash("Welcome back, {$user['username']}.");
}

/** Creates a motorist account after checking every field. */
function panel_motorist_register_handle(): void
{
    $name  = trim($_POST['name'] ?? '');
    $user  = trim($_POST['user'] ?? '');
    $phone = Motorist::cleanPhone($_POST['phone'] ?? '');
    $plate = Motorist::cleanPlate($_POST['plate'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $_SESSION['tab'] = 'signup';          // stay on the sign-up form if something is wrong

    if (strlen($name) < 2 || strlen($name) > 60)  { flash("Enter your full name.", "err"); return; }
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $user))   { flash("Username: 3 to 30 letters, numbers or underscores.", "err"); return; }
    if ($phone === null)                                { flash("Enter a valid Kenyan mobile number, e.g. 0712 345 678.", "err"); return; }
    if ($plate === null)                                { flash("Enter a valid number plate, e.g. KDA 123A.", "err"); return; }
    if (strlen($pass) < 8)                              { flash("Password must be at least 8 characters.", "err"); return; }
    if ($pass !== ($_POST['confirm'] ?? ''))            { flash("Passwords do not match.", "err"); return; }

    $problem = MotoristRepository::register($user, Security::hashPassword($pass), $name, $phone, $plate);
    if ($problem !== null) { flash($problem, "err"); return; }
    $_SESSION['tab'] = 'signin';
    flash("Account created. Please sign in.");
}

/** A motorist reports an incident for the operators to see. */
function panel_motorist_report_handle(): void
{
    $type = $_POST['type'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $details = trim($_POST['details'] ?? '');
    if (!isset(Motorist::INCIDENT_TYPES[$type]))                      { flash("Choose what happened.", "err"); return; }
    if (strlen($location) < 3 || strlen($location) > 100)       { flash("Say where it happened (3 to 100 characters).", "err"); return; }
    if (strlen($details) > 255)                                    { flash("Details are too long (255 characters at most).", "err"); return; }
    MotoristRepository::reportIncident((int) $_SESSION['user_id'], $type, $location, $details);
    flash("Thank you. Your report was sent to the traffic operators.");
}

/** The first page: motorist sign in / sign up, with a button to the operator login. */
function panel_motorist_auth_render(): void
{
    $signup = ($_SESSION['tab'] ?? 'signin') === 'signup';
    ?>
    <section class="card login">
        <div class="tabs">
            <a href="index.php?view=motorist&amp;tab=signin" class="<?= $signup ? '' : 'on' ?>">Sign in</a>
            <a href="index.php?view=motorist&amp;tab=signup" class="<?= $signup ? 'on' : '' ?>">Sign up</a>
        </div>
    <?php if ($signup): ?>
        <h2>Create a motorist account</h2>
        <form method="post">
            <input type="hidden" name="action" value="motorist_register">
            <label>Full name <input name="name" required autofocus></label>
            <label>Username <input name="user" required></label>
            <label>Mobile number <input name="phone" inputmode="tel" placeholder="0712 345 678" required></label>
            <label>Number plate <input name="plate" placeholder="KDA 123A" required></label>
            <label>Password (8+ characters) <input name="password" type="password" minlength="8" required></label>
            <label>Confirm password <input name="confirm" type="password" minlength="8" required></label>
            <button>Create account</button>
        </form>
    <?php else: ?>
        <h2>Motorist sign in</h2>
        <form method="post">
            <input type="hidden" name="action" value="motorist_login">
            <label>Username <input name="user" required autofocus></label>
            <label>Password <input name="password" type="password" required></label>
            <button>Sign in</button>
        </form>
    <?php endif; ?>
        <p class="switch"><span class="muted">Traffic operator?</span> <a class="btnlink" href="index.php?view=operator">Operator login</a></p>
    </section>
<?php }

/** The open-incidents list items as HTML (light.js refreshes this live). */
function panel_motorist_open_html(): string
{
    $open = MotoristRepository::openIncidents();
    if (!$open) return '<li class="muted">No open incidents.</li>';
    $out = '';
    foreach ($open as $i) {
        $out .= '<li>' . h(substr($i['created_at'], 11, 5)) . '  ' . h(Motorist::INCIDENT_TYPES[$i['type']]) . ' at ' . h($i['location']) . '</li>';
    }
    return $out;
}

/** The motorist's own reports table as HTML (light.js refreshes this live). */
function panel_motorist_reports_html(int $userId): string
{
    $mine = MotoristRepository::myIncidents($userId);
    if (!$mine) return '<p class="muted">You have not reported anything yet.</p>';
    $out = '<div class="scroll"><table><tr><th>Time</th><th>What</th><th>Where</th><th>Status</th></tr>';
    foreach ($mine as $r) {
        $pill = $r['status'] === 'OPEN' ? 'open' : 'resolved';
        $out .= '<tr><td>' . h(substr($r['created_at'], 5, 11)) . '</td><td>' . h(Motorist::INCIDENT_TYPES[$r['type']]) . '</td><td>' . h($r['location'])
              . '</td><td><span class="pill ' . $pill . '">' . h($r['status']) . '</span></td></tr>';
    }
    return $out . '</table></div>';
}

/** The motorist dashboard: what the road looks like now, alerts, and a way to report problems. */
function panel_motorist_render(SignalController $signals, AlertService $alerts): void
{
    $me = MotoristRepository::profile((int) $_SESSION['user_id']);
    $advice = ['RED' => 'Stop.', 'YELLOW' => 'Get ready to stop.', 'GREEN' => 'Go.'];
    $sig = $signals->getSignal();
    [$condLabel, $condClass] = Motorist::condition($signals->getQueueMeters());
    ?>
    <div class="narrow">
        <section class="card hello">
            <h2>Hello, <?= h($me['full_name'] ?? $_SESSION['user']) ?></h2>
            <p class="muted">Your vehicle <span class="plate"><?= h($me['plate'] ?? '') ?></span> &middot; Phone <?= h($me['phone'] ?? '') ?></p>
        </section>

        <div class="banner" id="emergency-banner" hidden>Emergency vehicle approaching. Give way and keep the junction clear.</div>

        <section class="card">
            <h2>Junction signal</h2>
            <div class="dashgrid">
                <div class="lightbox" id="light" aria-label="Traffic signal">
                    <span class="lamp <?= $sig === 'RED' ? 'red' : '' ?>" id="lamp-red"></span>
                    <span class="lamp <?= $sig === 'YELLOW' ? 'amber' : '' ?>" id="lamp-amber"></span>
                    <span class="lamp <?= $sig === 'GREEN' ? 'green' : '' ?>" id="lamp-green"></span>
                </div>
                <div class="countdown"><span id="count">--</span><span class="muted" id="count-label">Time left</span></div>
                <p class="advice" id="advice"><?= h($advice[$sig] ?? '') ?></p>
            </div>
        </section>

        <section class="card">
            <h2>Traffic ahead</h2>
            <p><span class="cond <?= h($condClass) ?>" id="condition"><?= h($condLabel) ?></span></p>
            <p class="muted">Last reported queue: <strong id="stat-queue"><?= h($signals->getQueueMeters()) ?> m</strong> &middot; Green light lasts <strong id="stat-green"><?= h($signals->getGreenSeconds()) ?>s</strong></p>
        </section>

        <section class="card">
            <h2>Road alerts</h2>
            <ul class="log" id="alerts-list">
                <?php foreach (Motorist::roadAlerts($alerts) as $a): ?><li><?= h($a) ?></li><?php endforeach; ?>
                <?php if (!Motorist::roadAlerts($alerts)): ?><li class="muted">No alerts right now.</li><?php endif; ?>
            </ul>
            <h3>Incidents reported on the road</h3>
            <ul class="log" id="open-incidents"><?= panel_motorist_open_html() ?></ul>
        </section>

        <section class="card">
            <h2>Report an incident</h2>
            <p class="muted">See a crash, a broken-down vehicle or a hazard? Tell the traffic operators.</p>
            <form method="post" class="stack">
                <input type="hidden" name="action" value="report_incident">
                <label>What happened
                    <select name="type" required>
                        <?php foreach (Motorist::INCIDENT_TYPES as $key => $label): ?><option value="<?= h($key) ?>"><?= h($label) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Where <input name="location" placeholder="e.g. Mombasa Road near the airport" required></label>
                <label>More details (optional) <textarea name="details" rows="2" maxlength="255"></textarea></label>
                <button>Send report</button>
            </form>
        </section>

        <section class="card">
            <h2>My reports</h2>
            <div id="my-reports"><?= panel_motorist_reports_html((int) $_SESSION['user_id']) ?></div>
        </section>
    </div>
<?php }