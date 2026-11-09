# Panduan Proyek SMK TPI Al-Ghoniyyah

Stack: PHP Native, MySQL/MariaDB (XAMPP), Bootstrap.

## 1. Lokasi proyek
Ekstrak folder proyek ke:
`C:\xampp\htdocs\smk-tpi-alghoniyyah`

## 2. Database
1. Jalankan Apache dan MySQL dari XAMPP.
2. Buka phpMyAdmin.
3. Pilih tab Import.
4. Import `database/schema.sql`.
5. Schema akan membuat database `db_smk_tpi_alghoniyyah` beserta tabel dan master data awal.

Konfigurasi lokal XAMPP yang umum:
- Host: `127.0.0.1`
- Port: `3306`
- Username: `root`
- Password: kosong (sesuaikan jika instalasi lokal Anda berbeda).

## 3. Dua jalur login, lima role
- Jalur `/manajemen/login`: Superadmin, Admin, Guru.
- Jalur `/portal/login`: Siswa dan Alumni.

Keduanya menggunakan tabel autentikasi yang sama (`users` dan `user_roles`), tetapi controller harus memvalidasi role yang diizinkan pada jalur tersebut. Jangan menerima role dari form login sebagai sumber kebenaran; role wajib dibaca dari database.

## 4. Pembuatan akun awal
Schema tidak membuat akun Superadmin dengan password bawaan. Setelah modul autentikasi dibuat, sediakan proses instalasi satu kali yang membuat password menggunakan PHP `password_hash()`. Jangan menyimpan password plaintext atau meng-hardcode password default.

## 5. Penyimpanan dokumen
Dokumen PPDB, akademik, dan alumni bersifat privat dan disimpan di `storage/private/`, di luar folder publik. Unduhan harus melalui controller yang memeriksa session dan izin pengguna.

## 6. Catatan
Schema ini adalah baseline pengembangan. Uji pada versi MySQL/MariaDB yang terpasang di XAMPP sebelum dipakai, lalu lakukan backup sebelum migrasi/perubahan struktur.


PENGEMBANGAN SISTEM INFORMASI SEKOLAH SMK TPI AL-GHONIYYAH

**Versi Progres: 6 November 2026**

## 1. IDENTITAS DAN TUJUAN PROYEK

Saya sedang mengembangkan website dan sistem informasi sekolah untuk **SMK TPI Al-Ghoniyyah**.

Proyek ini menggabungkan dua bagian utama:

1. **Company Profile:** Website publik yang menampilkan informasi, identitas, kegiatan, prestasi, jurusan, dan penerimaan peserta didik baru.
2. **Portal Internal Sekolah:** Sistem manajemen dan layanan akademik yang digunakan oleh Superadmin, Admin, Guru, Siswa, dan Alumni.

Tujuan proyek adalah membangun sistem informasi sekolah yang profesional, modern, mudah digunakan, terstruktur, aman, dan dapat dikembangkan secara bertahap.

Sistem harus memiliki fondasi yang benar, bukan hanya tampilan website.

---

## 2. REFERENSI DAN ACUAN DESAIN

Referensi website:
1. https://www.smkalmadanigarut.sch.id
2. https://sekolah.jatma.web.id/

Saya juga memiliki desain Figma sebagai acuan utama.

Ketentuan:
- Navbar Company Profile harus mengikuti desain Figma yang sudah tersedia.
- Jangan mengubah desain navbar berdasarkan asumsi sendiri.
- Company Profile dan Portal Internal merupakan bagian yang saling terintegrasi.
- Gunakan referensi sebagai acuan, bukan menyalin seluruh tampilannya.
- Utamakan desain profesional, konsisten, responsif, dan mudah digunakan.
- Desain Portal Internal menggunakan identitas visual sekolah, dengan warna hijau sebagai warna utama.

---

## 3. STRUKTUR COMPANY PROFILE

Halaman yang sudah disepakati:

1. Beranda
2. Profil Sekolah
3. Sejarah Sekolah
4. Jumlah Pendidik
5. Jumlah Siswa
6. Prestasi Siswa
7. Kegiatan Sekolah
8. Jurusan
9. PPDB (Penerimaan Peserta Didik Baru)

Statistik di halaman Beranda hanya:
- Jumlah pendidik.
- Jumlah siswa.

Jangan menambahkan statistik lain tanpa persetujuan.

---

## 4. STRUKTUR PORTAL INTERNAL

