<?php
// OWNER: Del Sambu | Alerts (simulated SMS + road display)
class AlertService
{
    private array $alerts = [];
    public bool $verbose = true;   // set false in tests to keep output clean

    public function sendSms(string $phone, string $message): void
    {
        $this->add("SMS to $phone: $message");
    }

    public function roadDisplay(string $message): void
    {
        $this->add("ROAD DISPLAY: $message");
    }

    private function add(string $a): void
    {
        $this->alerts[] = $a;
        if ($this->verbose) echo "[ALERT] $a\n";
    }

    public function getAlerts(): array { return $this->alerts; }
}
