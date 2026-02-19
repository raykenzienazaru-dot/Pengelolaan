<?php
// test_koneksi.php — Halaman diagnosa koneksi database
// Buka di browser: http://localhost/laporan_app/test_koneksi.php
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Test Koneksi Database</title>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #0b1120; color: #e2e8f0; padding: 40px; }
    .card { background: #192035; border: 1px solid #253050; border-radius: 12px; padding: 24px; max-width: 600px; margin: 0 auto; }
    h1 { margin-bottom: 20px; }
    .ok   { color: #4ade80; }
    .err  { color: #f87171; }
    .info { color: #60a5fa; }
    pre { background: #131d33; padding: 12px; border-radius: 8px; font-size: 12px; overflow-x: auto; }
    a { color: #60a5fa; }
    .btn { display:inline-block; padding:10px 20px; background:#3b82f6; color:#fff; border-radius:8px; text-decoration:none; margin-top:16px; }
  </style>
</head>
<body>
<div class="card">
  <h1>🔍 Diagnostik Koneksi Database</h1>

  <?php
  // Cek koneksi
  try {
    $db = getDB();
    echo '<p class="ok">✅ <strong>Koneksi ke MySQL berhasil!</strong></p>';

    // Cek database
    $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    echo "<p class='info'>📦 Database aktif: <strong>{$dbName}</strong></p>";

    // Cek tabel laporan
    $tables = $db->query("SHOW TABLES LIKE 'laporan'")->fetchAll();
    if ($tables) {
      echo '<p class="ok">✅ Tabel <strong>laporan</strong> ditemukan.</p>';

      // Hitung row
      $count = $db->query("SELECT COUNT(*) FROM laporan")->fetchColumn();
      echo "<p class='info'>📊 Jumlah data laporan: <strong>{$count}</strong></p>";

      // Sample data
      $sample = $db->query("SELECT id, waktu, kategori, status, ringkasan FROM laporan LIMIT 3")->fetchAll();
      if ($sample) {
        echo '<p class="info">📋 Sample data:</p>';
        echo '<pre>';
        foreach ($sample as $r) {
          echo "ID:{$r['id']} | {$r['kategori']} | {$r['status']} | {$r['ringkasan']}\n";
        }
        echo '</pre>';
      }
    } else {
      echo '<p class="err">❌ Tabel <strong>laporan</strong> TIDAK ditemukan.</p>';
      echo '<p>Jalankan file <code>database.sql</code> di phpMyAdmin terlebih dahulu.</p>';
    }

  } catch (Exception $e) {
    echo '<p class="err">❌ <strong>Koneksi gagal!</strong></p>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '<h3>Kemungkinan penyebab:</h3>';
    echo '<ul>';
    echo '<li>MySQL di XAMPP belum dijalankan (Start MySQL di XAMPP Control Panel)</li>';
    echo '<li>Database <strong>laporan_sanitasi</strong> belum dibuat (import database.sql)</li>';
    echo '<li>Username/password salah (default: root / kosong)</li>';
    echo '</ul>';
  }
  ?>

  <hr style="border-color:#253050; margin:20px 0" />
  <h3>Konfigurasi Saat Ini</h3>
  <pre>
Host   : <?= DB_HOST ?>

Port   : <?= DB_PORT ?>

User   : <?= DB_USER ?>

DB     : <?= DB_NAME ?>
  </pre>

  <a class="btn" href="index.php">← Kembali ke Dashboard</a>
</div>
</body>
</html>
