<?php
// OWNER: Tama Owen | UC-02 Process Traffic Violation | UC-04 Pay Fine via Mobile Gateway
class Violation
{
    public string $status = "UNPAID";
    public function __construct(
        public int $id, public string $plate, public string $type, public int $amount
    ) {}
    public function __toString(): string
    {
        return "#{$this->id} {$this->plate} {$this->type} KES {$this->amount} [{$this->status}]";
    }
}

class ViolationService
{
    private array $violations = [];
    private AlertService $alerts;
    private int $nextId = 1;

    public function __construct(AlertService $alerts) { $this->alerts = $alerts; }

    /** UC-02: record violation, generate penalty notice, notify motorist by SMS. */
    public function record(string $plate, string $type, int $amount, string $ownerPhone): Violation
    {
        $v = new Violation($this->nextId++, $plate, $type, $amount);
        $this->violations[] = $v;
        $this->alerts->sendSms($ownerPhone, "Fine KES $amount for $type ($plate). Pay via M-PESA.");
        return $v;
    }

    /** UC-04: simulated M-PESA payment. Returns true if the fine was paid. */
    public function pay(int $violationId, string $mpesaPhone): bool
    {
        foreach ($this->violations as $v) {
            if ($v->id === $violationId && $v->status === "UNPAID") {
                $v->status = "PAID";
                $this->alerts->sendSms($mpesaPhone, "M-PESA receipt: KES {$v->amount} paid for fine #{$v->id}");
                return true;
            }
        }
        return false;
    }

    public function getViolations(): array { return $this->violations; }
}
