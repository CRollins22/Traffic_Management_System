<?php
// OWNER: Anthony Kariuki | BR-03 Security (hashed passwords)
/**
 * Handles all cryptographic operations for user authentication.
 * Part of BR-03 compliance.
 */
class Security
{
        /** 
     * BR-03: Passwords must never be stored in plain text.
     * We use PHP's built-in password_hash with the default algorithm (currently bcrypt).
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /** True if the typed password matches the stored hash. */
    public static function verifyPassword(string $password, string $hash): bool
    {
                // Compare the plain text password against the stored hash.
        return password_verify($password, $hash);
    }
}