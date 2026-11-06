# Rancangan Autentikasi

## Dua halaman login
1. `/manajemen/login`: hanya menerima role `superadmin`, `admin`, dan `guru`.
2. `/portal/login`: hanya menerima role `siswa` dan `alumni`.

Kedua jalur menggunakan tabel `users`, `roles`, dan `user_roles` yang sama. Setelah kredensial valid, aplikasi membaca role pengguna dari database lalu mengarahkan ke dashboard role yang sesuai.

## Pembuatan akun
- Superadmin: dibuat melalui proses instalasi awal yang hanya dapat dijalankan sekali.
- Admin: dibuat oleh Superadmin.
- Guru dan Siswa: dibuat oleh Admin.
- Alumni: registrasi mandiri dengan status menunggu verifikasi Admin.

## Aturan keamanan
- Password wajib di-hash dengan `password_hash()` dan diverifikasi dengan `password_verify()`.
- Regenerasi session ID setelah login.
- Periksa session dan permission di setiap request, bukan hanya menyembunyikan menu.
- Cegah akses lintas jalur login meskipun pengguna mencoba URL dashboard secara langsung.
- Terapkan CSRF token pada formulir yang mengubah data.
- Dokumen privat diunduh melalui controller dengan pemeriksaan kepemilikan dan izin.