Portal Internal memiliki lima jenis pengguna:

1. Superadmin
2. Admin
3. Guru
4. Siswa
5. Alumni

### A. Pembagian jalur login

**Login Manajemen Sekolah**
- Superadmin
- Admin
- Guru

File:
`public/login-manajemen.php`

**Login Siswa dan Alumni**
- Siswa
- Alumni

Halaman login siswa dan alumni belum selesai dibuat.

### B. Pembagian hak akses

**Superadmin**
- Mengelola akun Admin.
- Mengelola data Guru, Siswa, dan Alumni pada tingkat administrasi sistem.
- Mengelola profil sekolah.
- Mengelola pengaturan sistem.
- Memiliki kewenangan tertinggi dalam Portal Internal.

**Admin**
- Mengelola operasional sekolah.
- Mengelola data Guru dan Siswa.
- Mengelola data Alumni sesuai kebutuhan administrasi.
- Mengelola tahun ajaran.
- Mengelola kelas.
- Mengelola mata pelajaran.
- Mengelola pembagian kelas.
- Mengelola PPDB.
- Mengelola administrasi akademik.

**Guru**
- Mengakses data dan layanan yang berkaitan dengan tugas mengajar.
- Mengelola materi, tugas, nilai, absensi, dan aktivitas pembelajaran sesuai kewenangan.

**Siswa**
- Mengakses informasi akademik.
- Melihat jadwal, materi, tugas, nilai, absensi, dan informasi sekolah.

**Alumni**
- Mengakses layanan alumni.
- Mengelola informasi profil alumni.
- Mengakses informasi dan layanan yang disediakan sekolah.

Siswa dan Alumni menggunakan jalur login yang sama, tetapi dashboard dan hak akses harus dibedakan berdasarkan role.

Setiap pengguna hanya boleh mengakses fitur sesuai kewenangannya.

---

## 5. TEKNOLOGI YANG DIGUNAKAN

Teknologi:
- PHP Native.
- MySQL / MariaDB.
- PDO.
- HTML.
- CSS.
- JavaScript.
- XAMPP.
- Visual Studio Code.
- phpMyAdmin.

Database:

`db_smk_tpi_alghoniyyah`

Konfigurasi lokal:
- Host: localhost
- Username: root
- Password: kosong
- Character set: utf8mb4

Koneksi database menggunakan class `Database` di:

`config/database.php`

Kode koneksi yang sudah berhasil digunakan:

```php
<?php

class Database
{
    private $host = "localhost";
    private $dbname = "db_smk_tpi_alghoniyyah";
    private $username = "root";
    private $password = "";

    public function connect()
    {
        try {
            $connection = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password
            );

            $connection->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            $connection->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );

            return $connection;

        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }
}
```

---

## 6. STRUKTUR DATABASE

Database awal memiliki 55 tabel. Setelah dievaluasi, disepakati untuk menyederhanakan fondasi menjadi 10 tabel inti:

1. `roles`
2. `users`
3. `school_profile`
4. `teachers`
5. `students`
6. `alumni`
7. `academic_years`
8. `classes`
9. `subjects`
10. `class_enrollments`

Prinsip database:
- Gunakan struktur sederhana dan mudah dipahami.
- Hindari tabel yang tidak diperlukan.
- Pertahankan integritas foreign key.
- Jangan membuat seluruh tabel fitur sekaligus.
- Tambahkan tabel sesuai kebutuhan modul.
- Jangan mengubah nama kolom atau tabel tanpa memeriksa dampaknya.

### A. Tabel roles

Kolom:
- `id`
- `code`
- `name`
- `created_at`

Role yang sudah tersedia:

| id | code |
|---|---|
| 1 | superadmin |
| 2 | admin |
| 3 | guru |
| 4 | siswa |
| 5 | alumni |

### B. Tabel users

Kolom yang sudah dikonfirmasi:

- `id`
- `role_id`
- `full_name`
- `username`
- `email`
- `phone`
- `password_hash`
- `status`
- `created_at`

Kemungkinan terdapat `updated_at`, tetapi jangan mengasumsikannya tanpa pemeriksaan.

Controller login juga menggunakan `last_login_at`. Pastikan kolom tersebut tersedia sebelum mengubah atau menjalankan query terkait.

### C. Tabel teachers

Kolom:

- `id`
- `user_id`
- `employee_number`
- `gender`
- `birth_place`
- `birth_date`
- `address`
- `employment_status`
- `created_at`
- `updated_at`

