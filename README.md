# Qreative LMS

LMS kursus bahasa Inggris dengan PHP native dan SQLite (PDO), tanpa framework
atau dependensi Composer.

## Fitur

- Dashboard untuk role admin, guru, dan murid.
- CRUD kursus dan materi; guru hanya dapat mengelola kursus miliknya.
- Admin dapat mengelola pengguna dan role.
- Murid dapat mendaftar ke kursus terbit dan menyimpan progres materi.
- Jadwal sesi kelas untuk admin, guru, dan murid: admin/guru mengatur sesi
  kursus terbit, murid melihat agenda kursus yang telah diikuti.
- Pesan privat tersimpan antara murid dan guru pada kursus yang diikuti, serta
  antara guru dan admin; percakapan memiliki indikator belum dibaca.
- Harga kursus gratis/berbayar, pendaftaran berbayar dengan QRIS yang diunggah
  admin, referensi pembayaran, dan verifikasi manual.
- Admin dapat mencatat pemasukan/pengeluaran, meninjau pembayaran, mengatur
  profil platform, dan melihat laporan bulanan yang dapat diekspor ke CSV.
- Login menggunakan password hashing, session, dan perlindungan CSRF.

## Persyaratan server

- PHP **8.1 atau lebih baru**.
- Ekstensi `PDO` dan `pdo_sqlite` aktif.
- Apache dengan PHP dan `mod_rewrite`, atau Nginx yang dikonfigurasi untuk PHP-FPM.
- Izin tulis PHP untuk lokasi database SQLite.
- HTTPS wajib digunakan di hosting produksi.

Periksa versi dan ekstensi PHP dari terminal:

```bash
php -v
php -m | grep -E 'PDO|pdo_sqlite'
```

Jika `pdo_sqlite` belum terpasang atau aktif, minta penyedia hosting mengaktifkan
ekstensi tersebut untuk versi PHP yang digunakan website maupun CLI.

## Instalasi localhost

### 1. Siapkan proyek

Clone/copy folder proyek, lalu masuk ke foldernya. Contoh:

```bash
cd /path/ke/QreativeLMS
```

Pastikan PHP dan `pdo_sqlite` tersedia:

```bash
php -v
php -m | grep -E 'PDO|pdo_sqlite'
```

### 2. Siapkan database

```bash
php app/setup.php
```

Perintah ini membuat atau memperbarui skema SQLite di `storage/qreative.sqlite`,
membuat akun demo jika emailnya belum ada, dan mengisi contoh kursus/materi jika
belum ada kursus. Setup dapat dijalankan kembali tanpa menghapus data aplikasi.

### 3. Jalankan server pengembangan

```bash
php -S localhost:8000 app/router.php
```

Buka <http://localhost:8000>.

Jika memakai lokasi database khusus, gunakan **path absolut** dan nilai
environment variable yang sama untuk setup dan server web:

```bash
QREATIVE_DB_PATH=/path/pribadi/qreative.sqlite php app/setup.php
QREATIVE_DB_PATH=/path/pribadi/qreative.sqlite php -S localhost:8000 app/router.php
```

Di Windows, atur `QREATIVE_DB_PATH` melalui pengaturan environment Windows atau
PowerShell sebelum menjalankan kedua perintah.

### Akun demo lokal

Semua akun demo menggunakan kata sandi `Qreative123!`.

| Role | Email |
| --- | --- |
| Admin | `admin@qreative.test` |
| Guru | `guru@qreative.test` |
| Murid | `murid@qreative.test` |

Akun dan kata sandi tersebut hanya untuk development. Ganti kata sandi atau
nonaktifkan akun demo melalui menu **Pengguna** sebelum aplikasi dibuka untuk
pengguna lain.

## Instalasi ke hosting

### 1. Siapkan hosting dan domain

Pastikan hosting menyediakan PHP 8.1+, `pdo_sqlite`, HTTPS, dan akses tulis untuk
PHP ke folder database. Aktifkan HTTPS pada domain terlebih dahulu, misalnya
dengan sertifikat SSL dari control panel hosting.

Sebaiknya letakkan file proyek di folder aplikasi dan database di lokasi privat
di luar document root. Contoh struktur:

```text
/home/akun/
├── private/
│   └── qreative.sqlite
└── public_html/
    └── QreativeLMS/
        ├── index.php
        ├── login.php
        ├── assets/
        ├── app/
        ├── database/
        ├── storage/
        └── views/
```

Sesuaikan path dengan control panel hosting. Database **tidak boleh** berada di
folder yang bisa diunduh melalui URL. Pada shared hosting, buat folder privat
di luar `public_html` jika tersedia.

Jika hosting hanya mengizinkan penyimpanan di dalam folder proyek, biarkan
database berada di `storage/qreative.sqlite`. Folder tersebut dilindungi dari
akses web oleh `.htaccess` pada Apache dan oleh router untuk server
pengembangan. Pastikan `.htaccess` benar-benar digunakan oleh konfigurasi
Apache hosting.

