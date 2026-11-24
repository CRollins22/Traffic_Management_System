<?php
// OWNER: Daniel Kirima | UC-01 adaptive signal control page (BR-01)
function panel_signal_handle(SignalController $signals): void
{
    $q = int_field('queue'); $m = int_field('minutes');
    if ($q === null || $m === null || $q < 0 || $m < 0) { flash("Enter valid numbers for queue and minutes.", "err"); return; }
    $before = $signals->getGreenSeconds();
    $signals->updateTraffic($q, $m);
    $after = $signals->getGreenSeconds();
    History::logTraffic((int) $_SESSION['user_id'], $q, $m, $before, $after);   // saved in the database
    flash($after > $before ? "Queue {$q}m for {$m} min: green time increased {$before}s -> {$after}s."
    : ($after < $before ? "Queue {$q}m for {$m} min: traffic eased, green time reset {$before}s -> {$after}s."
    : "Queue {$q}m for {$m} min: no change, green time stays {$after}s."));
}

function panel_signal_render(SignalController $s): void { ?>
    <section class="card">
        <h2>Traffic and adaptive signal</h2>
        <p class="muted">Queue over 80 m for more than 3 min adds 15 s green (max 120 s).</p>
        <form method="post" class="row">
            <input type="hidden" name="action" value="traffic">
            <label>Queue length (m) <input name="queue" type="number" min="0" value="85" required></label>
            <label>Minutes congested <input name="minutes" type="number" min="0" value="4" required></label>
            <button>Update traffic</button>
        </form>
        <p>Current green time: <strong><?= h($s->getGreenSeconds()) ?>s</strong> &middot; Queue: <strong><?= h($s->getQueueMeters()) ?>m</strong></p>
    </section>
<?php }