<?php
// OWNER: Claude Rollins | saves and finds users in the database
class UserRepository
{
    /** Saves a new user (for road users later). Returns false if the username is already taken. */
    public static function create(string $username, string $passwordHash, string $role = 'road_user'): bool
    {
                // Attempt to insert the new user into the database.
        try {
            $st = db()->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
            $st->execute([$username, $passwordHash, $role]);
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') return false;   // 23000 = duplicate username
            throw $e;
        }
    }

    /** Returns the user row (id, username, password_hash, role) or null if not found. */
    public static function find(string $username): ?array
    {
        $st = db()->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ?');
        $st->execute([$username]);
        return $st->fetch() ?: null;
    }
}