### D. Tabel students

Kolom:

- `id`
- `user_id`
- `nis`
- `nisn`
- `nik`
- `gender`
- `birth_place`
- `birth_date`
- `religion`
- `address`
- `parent_name`
- `parent_phone`
- `admission_year`
- `student_status`
- `created_at`
- `updated_at`

Ketentuan:
- `religion` menggunakan VARCHAR(40), nullable.
- `student_status` menggunakan ENUM:
  - active
  - graduated
  - transferred
  - inactive
- Default `student_status` adalah `active`.
- Nilai ENUM `gender` harus diperiksa sebelum membuat form.

### E. Tabel alumni

Kolom:

- `id`
- `user_id`
- `student_id`
- `graduation_year`
- `major_name`
- `verification_status`
- `created_at`
- `updated_at`

`verification_status` menggunakan ENUM:
- pending
- verified
- rejected

Default: `pending`.

### F. Tabel school_profile

Kolom:

- `id`
- `school_name`
- `npsn`
- `address`
- `postal_code`
- `phone`
- `email`
- `logo`
- `history`
- `vision`
- `mission`
- `created_at`
- `updated_at`

### G. Tabel lainnya

`academic_years`, `classes`, `subjects`, dan `class_enrollments` sudah masuk dalam rancangan fondasi.

Struktur kolom lengkap tabel tersebut harus diperiksa sebelum membuat fitur yang menggunakannya.

**Penting:** Jangan mengasumsikan struktur database hanya dari nama tabel.

---

## 7. LOKASI DAN STRUKTUR FOLDER PROYEK

Lokasi XAMPP yang benar:

`C:\xampp1\`

Lokasi proyek:

`C:\xampp1\htdocs\smk-tpi-alghoniyyah\`

Jangan menggunakan `C:\xampp` karena instalasi yang digunakan adalah `C:\xampp1`.

Gambaran struktur proyek saat ini:

```text
smk-tpi-alghoniyyah/
│
├── app/
│   ├── Controllers/
│   │   ├── Academic/
│   │   ├── Alumni/
│   │   ├── Auth/
│   │   │   └── AuthController.php
│   │   ├── Company/
│   │   └── PPDB/
│   │
│   ├── Core/
│   │   └── Session.php
│   │
│   ├── Helpers/
│   │
│   ├── Middleware/
│   │   └── AuthMiddleware.php
│   │
│   ├── Models/
│   └── Services/
│
├── config/
│   └── database.php
│
├── database/
│   └── schema.sql
│
├── docs/
│
├── public/
│   ├── index.php
│   ├── login-manajemen.php
│   ├── logout.php
│   │
│   └── dashboard/
│       ├── superadmin/
│       │   ├── index.php
│       │   ├── admin/
│       │   │   └── index.php
│       │   ├── guru/
│       │   │   └── index.php
│       │   ├── siswa/
│       │   │   └── index.php
│       │   ├── alumni/
│       │   │   └── index.php
│       │   ├── profil-sekolah/
│       │   │   └── index.php
│       │   └── pengaturan/
│       │       └── index.php
│       │
│       └── admin/
│           └── index.php
│
├── resources/
├── routes/
└── storage/
```

Struktur tersebut adalah gambaran berdasarkan progres. Jika terdapat folder atau file tambahan, pertahankan struktur aktual dan jangan membuat duplikat.

---

## 8. PROGRES PENGERJAAN TERBARU

### A. Persiapan proyek

- [x] Menentukan nama proyek.
- [x] Menentukan referensi website.
- [x] Menentukan Company Profile dan Portal Internal.
- [x] Menentukan halaman Company Profile.
- [x] Menentukan pembagian role dan jalur login.
- [x] Menyederhanakan rancangan database.
- [x] Membuat struktur folder proyek.
- [x] Menyiapkan database MySQL.

### B. Koneksi database

- [x] Membuat `config/database.php`.
- [x] Menggunakan PDO.
- [x] Menghubungkan PHP dengan MySQL.
- [x] Menguji koneksi database dan berhasil.

### C. Sistem Session

- [x] Membuat `app/Core/Session.php`.
- [x] Membuat fungsi start session.
- [x] Membuat penyimpanan data pengguna.
- [x] Membuat pemeriksaan login.
- [x] Membuat pemeriksaan role.
- [x] Membuat fungsi logout.
- [x] Menguji session dan berhasil.

### D. Middleware

- [x] Membuat `app/Middleware/AuthMiddleware.php`.
- [x] Membuat pemeriksaan login.
- [x] Membuat pembatasan akses berdasarkan role.
- [x] Membuat pemeriksaan guest.
- [x] Menguji middleware menggunakan simulasi Superadmin.
- [x] Pengujian awal middleware berhasil.

### E. Sistem autentikasi

- [x] Membuat `app/Controllers/Auth/AuthController.php`.
- [x] Menggunakan PDO untuk pencarian akun.
- [x] Menggunakan `password_verify()`.
- [x] Memeriksa status akun.
- [x] Memeriksa kesesuaian role dengan jalur login.
- [x] Menyimpan informasi pengguna ke session.
- [x] Menyiapkan redirect dashboard berdasarkan role.
- [x] Membuat fungsi logout.

### F. Login Manajemen Sekolah

File:

`public/login-manajemen.php`

- [x] Membuat tampilan login.
- [x] Membuat input username/email.
- [x] Membuat input password.
- [x] Membuat fitur lihat/sembunyikan password.
- [x] Menghubungkan halaman dengan AuthController.
- [x] Mengatur pengalihan pengguna berdasarkan role.

### G. Dashboard Superadmin

File:

`public/dashboard/superadmin/index.php`

- [x] Membuat dashboard Superadmin.
- [x] Membuat sidebar navigasi.
- [x] Membuat topbar pengguna.
- [x] Membuat statistik total pengguna.
- [x] Membuat statistik Admin, Guru, Siswa, dan Alumni.
- [x] Mengambil data statistik dari database.
- [x] Membuat navigasi ke halaman manajemen.

Desain:
- Sidebar hijau tua `#07543f`.
- Background abu-abu muda `#f4f6f5`.
- Kartu statistik berwarna putih.
- Layout sederhana dan responsif.

