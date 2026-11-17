<?php
// OWNER: Muti Michael | UC-03 Dispatch Emergency Override | BR-02 Emergency Override Rule
class EmergencyService
{
    private SignalController $signals;
    private AlertService $alerts;
    private array $auditLog = [];
    private string $previousSignal = "RED";

    public function __construct(SignalController $signals, AlertService $alerts)
    {
        $this->signals = $signals;
        $this->alerts = $alerts;
    }

    /** Vehicle sends priority request + distance (m) and speed (km/h). Returns ETA in seconds. */
    public function requestPriority(string $vehicleId, int $distanceMeters, int $speedKmh): int
    {
        if ($speedKmh <= 0) throw new InvalidArgumentException("Speed must be positive");
        $etaSeconds = (int) round($distanceMeters / ($speedKmh / 3.6));
        $this->previousSignal = $this->signals->getSignal();
        $this->signals->setSignal("GREEN");                       // BR-02: immediate green passage
        $this->auditLog[] = "OVERRIDE vehicle=$vehicleId dist={$distanceMeters}m speed={$speedKmh}km/h eta={$etaSeconds}s"; // BR-02: audit trail
        $this->alerts->roadDisplay("Emergency vehicle $vehicleId approaching. ETA {$etaSeconds}s");
        return $etaSeconds;
    }

    /** Sensors confirm the vehicle has cleared: return to normal scheduling. */
    public function vehicleCleared(string $vehicleId): void
    {
        $this->signals->setSignal($this->previousSignal);
        $this->auditLog[] = "RESTORED vehicle=$vehicleId signal={$this->previousSignal}";
    }

    public function getAuditLog(): array { return $this->auditLog; }
}
