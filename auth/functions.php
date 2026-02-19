<?php
// auth/functions.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'laporan_sanitasi');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Panggil di awal setiap halaman yang butuh login.
 * $role = 'admin' | 'user' | null (semua role boleh)
 */
function require_login(string $role = null): void {
    if (!isLoggedIn()) {
        header("Location: auth/login.php");
        exit;
    }
    if ($role !== null && $_SESSION['user_role'] !== $role) {
        // Arahkan ke halaman sesuai role mereka
        if ($_SESSION['user_role'] === 'admin') {
            header('Location: admin.php'); exit;
        } else {
            header('Location: user.php'); exit;
        }
    }
}

// Alias camelCase (untuk kompatibilitas)
function requireLogin(string $redirect = 'auth/login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

function currentUser(): array {
    return [
        'id'    => $_SESSION['user_id']    ?? null,
        'nama'  => $_SESSION['user_nama']  ?? '',
        'role'  => $_SESSION['user_role']  ?? '',
        'email' => $_SESSION['user_email'] ?? '',
    ];
}