### H. Manajemen Data Admin

File:

`public/dashboard/superadmin/admin/index.php`

Fitur:
- [x] Menambah akun Admin.
- [x] Menyimpan data ke tabel `users` dengan role admin.
- [x] Menampilkan daftar Admin.
- [x] Mengedit informasi Admin.
- [x] Mengaktifkan dan menonaktifkan akun.
- [x] Menghapus akun Admin.

Pengujian:
- [x] User mengonfirmasi fitur berjalan dengan baik.

### I. Manajemen Data Guru

File:

`public/dashboard/superadmin/guru/index.php`

Fitur:
- [x] Membuat akun Guru.
- [x] Menyimpan data ke `users` dan `teachers`.
- [x] Menggunakan transaksi database.
- [x] Menampilkan daftar Guru.
- [x] Mengedit data Guru.
- [x] Mengaktifkan dan menonaktifkan akun.
- [x] Menghapus data Guru dan akun terkait.

Pengujian:
- [x] User mengonfirmasi fitur berjalan setelah perbaikan pada proses edit.

### J. Manajemen Data Siswa

File:

`public/dashboard/superadmin/siswa/index.php`

Fitur yang sudah dibuat dalam kode:
- [x] Form tambah Siswa.
- [x] Pembuatan akun pada tabel `users`.
- [x] Penyimpanan profil pada tabel `students`.
- [x] Menampilkan daftar Siswa.
- [x] Form edit.
- [x] Pengaturan status akun.
- [x] Pengaturan status siswa.
- [x] Penghapusan akun dan data siswa.

Catatan:
- Kode sudah diberikan, tetapi belum dikonfirmasi berhasil diuji.
- Periksa kembali nilai ENUM gender dan kesesuaian query saat pengujian.

### K. Manajemen Data Alumni

File:

`public/dashboard/superadmin/alumni/index.php`

Fitur yang sudah dibuat dalam kode:
- [x] Membuat akun Alumni.
- [x] Menyimpan data ke `users` dan `alumni`.
- [x] Menampilkan daftar Alumni.
- [x] Mengedit data Alumni.
- [x] Mengubah status verifikasi.
- [x] Mengaktifkan dan menonaktifkan akun.
- [x] Menghapus data Alumni dan akun terkait.

Catatan:
- Kode sudah diberikan, tetapi belum dikonfirmasi berhasil diuji.
- Pemeriksaan pemanggilan AuthMiddleware masih perlu dilakukan.
- Pastikan penghapusan tidak melanggar foreign key.

