<?php
// OWNER: Del Sambu | live dashboard + alerts feed
function panel_dashboard_render(SignalController $s, EmergencyService $e, AlertService $a, ViolationService $v): void
{
    $unpaid = count(array_filter($v->getViolations(), fn($x) => $x->status === 'UNPAID'));
    ?>
    <section class="card dash">
        <h2>Traffic dashboard</h2>
        <div class="dashgrid">
            <div class="lightbox" aria-label="Signal is <?= h($s->getSignal()) ?>">
                <span class="lamp <?= $s->getSignal() === 'RED' ? 'red' : '' ?>"></span>
                <span class="lamp"></span>
                <span class="lamp <?= $s->getSignal() === 'GREEN' ? 'green' : '' ?>"></span>
            </div>
            <div class="stats">
                <div><span class="big"><?= h($s->getSignal()) ?></span><span class="muted">Signal</span></div>
                <div><span class="big"><?= h($s->getGreenSeconds()) ?>s</span><span class="muted">Green time</span></div>
                <div><span class="big"><?= h($s->getQueueMeters()) ?>m</span><span class="muted">Queue</span></div>
                <div><span class="big"><?= $unpaid ?></span><span class="muted">Unpaid fines</span></div>
            </div>
        </div>
        <div class="two">
            <div>
                <h3>Emergency audit log</h3>
                <ul class="log"><?php foreach (array_reverse($e->getAuditLog()) as $l): ?><li><?= h($l) ?></li><?php endforeach; ?>
                <?php if (!$e->getAuditLog()): ?><li class="muted">No emergency events.</li><?php endif; ?></ul>
            </div>
            <div>
                <h3>Alerts sent (<?= count($a->getAlerts()) ?>)</h3>
                <ul class="log"><?php foreach (array_slice($a->getAlerts(), 0, 8) as $l): ?><li><?= h($l) ?></li><?php endforeach; ?>
                <?php if (!$a->getAlerts()): ?><li class="muted">No alerts yet.</li><?php endif; ?></ul>
            </div>
        </div>
    </section>
<?php }
