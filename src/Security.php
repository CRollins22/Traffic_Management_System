<?php
// OWNER: Anthony Kariuki | BR-03 Security (operator login with MFA)
class Security
{
    private const USER = "operator";
    private const PASSWORD = "traffic123";
    private const MFA_CODE = "123456";   // fixed code for the demo

    /** BR-03: operator needs correct password AND MFA code. */
    public static function login(string $user, string $password, string $mfa): bool
    {
        return $user === self::USER && $password === self::PASSWORD && $mfa === self::MFA_CODE;
    }
}