### L. Profil Sekolah

File:

`public/dashboard/superadmin/profil-sekolah/index.php`

Fitur yang sudah dibuat:
- [x] Mengambil profil sekolah dari database.
- [x] Membuat data awal jika belum tersedia.
- [x] Mengedit nama sekolah.
- [x] Mengedit NPSN.
- [x] Mengedit alamat.
- [x] Mengedit kode pos.
- [x] Mengedit nomor telepon.
- [x] Mengedit email.
- [x] Mengunggah logo.
- [x] Mengelola sejarah sekolah.
- [x] Mengelola visi dan misi.

Catatan:
- Upload logo memiliki validasi MIME dan batas ukuran 2 MB.
- File disimpan di `public/uploads/school/`.
- Kode belum dikonfirmasi berhasil diuji.

### M. Pengaturan Sistem

File:

`public/dashboard/superadmin/pengaturan/index.php`

Tampilan yang sudah dibuat:
- [x] Identitas aplikasi.
- [x] Nama sekolah.
- [x] Pengaturan tema.
- [x] Pengaturan bahasa.
- [x] Informasi registrasi dan login.
- [x] Informasi mode pemeliharaan.
- [x] Informasi sistem.
- [x] Tampilan backup.

Catatan:
- Halaman masih berupa antarmuka.
- Belum terhubung dengan tabel pengaturan database.
- Beberapa tombol masih nonaktif.
- Jangan menganggap fitur pengaturan sudah berfungsi secara nyata.

### N. Dashboard Admin

File:

`public/dashboard/admin/index.php`

- [x] Membuat halaman Dashboard Admin.
- [x] Memisahkan dashboard Admin dari Superadmin.
- [x] Menggunakan desain yang konsisten dengan Dashboard Superadmin.
- [x] Menambahkan statistik pengguna, Guru, Siswa, dan Alumni.
- [x] Menambahkan navigasi manajemen sekolah.
- [x] Menambahkan navigasi akademik.
- [x] Menambahkan navigasi PPDB.
- [x] Menambahkan tombol Logout.

Menu yang ditampilkan:
- Dashboard.
- Data Guru.
- Data Siswa.
- Data Alumni.
- Tahun Ajaran.
- Data Kelas.
- Mata Pelajaran.
- Pembagian Kelas.
- Manajemen PPDB.
- Logout.

Catatan:
- Halaman sudah dituliskan, tetapi belum dikonfirmasi berhasil dibuka.
- Folder dan halaman menu akademik belum semuanya dibuat.
- Pastikan seluruh query menggunakan kolom yang benar.
- Statistik Admin saat ini menghitung semua akun dengan role admin, termasuk akun yang mungkin tidak aktif.

---

## 9. KONDISI AUTENTIKASI TERAKHIR

Ini bagian penting yang harus dipahami ketika melanjutkan proyek.

### A. AuthController.php

Lokasi:

`app/Controllers/Auth/AuthController.php`

Controller sudah menggunakan:

- `Session::start()`
- Pencarian akun berdasarkan username/email.
- `password_verify()`.
- Pemeriksaan status active.
- Pemeriksaan role berdasarkan `$allowedRoles`.
- `Session::setUser()`.
- Update `last_login_at`.
- Redirect berdasarkan role.

Pemetaan dashboard:

```php
$dashboards = [
    'superadmin' => 'superadmin',
    'admin'      => 'admin',
    'guru'       => 'guru'
];
```

Redirect:

```php
'redirect' => '/smk-tpi-alghoniyyah/public/dashboard/'
    . $dashboards[$user['role']] . '/index.php'
```

### B. login-manajemen.php

Lokasi:

`public/login-manajemen.php`

Halaman login sudah memeriksa session aktif.

Jika pengguna sudah login, halaman akan mengarahkan ke dashboard berdasarkan role session.

Pemetaan role:

```php
$dashboards = [
    'superadmin' => 'superadmin',
    'admin'      => 'admin',
    'guru'       => 'guru'
];
```

Jika belum login, pengguna dapat masuk menggunakan username/email dan password.

Role yang diizinkan:

```php
['superadmin', 'admin', 'guru']
```

### C. Data akun terakhir yang diperiksa

Hasil query database:

```sql
SELECT
    users.id,
    users.full_name,
    users.username,
    users.email,
    users.status,
    roles.code AS role
FROM users
INNER JOIN roles ON users.role_id = roles.id;
```

