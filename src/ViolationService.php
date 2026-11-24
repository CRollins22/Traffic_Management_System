<?php

class ViolationService
{
    // sample fines in KES, change to match your spec
    const FINES = [
        'speeding' => 3000,
        'red_light' => 5000,
        'illegal_parking' => 2000,
    ];

    private array $violations = [];
    private array $smsLog = [];
    private int $nextId = 1;

    public function recordViolation(string $plate, string $type, string $location, string $phone): array
    {
        $plate = strtoupper(trim($plate));
        $type = strtolower(trim($type));
        $location = trim($location);
        $phone = trim($phone);

        if ($plate === '') {
            throw new InvalidArgumentException('Number plate is required');
        }
                if (!preg_match('/^[A-Z]{2,3}\s?\d{3}[A-Z]?$/', $plate)) {
            throw new InvalidArgumentException('Invalid number plate');
        }
        if (!isset(self::FINES[$type])) {
            throw new InvalidArgumentException('Invalid violation type');
        }
        if ($location === '') {
            throw new InvalidArgumentException('Location is required');
        }
        if (!preg_match('/^(?:\+254|0)[17]\d{8}$/', $phone)) {
            throw new InvalidArgumentException('Invalid phone number');
        }

        $id = 'V' . str_pad((string) $this->nextId++, 3, '0', STR_PAD_LEFT);
        $violation = [
            'id' => $id,
            'plate' => $plate,
            'type' => $type,
            'location' => $location,
            'phone' => $phone,
            'fine' => self::FINES[$type],
            'status' => 'unpaid',
            'receipt' => null,
            'time' => date('Y-m-d H:i:s'),
        ];
        $this->violations[$id] = $violation;

        $this->sendSms(
            $phone,
            "Traffic violation $id: $type at $location. Fine KES {$violation['fine']}. Pay via M-PESA quoting $id."
        );
        return $violation;
    }

    // Simulated M-PESA payment, no real money involved
    public function payViolation(string $id): array
    {
        $v = $this->getViolation($id);

        if ($v['status'] === 'paid') {
            throw new RuntimeException("Violation $id is already paid (receipt {$v['receipt']})");
        }

        $v['receipt'] = 'MP' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
        $v['status'] = 'paid';
        $this->violations[$id] = $v;

        $this->sendSms(
            $v['phone'],
            "M-PESA receipt {$v['receipt']}: KES {$v['fine']} paid for violation $id. Thank you."
        );
        return $v;
    }

    public function getViolation(string $id): array
    {
        if (!isset($this->violations[$id])) {
            throw new InvalidArgumentException("Violation $id not found");
        }
        return $this->violations[$id];
    }

    public function getAll(): array
    {
        return array_values($this->violations);
    }

    public function getSmsLog(): array
    {
        return $this->smsLog;
    }

    // Simulated SMS, just stores the message
    private function sendSms(string $phone, string $message): void
    {
        $this->smsLog[] = ['to' => $phone, 'message' => $message];
    }
}
