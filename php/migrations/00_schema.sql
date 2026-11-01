-- Skema database lokal untuk proyek kemenham (XAMPP / MySQL / MariaDB).
-- Dibuat ulang dari query di folder php/, karena export database aslinya tidak ikut di zip.
-- Cara pakai: phpMyAdmin -> tab "Import" -> pilih file ini -> Kirim.
-- Aman dijalankan ulang (CREATE ... IF NOT EXISTS), tidak menghapus data.

CREATE DATABASE IF NOT EXISTS db_kemenham CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_kemenham;

CREATE TABLE IF NOT EXISTS pengguna (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nama_depan    VARCHAR(60)  NOT NULL,
  nama_belakang VARCHAR(60)  NULL,
  email         VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('peserta','admin') NOT NULL DEFAULT 'peserta',
  telepon       VARCHAR(20)  NULL,
  gender        ENUM('Laki-laki','Perempuan') NOT NULL,
  tempat_lahir  VARCHAR(80)  NULL,
  tanggal_lahir DATE         NOT NULL,
  alamat        VARCHAR(255) NULL,
  dibuat_pada   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pengguna_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lomba (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kode         VARCHAR(40)  NOT NULL,
  nama         VARCHAR(120) NOT NULL,
  batas_daftar DATE         NULL,
  UNIQUE KEY uq_lomba_kode (kode)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pendaftaran (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pengguna_id   INT UNSIGNED NOT NULL,
  lomba_id      INT UNSIGNED NOT NULL,
  nama_peserta  VARCHAR(120) NOT NULL,
  tipe_tim      ENUM('Individu','Kelompok') NOT NULL DEFAULT 'Individu',
  anggota2      VARCHAR(120) NULL,
  anggota3      VARCHAR(120) NULL,
  nomor_induk   VARCHAR(40)  NOT NULL,
  asal          VARCHAR(120) NOT NULL,
  email         VARCHAR(120) NOT NULL,
  whatsapp      VARCHAR(20)  NOT NULL,
  file_kartu    VARCHAR(255) NULL,
  tahap         VARCHAR(20)  NOT NULL DEFAULT 'terbuka',      -- terbuka | terkirim
  status        VARCHAR(30)  NOT NULL DEFAULT 'Terdaftar',    -- Terdaftar | Karya terkirim | Diverifikasi | Perlu revisi | Ditolak
  catatan_admin VARCHAR(255) NULL,
  dibuat_pada   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pendaftaran (pengguna_id, lomba_id),
  CONSTRAINT fk_pendaftaran_pengguna FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE CASCADE,
  CONSTRAINT fk_pendaftaran_lomba    FOREIGN KEY (lomba_id)    REFERENCES lomba(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS karya (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pendaftaran_id  INT UNSIGNED NOT NULL,
  judul           VARCHAR(200) NOT NULL,
  file_pdf        VARCHAR(255) NOT NULL DEFAULT '',
  file_word       VARCHAR(255) NOT NULL DEFAULT '',
  file_kartu      VARCHAR(255) NOT NULL DEFAULT '',
  file_pernyataan VARCHAR(255) NOT NULL DEFAULT '',
  dikirim_pada    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_karya_pendaftaran (pendaftaran_id),
  CONSTRAINT fk_karya_pendaftaran FOREIGN KEY (pendaftaran_id) REFERENCES pendaftaran(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifikasi (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pengguna_id INT UNSIGNED NOT NULL,
  lomba_id    INT UNSIGNED NULL,
  pesan       VARCHAR(255) NOT NULL,
  dibaca      TINYINT(1)   NOT NULL DEFAULT 0,
  dibuat_pada TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notifikasi_pengguna (pengguna_id),
  CONSTRAINT fk_notifikasi_pengguna FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Data awal: dua lomba (kode harus sama persis dengan yang dipakai frontend)
INSERT IGNORE INTO lomba (kode, nama, batas_daftar) VALUES
  ('buku-cerita', 'Lomba Buku Cerita HAM',        '2026-11-04'),
  ('komik',       'Lomba Serial Komik Bergambar', '2026-11-04');

-- Catatan: 20261020_pendaftaran_kartu.sql TIDAK perlu dijalankan,
-- karena kolom pendaftaran.file_kartu sudah ada di skema ini.
