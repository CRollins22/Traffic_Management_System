<?php
// OWNER: Del Sambu | live dashboard + alerts feed
function panel_dashboard_render(SignalController $s, EmergencyService $e, AlertService $a): void
{
    ?>
    <section class="card dash">
        <h2>Traffic dashboard</h2>
        <div class="dashgrid">
            <div class="lightbox" id="light" aria-label="Traffic signal">
                <span class="lamp <?= $s->getSignal() === 'RED' ? 'red' : '' ?>" id="lamp-red"></span>
                <span class="lamp <?= $s->getSignal() === 'YELLOW' ? 'amber' : '' ?>" id="lamp-amber"></span>
                <span class="lamp <?= $s->getSignal() === 'GREEN' ? 'green' : '' ?>" id="lamp-green"></span>
            </div>
            <div class="countdown"><span id="count">--</span><span class="muted" id="count-label">Time left</span></div>
            <div class="stats">
                <div><span class="big" id="stat-signal"><?= h($s->getSignal()) ?></span><span class="muted">Signal</span></div>
                <div><span class="big" id="stat-green"><?= h($s->getGreenSeconds()) ?>s</span><span class="muted">Green time</span></div>
                <div><span class="big"><?= h($s->getQueueMeters()) ?>m</span><span class="muted">Queue</span></div>
            </div>
        </div>
    </section>
<?php }