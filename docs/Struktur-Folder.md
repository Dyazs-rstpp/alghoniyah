# Struktur Folder

```text
smk-tpi-alghoniyyah/
├── app/
│   ├── Core/                 # Database, router, session
│   ├── Controllers/          # Proses tiap modul
│   ├── Models/               # Query dan akses data
│   ├── Middleware/            # Pemeriksaan login dan role
│   ├── Services/               # Logika bisnis lintas modul
│   └── Helpers/                # Fungsi bantuan
├── config/                    # Konfigurasi aplikasi/database
├── database/
│   ├── schema.sql             # Struktur dan master data awal
│   └── seeders/                # Data awal tambahan
├── public/                    # Document root aplikasi
│   ├── index.php
│   └── assets/
├── resources/views/
│   ├── company/                # Company Profile
│   ├── auth/                   # Dua tampilan login
│   ├── dashboards/             # Lima dashboard role
│   ├── ppdb/
│   ├── academic/
│   └── alumni/
├── routes/                     # Definisi URL
└── storage/
    ├── private/                # Dokumen privat, jangan diekspos URL
    ├── exports/
    └── logs/
```

## Catatan deployment lokal
Untuk tahap awal XAMPP, arahkan VirtualHost/DocumentRoot ke folder `public/` bila memungkinkan. Jika belum menggunakan VirtualHost, atur konfigurasi Apache agar hanya `public/` yang dapat diakses melalui web. Folder `app`, `config`, `database`, `resources`, dan `storage` tidak boleh dapat diakses langsung dari browser.
