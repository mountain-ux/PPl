# TAMUIN — Sistem Buku Tamu & Absensi Event (Event Check-in Kiosk PWA)

**TAMUIN** adalah aplikasi berbasis *Progressive Web App* (PWA) dengan arsitektur *offline-first* yang dirancang untuk menggantikan pencatatan buku tamu manual pada berbagai jenis acara (seminar, wisuda, pernikahan, maupun konferensi). Sistem ini memungkinkan tamu mendaftar secara mandiri untuk memperoleh tiket QR Code unik bertanda tangan kriptografi, serta memungkinkan petugas di pintu masuk memvalidasi dan mencatat kehadiran tamu secara instan melalui perangkat *kiosk* meskipun tanpa koneksi internet sama sekali.

Proyek ini dikembangkan menggunakan **Laravel** dan **Tailwind CSS** sebagai bagian dari tugas mata kuliah **Proyek Perangkat Lunak** pada Program Studi Informatika, **Universitas Sulawesi Barat**.

## Identitas Proyek & Tim Pengembang (Kelompok 1)

| Komponen | Keterangan |
| :--- | :--- |
| **Nama Proyek** | TAMUIN — Buku Tamu & Absensi Event (*Event Check-in Kiosk*) |
| **Institusi** | Universitas Sulawesi Barat (Unsulbar) |
| **Program Studi / Kelas** | Informatika / C2023 |
| **Mata Kuliah** | Proyek Perangkat Lunak |
| **Dosen Pengampu** | Mawardi Kudin, S.Pd., M.Kom |

### Susunan Tim & Peran

| No | Nama Anggota | NIM | Peran dalam Proyek |
| :--- | :--- | :--- | :--- |
| 1 | **Ahmad Khanif Izzah Arifin** | D0223511 | *Project Manager* & *System Analyst* |
| 2 | **Aldi Salam** | D0224043 | *System Designer* |
| 3 | **Ade Evi** | D0223043 | *UI/UX Designer* |
| 4 | **Hasnur** | D0223509 | *Database Designer* & *Backend Developer* |
| 5 | **Muh. Ali Sadikin** | D0223332 | *Frontend Developer* |
| 6 | **Muh. Alif Satria** | D0223517 | *Tester / Quality Assurance (QA)* |

## Fitur Utama Sistem

1. **Manajemen Akun & Hak Akses Berbasis Peran (`Admin` & `Petugas Check-in`)**:
   - Autentikasi menggunakan *username* atau *email* beserta proteksi *rate limiting* dan pemeriksaan status aktif akun.
   - Admin dapat membuat, memperbarui, dan menonaktifkan akun Petugas Check-in.
   - Isolasi hak akses acara melalui `EventPolicy`: Admin dapat mengakses seluruh acara, sedangkan Petugas hanya dapat mengelola acara yang dibuatnya sendiri.
2. **Manajemen Multi-Event & Konfigurasi Formulir Dinamis**:
   - Mendukung pengelolaan banyak acara secara terpisah dalam satu sistem.
   - Pembuat acara dapat mengatur kolom tambahan (*custom fields*) pada formulir pendaftaran tamu hingga maksimal 10 kolom (teks singkat, angka, pilihan *dropdown*, atau teks panjang) yang disimpan dalam format JSON.
3. **Registrasi Mandiri Tamu (RSVP Publik) & Tiket QR Code Bertanda Tangan**:
   - Halaman publik khusus tamu tanpa perlu memiliki akun atau melakukan login.
   - Pembuatan kode tiket alfanumerik (`TMN-YYYY-XXXXX`) dan *payload* QR Code unik per tamu per acara yang dilengkapi tanda tangan **HMAC-SHA256** untuk mencegah pemalsuan tiket.
   - Fitur unduh gambar QR Code (`.PNG`), kirim salinan tiket ke WhatsApp, serta fitur **Cari / Kirim Ulang Tiket QR** menggunakan nomor telepon atau email terdaftar.
4. **Pengelolaan Data Tamu Manual & Impor Massal (`.xlsx` / `.csv`)**:
   - Penambahan, pengubahan, pencarian, penyaringan, dan penghapusan data tamu secara manual.
   - Fitur impor data tamu massal dari berkas Microsoft Excel (`.xlsx`) maupun CSV (`.csv`) yang dilengkapi unduhan *template* dinamis serta halaman **Pratinjau Validasi Data** (memisahkan baris valid dan baris bermasalah sebelum disimpan).
