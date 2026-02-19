-- ============================================
-- DATABASE: laporan_sanitasi
-- Jalankan file ini di phpMyAdmin atau MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS laporan_sanitasi
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE laporan_sanitasi;

CREATE TABLE IF NOT EXISTS laporan (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  waktu       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  kategori    VARCHAR(100) NOT NULL,
  status      ENUM('pending','verified','in_progress','resolved','rejected')
              NOT NULL DEFAULT 'pending',
  ringkasan   TEXT,
  deskripsi   TEXT,
  latitude    DECIMAL(10,7),
  longitude   DECIMAL(10,7),
  foto_path   VARCHAR(255),
  catatan_admin TEXT,
  updated_at  DATETIME     ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data contoh bawaan
INSERT INTO laporan (kategori, status, ringkasan, deskripsi, latitude, longitude) VALUES
('Air Bersih',   'pending',     'Pipa bocor di RT 03',     'Pipa utama bocor sejak 3 hari lalu, air menggenang di jalan.', -6.200000, 106.816666),
('Sanitasi',     'verified',    'Saluran mampet',          'Gorong-gorong mampet menyebabkan banjir kecil saat hujan.',     -6.210000, 106.820000),
('Limbah',       'in_progress', 'Pembuangan ilegal',       'Ditemukan pembuangan limbah cair tidak resmi di sungai.',        -6.195000, 106.825000),
('Air Bersih',   'resolved',    'Kualitas air buruk',      'Air dari keran berwarna keruh dan berbau.',                      -6.205000, 106.812000),
('Sanitasi',     'rejected',    'Duplikat laporan',        'Laporan ini merupakan duplikat dari laporan sebelumnya.',         -6.215000, 106.830000);
