<?php
// OWNER: Anthony Kariuki | BR-03 Security (hashed passwords)
class Security
{
    /** Turns a password into a hash. Only the hash is stored, never the password. */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /** True if the typed password matches the stored hash. */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}