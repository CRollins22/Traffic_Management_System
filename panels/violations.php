<?php
// OWNER: Tama Owen | UC-02 record violation, UC-04 pay fine (simulated M-PESA)
function panel_violation_handle(ViolationService $vs, string $action): void
{
    if ($action === 'violation_record') {
        $plate = strtoupper(trim($_POST['plate'] ?? '')); $type = trim($_POST['type'] ?? '');
        $amt = int_field('amount'); $phone = trim($_POST['phone'] ?? '');
        if ($plate === '' || $type === '' || $amt === null || $amt <= 0 || $phone === '') { flash("Fill in all violation fields with a valid amount.", "err"); return; }
        $v = $vs->record($plate, $type, $amt, $phone);
        flash("Violation recorded: $v. SMS sent to $phone.");
    } elseif ($action === 'violation_pay') {
        $id = int_field('id'); $phone = trim($_POST['phone'] ?? '');
        if ($id === null || $phone === '') { flash("Missing fine or M-PESA phone number.", "err"); return; }
        $paid = $vs->pay($id, $phone);
        flash($paid ? "M-PESA payment successful for fine #$id." : "Payment failed: fine not found or already paid.",
              $paid ? "ok" : "err");
    }
}

function panel_violation_render(ViolationService $vs): void { ?>
    <section class="card">
        <h2>3. Violations and fines <span class="tag">UC-02 / UC-04</span></h2>
        <form method="post" class="row">
            <input type="hidden" name="action" value="violation_record">
            <label>Plate <input name="plate" value="KDA 123A" required></label>
            <label>Violation <input name="type" value="Red light" required></label>
            <label>Fine (KES) <input name="amount" type="number" min="1" value="5000" required></label>
            <label>Owner phone <input name="phone" value="0712345678" required></label>
            <button>Record violation</button>
        </form>
        <table>
            <tr><th>ID</th><th>Plate</th><th>Violation</th><th>Fine</th><th>Status</th><th>Pay via M-PESA</th></tr>
            <?php foreach ($vs->getViolations() as $v): ?>
            <tr>
                <td>#<?= h($v->id) ?></td><td><?= h($v->plate) ?></td><td><?= h($v->type) ?></td>
                <td>KES <?= h($v->amount) ?></td>
                <td><span class="pill <?= $v->status === 'PAID' ? 'paid' : 'unpaid' ?>"><?= h($v->status) ?></span></td>
                <td>
                    <?php if ($v->status === 'UNPAID'): ?>
                    <form method="post" class="inline">
                        <input type="hidden" name="action" value="violation_pay">
                        <input type="hidden" name="id" value="<?= h($v->id) ?>">
                        <input name="phone" value="0712345678" size="11" required>
                        <button class="small">Pay</button>
                    </form>
                    <?php else: ?>&ndash;<?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$vs->getViolations()): ?><tr><td colspan="6" class="muted">No violations yet.</td></tr><?php endif; ?>
        </table>
    </section>
<?php }
