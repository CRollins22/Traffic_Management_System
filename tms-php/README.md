# Traffic Management System (TMS) - Junction Simulator (PHP)

Console simulator for the TMS scope: adaptive signals (BR-01), emergency override (BR-02),
violations and fines (UC-02/UC-04), operator login with MFA (BR-03), alerts and dashboard.

## Run (PHP 8.0+)
    cd src
    php main.php     # login: operator / traffic123 / MFA 123456
    php tests.php    # runs the 13 tests

## Who owns which file
| Member | File |
|---|---|
| Claude Rollins | main.php |
| Daniel Kirima | SignalController.php |
| Muti Michael | EmergencyService.php |
| Tama Owen | ViolationService.php |
| Del Sambu | AlertService.php, Dashboard.php |
| Anthony Kariuki | Security.php, tests.php |
