<?php
// OWNER: Daniel Kirima | UC-01 Adjust Traffic Signals | BR-01 Adaptive Signal Rule
class SignalController
{
    const DEFAULT_GREEN = 30;   // seconds
    const STEP = 15;            // BR-01: increase by 15 s
    const MAX_GREEN = 120;      // BR-01: cap at 120 s
    const QUEUE_LIMIT = 80;     // metres
    const MINUTES_LIMIT = 3;    // minutes

    private int $greenSeconds = self::DEFAULT_GREEN;
    private string $signal = "RED";      // RED or GREEN
    private int $queueMeters = 0;

    /** BR-01: if queue > 80 m for more than 3 minutes, add 15 s green (max 120 s). */
    public function updateTraffic(int $queueMeters, int $minutesCongested): void
{
    $this->queueMeters = $queueMeters;
    if ($queueMeters > self::QUEUE_LIMIT && $minutesCongested > self::MINUTES_LIMIT) {
        $this->greenSeconds = min(self::MAX_GREEN, $this->greenSeconds + self::STEP);
    } else {
        $this->greenSeconds = self::DEFAULT_GREEN;   // traffic is normal again, go back to 30s
    }
}

    public function setSignal(string $s): void { $this->signal = $s; }
    public function getGreenSeconds(): int { return $this->greenSeconds; }
    public function getSignal(): string { return $this->signal; }
    public function getQueueMeters(): int { return $this->queueMeters; }
}
