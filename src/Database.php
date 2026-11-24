<?php
// OWNER: Claude Rollins | one shared database connection (settings are XAMPP's defaults)
const DB_HOST = '127.0.0.1';
const DB_NAME = 'tms';
const DB_USER = 'root';
const DB_PASS = '';          // XAMPP's root user has no password by default

function db(): PDO
{
    static $pdo = null;      // created once, then reused
    if ($pdo === null) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // errors throw exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // rows come back as arrays
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+03:00'",  // saved times in Nairobi time (UTC+3)
        ]);
    }
    return $pdo;
}