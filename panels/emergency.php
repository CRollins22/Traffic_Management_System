<?php
// OWNER: Muti Michael | UC-03 emergency vehicle priority page (BR-02)
function panel_emergency_handle(EmergencyService $em, string $action): void
{
    if ($action === 'emergency_request') {
        $id = trim($_POST['vehicle'] ?? '');
        $d = int_field('distance'); $sp = int_field('speed');
        if ($id === '' || $d === null || $sp === null) { flash("Fill in vehicle ID, distance and speed.", "err"); return; }
        try {
            $eta = $em->requestPriority($id, $d, $sp);
            flash("Priority granted to $id. Signal is GREEN. ETA {$eta}s.");
        } catch (InvalidArgumentException $e) {
            flash("Error: " . $e->getMessage(), "err");
        }
    } elseif ($action === 'emergency_clear') {
        $active = $em->getActiveVehicle();
        if ($active === null) { flash("No active emergency.", "err"); return; }
        $em->vehicleCleared($active);
        flash("Vehicle $active cleared the junction. Normal signal timing restored.");
    }
}

function panel_emergency_render(EmergencyService $em): void { $active = $em->getActiveVehicle(); ?>
    <section class="card">
        <h2>2. Emergency vehicle priority <span class="tag">UC-03 / BR-02</span></h2>
        <?php if ($active): ?>
            <p class="alert-on">Emergency override ACTIVE for <strong><?= h($active) ?></strong>. Signal forced to GREEN.</p>
            <form method="post">
                <input type="hidden" name="action" value="emergency_clear">
                <button class="secondary">Vehicle has cleared the junction</button>
            </form>
        <?php else: ?>
            <form method="post" class="row">
                <input type="hidden" name="action" value="emergency_request">
                <label>Vehicle ID <input name="vehicle" value="AMB-01" required></label>
                <label>Distance (m) <input name="distance" type="number" min="0" value="500" required></label>
                <label>Speed (km/h) <input name="speed" type="number" min="1" value="60" required></label>
                <button>Request priority</button>
            </form>
        <?php endif; ?>
    </section>
<?php }
