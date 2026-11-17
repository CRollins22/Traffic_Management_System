<?php
// OWNER: Claude Rollins | Menu + integration of all modules
// Run: php main.php
require_once __DIR__ . '/SignalController.php';
require_once __DIR__ . '/AlertService.php';
require_once __DIR__ . '/EmergencyService.php';
require_once __DIR__ . '/ViolationService.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Dashboard.php';

function ask(string $prompt): string
{
    echo $prompt;
    $line = fgets(STDIN);
    return $line === false ? "" : trim($line);
}

echo "=== Traffic Management System ===\n";
$u = ask("Username: ");
$p = ask("Password: ");
$m = ask("MFA code: ");
if (!Security::login($u, $p, $m)) { echo "Login failed.\n"; exit(1); }
echo "Login successful.\n\n";

$signals = new SignalController();
$alerts = new AlertService();
$emergency = new EmergencyService($signals, $alerts);
$violations = new ViolationService($alerts);

while (true) {
    echo "1. Simulate traffic   2. Emergency vehicle   3. Record violation\n";
    echo "4. Pay fine           5. Dashboard           0. Exit\n";
    $c = ask("Choice: ");
    try {
        switch ($c) {
            case "1":
                $q = (int) ask("Queue length (m): ");
                $min = (int) ask("Minutes congested: ");
                $signals->updateTraffic($q, $min);
                echo "Green time is now {$signals->getGreenSeconds()}s\n";
                break;
            case "2":
                $id = ask("Vehicle ID: ");
                $d = (int) ask("Distance (m): ");
                $s = (int) ask("Speed (km/h): ");
                $eta = $emergency->requestPriority($id, $d, $s);
                echo "Signal -> {$signals->getSignal()}, ETA {$eta}s\n";
                ask("Press Enter when vehicle has cleared...");
                $emergency->vehicleCleared($id);
                echo "Signal restored -> {$signals->getSignal()}\n";
                break;
            case "3":
                $plate = ask("Plate: ");
                $t = ask("Violation type (e.g. Red light): ");
                $amt = (int) ask("Fine (KES): ");
                $ph = ask("Owner phone: ");
                echo "Recorded " . $violations->record($plate, $t, $amt, $ph) . "\n";
                break;
            case "4":
                $id = (int) ask("Violation ID: ");
                $ph = ask("M-PESA phone: ");
                echo $violations->pay($id, $ph) ? "Payment successful.\n" : "Payment failed (not found or already paid).\n";
                break;
            case "5":
                Dashboard::show($signals, $violations, $emergency, $alerts);
                break;
            case "0":
                echo "Goodbye.\n";
                exit(0);
            default:
                echo "Invalid choice.\n";
        }
    } catch (InvalidArgumentException $ex) {
        echo "Error: " . $ex->getMessage() . "\n";
    }
}