### 2. Unggah source code

Unggah source code melalui Git/SSH atau File Manager/FTP ke folder aplikasi.
Jangan unggah database lokal, `.git`, cache, atau file backup. Database SQLite
lokal dikecualikan melalui `.gitignore`; hosting akan memiliki database sendiri.

Pastikan file `.htaccess` ikut terunggah jika menggunakan Apache. **Jangan**
jadikan `app/setup.php`, `database/schema.sql`, atau file dalam `storage/` sebagai
halaman publik.

#### Apache dengan document root khusus

Pilihan terbaik adalah mengatur document root domain/subdomain langsung ke
folder aplikasi. Jika hosting tidak mengizinkannya, folder proyek dapat
ditempatkan di bawah `public_html`; file `.htaccess` proyek memblokir akses web
langsung ke `app/`, `database/`, `storage/`, dan `views/`.

Aturan `.htaccess` memerlukan `mod_rewrite` dan izin hosting
`AllowOverride` yang sesuai. Jika `.htaccess` diabaikan, minta penyedia hosting
mengaktifkan proteksi direktori sebelum menggunakan aplikasi.

#### Nginx dengan PHP-FPM

File `.htaccess` tidak berlaku pada Nginx. Atur document root ke folder aplikasi
dan tambahkan perlindungan berikut pada konfigurasi server block:

```nginx
location ~ ^/(app|database|storage|views)(/|$) {
    deny all;
    return 404;
}

location ~ /\. {
    deny all;
    return 404;
}

location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass unix:/path/to/php-fpm.sock;
}
```

Ganti `/path/to/php-fpm.sock` sesuai konfigurasi hosting. Konfigurasi Nginx
umumnya hanya dapat diubah oleh administrator/VPS; pada shared hosting, minta
bantuan penyedia.

### 3. Atur lokasi database dan izin

Untuk memakai folder privat di luar document root, atur environment variable
`QREATIVE_DB_PATH` ke **path absolut file SQLite**, misalnya:

```text
/home/akun/private/qreative.sqlite
```

Atur variable tersebut untuk **proses PHP web** (Apache/PHP-FPM) dan untuk
terminal/SSH yang dipakai menjalankan setup. Cara mengatur variable pada web
server berbeda antarhosting; minta penyedia hosting memasangnya jika control
panel tidak menyediakan konfigurasi environment variable.

PHP perlu dapat membuat/menulis file database dan file jurnal SQLite di folder
induknya. Jalankan setup sebagai akun yang sama dengan deployment bila
memungkinkan; script membatasi izin file database menjadi `0600`. Jangan
membuka izin database ke semua pengguna (`777`). Jika hosting tidak dapat
menggunakan file dengan izin tersebut, minta penyedia mengatur pemilik/group
folder serta PHP-FPM agar akun PHP dapat menulis secara privat.

### 4. Inisialisasi database di hosting

Jalankan sekali melalui SSH/terminal dari folder aplikasi:

```bash
QREATIVE_DB_PATH=/home/akun/private/qreative.sqlite php app/setup.php
```

Jika memakai database default di `storage/`:

```bash
php app/setup.php
```

Perintah setup hanya boleh dijalankan melalui CLI. Endpoint HTTP untuk setup
akan ditolak. Untuk pembaruan versi berikutnya, backup database terlebih
dahulu, unggah kode baru tanpa menimpa/menghapus database, lalu jalankan kembali
setup jika skema membutuhkan pembaruan.

Jika paket hosting tidak menyediakan SSH/terminal, minta dukungan hosting
menjalankan perintah setup secara aman. Jangan menghapus pemeriksaan CLI atau
membuka setup melalui browser.

### 5. Atur akun admin dan verifikasi

1. Buka `https://domain-anda/.../login.php`.
2. Masuk dengan akun demo admin dari tabel di atas.
3. Buka menu **Pengguna**, ubah kata sandi akun admin, lalu buat akun admin
   pribadi dengan alamat email Anda.
4. Buat akun guru/murid yang diperlukan.
5. Nonaktifkan atau ganti email akun demo admin, guru, dan murid.
6. Buka **Pengaturan** dan unggah gambar QRIS BCA resmi milik organisasi Anda.
   Atur instruksi pembayaran dan alamat email bantuan.
7. Uji pembuatan kursus gratis/berbayar, pendaftaran, progres, verifikasi
   pembayaran, jadwal sesi per role, pencatatan pengeluaran, laporan, dan
   ekspor CSV.
8. Pastikan browser menunjukkan HTTPS dan URL database/folder internal
   menghasilkan respons `404` atau `403`.

Jangan gunakan kata sandi demo pada hosting produksi.

### Alur pembayaran dan laporan

