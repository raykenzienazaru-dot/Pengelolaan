<?php
// config.php — Konfigurasi koneksi database
// Sesuaikan nilai di bawah jika XAMPP Anda berbeda

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // default XAMPP
define('DB_PASS', '');           // default XAMPP (kosong)
define('DB_NAME', 'laporan_sanitasi');
define('DB_PORT', 3306);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'error' => true,
                'message' => 'Koneksi database gagal: ' . $e->getMessage(),
                'hint'  => 'Pastikan XAMPP MySQL berjalan & database sudah dibuat via database.sql'
            ]));
        }
    }
    return $pdo;
}
