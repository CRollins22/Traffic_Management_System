<?php
// OWNER: Claude Rollins | operator view of incidents reported by motorists

function panel_incident_handle(string $action): void
{
    if ($action === 'incident_resolve') {
        MotoristRepository::resolveIncident((int) ($_POST['id'] ?? 0));
        flash("Incident marked as resolved.");
    }
}

/** The incidents table as an HTML string, so light.js can refresh it live. */
function panel_incidents_html(): string
{
    ob_start();
    panel_incidents_body();
    return ob_get_clean();
}

function panel_incidents_render(): void
{
    ?>
    <section class="card">
        <h2>Incidents reported by motorists</h2>
        <div id="incidents"><?php panel_incidents_body(); ?></div>
    </section>
<?php }

function panel_incidents_body(): void
{
    $rows = MotoristRepository::recentIncidents();
    if (!$rows) { ?><p class="muted">No reports yet.</p><?php return; }
    ?>
    <div class="scroll"><table>
        <tr><th>Time</th><th>Plate</th><th>What</th><th>Where</th><th>Details</th><th>Status</th><th></th></tr>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= h(substr($r['created_at'], 5, 11)) ?></td>
            <td><?= h($r['plate']) ?></td>
            <td><?= h(Motorist::INCIDENT_TYPES[$r['type']]) ?></td>
            <td><?= h($r['location']) ?></td>
            <td><?= h($r['details'] ?? '') ?></td>
            <td><span class="pill <?= $r['status'] === 'OPEN' ? 'open' : 'resolved' ?>"><?= h($r['status']) ?></span></td>
            <td><?php if ($r['status'] === 'OPEN'): ?>
                <form method="post" class="inline"><input type="hidden" name="action" value="incident_resolve"><input type="hidden" name="id" value="<?= h($r['id']) ?>"><button class="secondary small">Resolve</button></form>
            <?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
    </table></div>
<?php }