Hasil:

| id | full_name | username | email | status | role |
|---|---|---|---|---|---|
| 1 | Superadmin | superadmin | NULL | active | superadmin |
| 3 | Muhammad Padila | Pad | padilamuhammad@gmail.com | active | admin |

Artinya:
- Akun Superadmin memiliki role `superadmin`.
- Akun Muhammad Padila memiliki role `admin`.
- Kedua akun berstatus `active`.
- Pembagian role pada database sudah benar.

### D. Masalah terakhir

User melaporkan bahwa ketika membuka halaman Login Manajemen Sekolah, halaman malah langsung masuk ke Dashboard Superadmin.

Kemungkinan yang ditemukan:
- Session Superadmin sebelumnya masih aktif.
- `login-manajemen.php` mendeteksi session aktif.
- Karena session masih berisi role `superadmin`, pengguna langsung diarahkan ke Dashboard Superadmin.

Langkah yang sudah disarankan:
1. Buka `http://localhost/smk-tpi-alghoniyyah/public/logout.php`.
2. Pastikan session sebelumnya sudah berakhir.
3. Buka kembali `http://localhost/smk-tpi-alghoniyyah/public/login-manajemen.php`.
4. Login menggunakan username `Pad` dan password akun Admin.
5. Seharusnya masuk ke `dashboard/admin/index.php`.

**Status masalah: Belum dikonfirmasi selesai.**

Jangan langsung mengubah AuthController atau login-manajemen.php sebelum menguji logout dan login ulang. Jika masalah masih terjadi, periksa implementasi Session, logout, dan pengelolaan session.

---

## 10. URUTAN PENGEMBANGAN SELANJUTNYA

Tahap autentikasi dan Dashboard Superadmin sudah memiliki fondasi. Lanjutkan dari progres terakhir, bukan mengulang dari awal.

### Tahap 1 — Menyelesaikan Dashboard Admin
- Memastikan login Admin masuk ke dashboard Admin.
- Memastikan akses dashboard dibatasi untuk role admin.
- Memeriksa statistik dashboard.
- Memastikan menu navigasi sesuai struktur folder.

### Tahap 2 — Manajemen Akademik
Buat secara bertahap:
1. Manajemen Tahun Ajaran.
2. Manajemen Data Kelas.
3. Manajemen Mata Pelajaran.
4. Pembagian Siswa ke Kelas.

Sebelum membuat CRUD, periksa struktur tabel terkait.

### Tahap 3 — Dashboard Guru
- Membuat dashboard Guru.
- Menampilkan informasi Guru.
- Membuat menu mengajar.
- Mengelola jadwal.
- Mengelola materi.
- Mengelola tugas.
- Mengelola nilai.
- Mengelola absensi.

### Tahap 4 — Dashboard Siswa
- Membuat dashboard Siswa.
- Menampilkan profil.
- Menampilkan jadwal.
- Menampilkan materi.
- Menampilkan tugas.
- Menampilkan nilai.
- Menampilkan absensi.

### Tahap 5 — Dashboard Alumni
- Membuat dashboard Alumni.
- Menampilkan profil Alumni.
- Mengembangkan layanan Alumni.
- Mengembangkan verifikasi data Alumni.

### Tahap 6 — Login Siswa dan Alumni
- Membuat satu halaman login untuk Siswa dan Alumni.
- Menggunakan autentikasi yang sama.
- Mengarahkan pengguna sesuai role.
- Membatasi hak akses berdasarkan role.

### Tahap 7 — Company Profile
- Membuat halaman publik.
- Mengintegrasikan data profil sekolah.
- Mengintegrasikan statistik pendidik dan siswa.
- Membuat halaman sejarah, prestasi, kegiatan, jurusan, dan PPDB.
- Mempertahankan desain navbar sesuai Figma.

### Tahap 8 — Modul PPDB
- Membuat pendaftaran calon siswa.
- Mengelola data pendaftar.
- Mengelola verifikasi.
- Mengelola hasil seleksi.
- Mengintegrasikan hasil penerimaan dengan data siswa.

### Tahap 9 — Modul Akademik Lanjutan
- Jadwal pelajaran.
- Materi.
- Tugas.
- Nilai.
- Absensi.
- Laporan akademik.