5. **Kiosk Check-in Offline-First (`IndexedDB` & `Service Worker`)**:
   - Pengunduhan seluruh daftar tamu acara aktif ke penyimpanan lokal browser (**IndexedDB**) sebelum acara berlangsung.
   - Pemindaian QR Code menggunakan kamera perangkat (`html5-qrcode`) maupun input kode tiket manual dengan waktu validasi lokal di bawah 2 detik tanpa memerlukan koneksi internet.
   - Deteksi otomatis apabila QR Code yang sama dipindai lebih dari satu kali (peringatan duplikasi *scan*).
   - Indikator status koneksi *real-time* (*Online* / *Offline*) beserta penghitung antrean data lokal yang belum tersinkronisasi.
6. **Sinkronisasi Otomatis/Manual & Pelaporan Kehadiran (`PDF` & `Excel`)**:
   - Pengiriman antrean data kehadiran dari *IndexedDB* ke server pusat MySQL secara otomatis saat koneksi internet kembali tersedia atau secara manual melalui tombol sinkronisasi, bebas dari duplikasi data menggunakan `local_uuid`.
   - Dashboard analitik *real-time* (statistik kehadiran, grafik distribusi waktu *check-in*, dan progres per kategori tamu).
   - Ekspor laporan akhir kehadiran ke dalam format **Microsoft Excel (`.xlsx`)** dan **PDF Siap Cetak**.

## Teknologi yang Digunakan

| Kategori | Teknologi / Library | Kegunaan |
| :--- | :--- | :--- |
| **Backend Framework** | Laravel 11 / 12 (PHP 8.2+) | Arsitektur MVC, REST API sinkronisasi, autentikasi, dan validasi server |
| **Database Server** | MySQL / MariaDB | Penyimpanan data terpusat (`users`, `events`, `guests`, `attendances`) |
| **Frontend & Styling** | Blade, Tailwind CSS, JavaScript (ES6+) | Antarmuka responsif (*mobile-first*) untuk halaman tamu, kiosk, dan dashboard |
| **Teknologi Offline (PWA)** | Web App Manifest, Service Worker, IndexedDB | *Caching* aset aplikasi dan penyimpanan basis data lokal di perangkat *kiosk* |
| **Pemindai & Generator QR** | `html5-qrcode` & `qrcode.js` + HMAC-SHA256 | Pemindaian QR melalui kamera perangkat dan pembuatan gambar tiket QR |
| **Lingkungan Pengembangan** | Laragon | Server lokal pengembangan (Apache/Nginx, PHP, MySQL) |

## Struktur Basis Data Utama

Sistem menggunakan 4 tabel utama di MySQL sesuai rancangan *Entity Relationship Diagram* (ERD):

- **`users`**: Menyimpan kredensial dan peran pengguna (`id`, `name`, `username`, `email`, `password`, `role`, `is_active`).
- **`events`**: Menyimpan data acara dan konfigurasi formulir kustom (`id`, `name`, `event_date`, `event_time`, `location`, `description`, `form_fields` [JSON], `registration_slug`, `is_registration_open`, `created_by`).
- **`guests`**: Menyimpan data tamu per acara beserta kode tiket dan QR (`id`, `event_id`, `name`, `phone`, `email`, `custom_data` [JSON], `ticket_code`, `qr_code`, `source`, `registered_at`).
- **`attendances`**: Menyimpan catatan kehadiran hasil validasi *kiosk* (`id`, `guest_id` [Unique], `checked_in_by`, `checked_in_at`, `scan_method`, `device_id`, `local_uuid` [Unique], `sync_status`, `synced_at`).

## Panduan Instalasi & Konfigurasi di Laragon

1. **Persiapkan Proyek Laravel**:
   Pastikan **Laragon** (PHP >= 8.2, Composer, dan MySQL) sudah aktif, lalu buat atau buka direktori proyek di `C:\laragon\www\tamuin`:
   ```bash
   composer create-project laravel/laravel tamuin
   cd tamuin
   ```
2. **Pasang Kode Sumber TAMUIN**:
   Ekstrak seluruh isi paket kode sumber **TAMUIN** ke dalam direktori proyek `tamuin/` (pilih *Replace/Overwrite* saat diminta).
3. **Konfigurasi Berkas `.env`**:
   Salin `.env.example` menjadi `.env` (jika belum ada), buat database baru di MySQL bernama `tamuin_db`, lalu sesuaikan pengaturan berikut pada berkas `.env`:
   ```env
   APP_NAME="TAMUIN"
   APP_ENV=local
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tamuin_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```
4. **Jalankan Migrasi dan Seeder Data Awal**:
   Jalankan perintah berikut untuk membangun seluruh tabel basis data sekaligus mengisi akun bawaan dan sampel data acara:
   ```bash
   php artisan key:generate
   php artisan migrate:fresh --seed
   php artisan view:clear
   ```