Admin menentukan harga saat membuat atau mengubah kursus. Harga yang disimpan
pada permintaan pembayaran tidak berubah bila harga katalog kemudian diedit.
Murid memilih kursus berbayar, membayar menggunakan QRIS BCA resmi yang
ditampilkan di halaman **Pembayaran**, kemudian mengirim nomor referensi atau
nama pengirim. Admin memeriksa pembayaran di **Keuangan** dan menyetujui atau
menolaknya; akses kursus baru diberikan setelah pembayaran disetujui. Kursus
gratis dapat didaftarkan langsung.

Pembayaran ini dicatat dan diverifikasi secara manual. Aplikasi tidak
menghubungi BCA, membaca bukti pembayaran, atau mengirim/menerima uang secara
otomatis. Admin harus mencocokkan pembayaran dengan mutasi secara mandiri.
Catat pengeluaran dan pemasukan lain di menu **Keuangan**. Menu **Laporan**
merangkum transaksi dan performa kursus per bulan; CSV dapat dibuka dengan
spreadsheet. Unggah hanya gambar QRIS resmi milik organisasi, bukan QRIS
contoh.

## Database

Database default tersimpan di `storage/qreative.sqlite`. Lokasi alternatif
dapat ditetapkan melalui `QREATIVE_DB_PATH`. Tabel utama:

- `users` — akun, role, dan status.
- `courses` — kursus dan guru pengampu.
- `lessons` — materi kursus.
- `enrollments` — pendaftaran murid ke kursus.
- `lesson_progress` — materi yang telah diselesaikan murid.
- `class_schedules` — jadwal sesi, lokasi/tautan pertemuan, dan catatan kelas.
- `conversations`, `messages` — percakapan privat dan status baca pesan.
- `app_settings` — identitas platform, instruksi pembayaran, dan nama file QRIS.
- `finance_transactions` — transaksi pemasukan/pengeluaran dan status verifikasi.

### Pesan antar pengguna

Menu **Pesan** menyediakan percakapan privat tersimpan. Murid dapat menghubungi
guru dari kursus terbit yang diikuti; guru dapat membalas murid pada kursusnya
atau menghubungi admin; admin dapat menghubungi guru aktif. Akses percakapan
dan pengiriman pesan dibatasi server berdasarkan role dan relasi kursus.

### Backup dan pemindahan database

Untuk backup konsisten, gunakan SQLite Backup API atau hentikan sementara
aplikasi sebelum menyalin file database. Salinan file SQLite dapat berada dalam
keadaan tidak konsisten jika dibuat saat database sedang ditulis.

Gunakan `sqlite3` jika tersedia:

```bash
sqlite3 /home/akun/private/qreative.sqlite \
  ".backup '/home/akun/private/qreative-backup.sqlite'"
```

Simpan backup di lokasi privat dengan izin terbatas, dan uji pemulihan backup
sebelum melakukan pemeliharaan database.

## Struktur proyek

- `index.php` — dashboard dengan ringkasan kursus dan progres dari database.
- `login.php`, `logout.php` — autentikasi.
- `courses.php`, `course.php` — CRUD kursus/materi, pendaftaran, progres.
- `schedule.php` — pengelolaan sesi kelas (admin/guru) dan agenda terdaftar
  (murid).
- `messages.php` — pesan privat sesuai relasi murid, guru, dan admin.
- `users.php` — pengelolaan akun oleh admin.
- `settings.php` — identitas platform dan gambar QRIS privat (admin).
- `finance.php` — transaksi manual dan review pembayaran (admin/murid).
- `reports.php` — laporan keuangan dan kursus, termasuk ekspor CSV (admin).
- `app/bootstrap.php` — session dan bootstrap aplikasi.
- `app/database.php`, `database/schema.sql` — koneksi SQLite dan skema.
- `app/security.php`, `app/lms.php` — helper keamanan dan operasi LMS.
- `app/setup.php` — inisialisasi database; hanya dapat berjalan melalui CLI.
- `app/router.php` — router untuk PHP built-in development server.
- `views/` — template halaman.
- `assets/` — CSS, JavaScript, dan logo.

## Keamanan dan batasan

Aplikasi menggunakan password hashing, prepared statements, pengecekan
otorisasi di server, session yang diregenerasi saat login, cookie session
`HttpOnly`/`SameSite`, dan token CSRF pada formulir perubahan data. Akun
dinonaktifkan alih-alih dihapus permanen agar riwayat tetap terhubung.

Sebelum deployment: aktifkan HTTPS, ganti/nonaktifkan seluruh akun demo, jangan
commit atau mengunggah database berisi data pengguna, backup database secara
teratur, dan pastikan direktori internal tidak dapat diakses lewat URL. Terapkan
backup juga sebelum update aplikasi.

Gateway pembayaran otomatis, unggah bukti transfer murid, ujian/tugas,
kelas live terintegrasi/berulang, reset password melalui email, dan manajemen
sesi administrator belum tersedia.