### Tahap 10 — Pengujian dan Penyempurnaan
- Menguji seluruh role.
- Menguji pembatasan akses.
- Menguji CRUD.
- Menguji relasi database.
- Menguji validasi input.
- Menguji keamanan session.
- Menguji responsivitas.
- Menguji integrasi Company Profile dan Portal Internal.

---

## 11. ATURAN WAJIB SELAMA PENGEMBANGAN

1. Jangan mengubah struktur folder tanpa alasan yang jelas.
2. Jangan membuat file duplikat.
3. Periksa file yang sudah ada sebelum membuat file baru.
4. Gunakan nama tabel dan kolom sesuai database aktual.
5. Jangan mengasumsikan struktur database.
6. Jangan mengubah kode yang sudah berjalan jika tidak diperlukan.
7. Jika muncul error, cari penyebabnya terlebih dahulu.
8. Jelaskan error dengan bahasa Indonesia yang mudah dipahami.
9. Sebutkan lokasi penyimpanan file secara jelas.
10. Berikan kode lengkap ketika membuat file baru.
11. Jika memperbaiki kode, prioritaskan perubahan sekecil mungkin.
12. Jangan memberikan terlalu banyak tahapan sekaligus.
13. Kerjakan satu tahap, kemudian lanjutkan sesuai kesepakatan.
14. Gunakan PHP Native dan PDO.
15. Utamakan prepared statements, password hashing, session protection, validasi akses, dan keamanan upload.
16. Jangan mengarang hasil pengujian.
17. Jika membutuhkan kode atau screenshot, minta secara spesifik.
18. Pertahankan keputusan desain dan kebutuhan yang sudah disepakati.
19. Jangan menghilangkan kebutuhan fitur utama hanya demi menyederhanakan sistem.
20. Dokumentasikan perubahan penting.
21. Jangan menganggap kode yang baru diberikan sudah berhasil diuji sebelum user mengonfirmasi.
22. Jangan meminta user mengulang informasi yang sudah tersedia dalam konteks proyek.
23. Jangan langsung membuat seluruh modul sekaligus.
24. Pastikan role Superadmin, Admin, Guru, Siswa, dan Alumni benar-benar terpisah hak aksesnya.
25. Perhatikan path relatif dan absolut berdasarkan lokasi file aktual.

---

## 12. GAYA PENDAMPINGAN

Saya masih belajar dalam pengembangan web.

Oleh karena itu:
- Gunakan bahasa Indonesia yang mudah dipahami.
- Berikan instruksi langkah demi langkah.
- Jelaskan fungsi kode secukupnya.
- Beritahu file harus disimpan di folder mana.
- Jangan menganggap saya sudah memahami semua konsep pemrograman.
- Bantu saya memahami prosesnya, bukan hanya memberikan kode.
- Jika saya meminta perbaikan, jangan mengubah seluruh kode tanpa alasan.
- Jika ada beberapa kemungkinan penyebab error, periksa satu per satu.
- Tunggu konfirmasi saya sebelum berpindah ke tahap berikutnya jika diperlukan.

Saya lebih nyaman mengembangkan sistem secara bertahap, dengan satu modul dikerjakan sampai jelas sebelum melanjutkan ke modul berikutnya.

---

## 13. INSTRUKSI UNTUK MELANJUTKAN PROYEK

Ketika prompt ini digunakan dalam percakapan baru:

1. Baca seluruh konteks dan progres proyek.
2. Jangan memulai dari awal.
3. Jangan mengulang pembuatan koneksi database, session, middleware, atau dashboard yang sudah dibuat.
4. Pertahankan struktur folder dan keputusan sebelumnya.
5. Perhatikan masalah terakhir yang belum dikonfirmasi selesai.

**TUGAS PERTAMA:**

Lanjutkan dari pembangunan Dashboard Admin Sekolah.

Prioritas pertama:
- Konfirmasi apakah login menggunakan akun Admin sudah berhasil masuk ke Dashboard Admin.
- Jika belum, selesaikan masalah session dan redirect terlebih dahulu.
- Jika sudah, lanjutkan memeriksa Dashboard Admin yang baru dibuat.
- Setelah itu mulai membangun modul Manajemen Tahun Ajaran.

Jangan langsung membuat modul Tahun Ajaran sebelum memastikan struktur tabel `academic_years` yang sebenarnya.

Target akhir adalah menghasilkan sistem informasi sekolah SMK TPI Al-Ghoniyyah yang terintegrasi, profesional, aman, mudah digunakan, dan dapat digunakan secara nyata.