5. **Jalankan Server Aplikasi**:
   ```bash
   php artisan serve
   ```
   Akses aplikasi melalui browser pada alamat `http://localhost:8000`.

## Akun Bawaan untuk Pengujian (Hasil Seeder)

| Peran | Username | Email | Kata Sandi | Cakupan Hak Akses |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin@unsulbar.ac.id` | `password123` | Mengakses seluruh acara, mengelola akun petugas, mengelola/impor tamu, memantau dashboard, dan mengekspor laporan PDF/Excel |
| **Petugas Check-in** | `petugas01` | `petugas01@unsulbar.ac.id` | `petugas123` | Mengelola acara yang dibuatnya sendiri, memilih acara aktif di kiosk, mengunduh data tamu lokal, memindai QR, dan menyinkronkan kehadiran |

**Sampel Kode Tiket Tamu untuk Uji Coba Kiosk / Pencarian QR:**
- `TMN-2026-88421` (Muhammad Rian Hidayat — Sudah Check-in)
- `TMN-2026-88425` (Putri Lestari, S.Kom., M.T. — Belum Check-in)
- `TMN-2026-88426` (Budi Santoso — Belum Check-in)

## Panduan Pengujian Alur Offline-First pada Kiosk

1. Masuk ke sistem menggunakan akun `admin` atau `petugas01`, lalu buka menu **Kiosk Check-in** (`/kiosk`).
2. Pilih acara aktif pada menu pilihan di bagian atas. Saat perangkat terhubung ke internet, sistem otomatis mengunduh daftar tamu acara tersebut ke penyimpanan lokal **IndexedDB**.
3. Buka tab **Sinkronisasi**, lalu klik tombol **Simulasi Offline** (atau matikan koneksi jaringan pada browser DevTools). Status pada bagian atas akan berubah menjadi **Offline**.
4. Kembali ke tab **Pemindai QR**, lalu pindai kode QR tamu menggunakan kamera atau masukkan kode tiket `TMN-2026-88425` pada kolom input manual. Sistem akan memvalidasi tiket secara instan dari *IndexedDB* tanpa koneksi internet dan mencatat kehadiran lokal.
5. Coba masukkan kembali kode `TMN-2026-88425` untuk menguji fitur **Deteksi Duplikasi Scan**.
6. Buka kembali tab **Sinkronisasi** dan klik tombol **Mode Online** (atau **Sinkronkan Data Sekarang**). Data kehadiran yang tertahan di perangkat lokal akan langsung dikirim dan digabungkan ke database MySQL server pusat.

## Daftar Rute Utama Aplikasi

| Modul | URL / Endpoint | Aktor | Deskripsi |
| :--- | :--- | :--- | :--- |
| **Publik Tamu** | `/e/{slug}` | Tamu Undangan | Halaman informasi acara (*Landing Page*) |
| **Publik Tamu** | `/e/{slug}/register` | Tamu Undangan | Formulir registrasi mandiri (RSVP) |
| **Publik Tamu** | `/e/{slug}/ticket/{ticketCode}` | Tamu Undangan | Halaman tiket digital & unduh QR Code |
| **Publik Tamu** | `/e/{slug}/find-qr` | Tamu Undangan | Pencarian & pengiriman ulang tiket QR |
| **Autentikasi** | `/login` | Admin, Petugas | Halaman masuk sistem |
| **Kiosk PWA** | `/kiosk` | Admin, Petugas | Antarmuka pemindai QR & validasi *offline* |
| **Kiosk API** | `/kiosk/api/events/{event}/guests` | Admin, Petugas | Endpoint pengunduhan data tamu ke *IndexedDB* |
| **Kiosk API** | `/kiosk/api/events/{event}/sync` | Admin, Petugas | Endpoint sinkronisasi kehadiran lokal ke server |
| **Acara & Form** | `/events` | Admin, Petugas | Pengelolaan data acara & konfigurasi *field* formulir |
| **Data Tamu** | `/events/{event}/guests` | Admin, Petugas | Pengelolaan data tamu manual & impor massal |
| **Dashboard** | `/admin/dashboard` | Admin | Dashboard pemantauan kehadiran *real-time* |
| **Laporan** | `/admin/reports` | Admin | Rekapitulasi & ekspor laporan kehadiran (PDF / Excel) |
| **Petugas** | `/admin/petugas` | Admin | Pengelolaan akun Petugas Check-in |
