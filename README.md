# Portal Lomba Hari HAM

Portal ini menggunakan Laravel 13 dan Blade. Halaman peserta dan admin dirender dari
`resources/views`, menggunakan layout bersama `resources/views/layouts/app.blade.php`.

## Menjalankan di XAMPP

1. Pastikan PHP 8.3 atau lebih baru, Composer, ekstensi `pdo_mysql`, dan ekstensi `zip`
   tersedia.
2. Jalankan Apache dan MySQL dari XAMPP. Database lama `db_kemenham` dan tabel-tabelnya
   harus sudah tersedia.
3. Salin `.env.example` menjadi `.env`, pastikan `APP_URL` sesuai alamat XAMPP
   (default `http://localhost/kemenhamversiclone`), lalu sesuaikan `DB_HOST`,
   `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` dengan MySQL lokal.
4. Jalankan `composer install` dan `php artisan key:generate`.
5. Untuk XAMPP pada `htdocs/kemenhamversiclone`, buka
   `http://localhost/kemenhamversiclone/`. Konfigurasi `.htaccess` meneruskan
   permintaan ke Laravel dan menyajikan aset dari `public/`.

Alternatif untuk development adalah `php artisan serve`; buka URL yang ditampilkan
oleh Artisan. Untuk deployment Apache, arahkan document root langsung ke folder
`public/` jika memungkinkan.

Jangan jalankan `php artisan migrate` pada database lama: aplikasi menggunakan
skema `db_kemenham` yang sudah ada dan tidak memerlukan migrasi otomatis. Referensi
skema lama tersedia di `php/migrations/00_schema.sql`.

## Rute utama

- `/` — halaman utama
- `/masuk` dan `/daftar-akun` — autentikasi peserta
- `/admin/masuk` — autentikasi admin
- `/lomba/buku-cerita/daftar` dan `/lomba/komik/daftar` — pendaftaran
- `/lomba/{kategori}/unggah`, `/profil`, dan `/notifikasi` — halaman peserta
- `/admin/dashboard` — dashboard dan tinjau pendaftaran

Formulir menggunakan POST Laravel dengan CSRF, validasi, redirect, dan flash message.
File unggahan disimpan di luar direktori publik; akses unduhan melewati rute aplikasi
yang memeriksa izin pengguna.
