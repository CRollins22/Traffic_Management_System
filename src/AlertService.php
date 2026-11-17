<?php
// OWNER: Del Sambu | Alerts (simulated SMS + road display)
class AlertService
{
    private array $alerts = [];
    public bool $verbose = false;   // true prints to console (not needed for the web app)

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
        array_unshift($this->alerts, date("H:i:s") . "  " . $a);   // newest first
        if ($this->verbose) echo "[ALERT] $a\n";
    }

    public function getAlerts(): array { return $this->alerts; }
}
