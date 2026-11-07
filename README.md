# Alarm Minum Obat TBC: Backend dan Dashboard Petugas

Sistem pengingat minum obat untuk pasien TBC, dikembangkan untuk **RSUD Dr. R. Soedjati Soemodiardjo Purwodadi** sebagai bagian dari program KKN.

Repositori ini berisi **backend (Laravel) dan dashboard petugas (Filament)**. Aplikasi Android untuk pasien dikerjakan di repositori terpisah.

> **Status:** dalam pengembangan. Fase 1 (backend dan dashboard) hampir selesai. API dan aplikasi Android menyusul.

## Daftar isi

- [Latar belakang](#latar-belakang)
- [Fitur](#fitur)
- [Arsitektur](#arsitektur)
- [Teknologi](#teknologi)
- [Prasyarat](#prasyarat)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Peran pengguna](#peran-pengguna)
- [Alur kerja petugas](#alur-kerja-petugas)
- [Struktur proyek](#struktur-proyek)
- [Model data](#model-data)
- [API (rencana)](#api-rencana)
- [Pengujian manual](#pengujian-manual)
- [Keamanan dan privasi](#keamanan-dan-privasi)
- [Pemecahan masalah](#pemecahan-masalah)
- [Roadmap](#roadmap)
- [Tim dan kontak](#tim-dan-kontak)
- [Lisensi](#lisensi)

## Latar belakang

Putus obat adalah masalah utama dalam pengobatan TBC. Sistem ini membantu pasien (dan pendampingnya, umumnya keluarga) minum obat tepat waktu lewat alarm di HP, dan membantu petugas TB-DOTS memantau pasien yang berisiko putus obat.

Kondisi yang menjadi dasar rancangan:

- Pengguna umumnya orang tua atau pendamping pasien, dengan kuota data terbatas.
- Data pasien dan resep **berdiri sendiri**: diinput manual oleh petugas dan disimpan di database sendiri. Sistem ini tidak terhubung ke database SIMRS sama sekali (permintaan pihak rumah sakit).
- Login memakai **kode aktivasi dari petugas**, bukan OTP berbayar.
- Aplikasi **tidak menghitung dosis**. Dosis hanya teks yang disalin petugas dari resep dokter.

## Fitur

**Sudah tersedia (dashboard petugas)**

- Pendaftaran pasien dengan input manual (no. RM, nama, tempat dan tanggal lahir, jenis kelamin, no. HP, alamat) dan pencarian pasien berdasarkan nama atau no. RM.
- Pendaftaran pasien ke program, termasuk data PMO (Pengawas Menelan Obat).
- Pengelolaan resep obat (dosis berupa teks yang disalin dari resep dokter).
- Pembuatan jadwal minum harian otomatis dari tanggal mulai sampai tanggal selesai resep.
- Pembuatan **kode aktivasi** sekali pakai (berlaku 48 jam) untuk menghubungkan HP pasien.
- Widget pemantauan: pasien dengan dosis yang belum terkonfirmasi dalam 7 hari terakhir.
- Peran petugas (admin, TB-DOTS, farmasi), akun dapat dinonaktifkan.
- Audit log untuk aksi penting petugas.

**Dalam pengembangan**

- API untuk aplikasi Android (aktivasi, jadwal, sinkronisasi konfirmasi minum).
- Aplikasi Android (alarm lokal, tombol "Sudah minum", offline-first).
- Deteksi dosis terlewat dan notifikasi (FCM).

## Arsitektur

```
 HP pasien / pendamping (Flutter)            Server (Laravel)                 RS
 ┌───────────────────────────┐      HTTPS   ┌──────────────────────┐
 │ Alarm lokal (offline)     │ <──────────> │ API  /api/v1/*       │
 │ Antrean konfirmasi lokal  │   Sanctum    │ Dashboard /admin     │
 └───────────────────────────┘   token      │ (Filament, petugas)  │
                                            │ Scheduler + Queue    │
                                            └──────────┬───────────┘
                                                       ▼
                                                  DB sendiri
                                                  alarm_tbc
```

Keputusan penting:

- Alarm dijadwalkan **lokal di HP**, sehingga tetap berbunyi tanpa kuota atau sinyal.
- Satu database milik sendiri. Tidak ada koneksi ke SIMRS, bahkan untuk membaca.
- `no_rm` hanyalah data identitas yang diketik petugas (unik di dalam sistem ini), bukan penghubung ke sistem lain.

## Teknologi

| Komponen | Teknologi |
|---|---|
| Backend | Laravel (PHP) |
| Dashboard | Filament |
| Autentikasi API | Laravel Sanctum |
| Database | MySQL |
| Aplikasi pasien | Flutter (Android), repositori terpisah |
| Notifikasi | Firebase Cloud Messaging (rencana) |

Versi yang dipakai: PHP `____`, Laravel `____`, Filament `____` (isi sesuai `php -v` dan `composer show`).

## Prasyarat

- PHP (sesuai syarat versi Laravel yang terpasang) dengan ekstensi umum Laravel (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `intl`)
- Composer
- MySQL 8 atau MariaDB setara
- Git
- Untuk Windows: Laragon atau XAMPP memudahkan pemasangan PHP dan MySQL

Node.js tidak diperlukan untuk menjalankan dashboard.

## Instalasi

Langkah berikut untuk lingkungan pengembangan lokal.

**1. Ambil kode**

```bash
git clone https://github.com/<username>/<nama-repositori>.git
cd <nama-repositori>
```

**2. Pasang dependensi**

```bash
composer install
```

**3. Siapkan file lingkungan**

```bash
cp .env.example .env        # Windows (cmd): copy .env.example .env
php artisan key:generate
```

**4. Buat database**

```sql
CREATE DATABASE alarm_tbc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**5. Isi `.env`**

Sesuaikan kredensial database. Pastikan juga `APP_TIMEZONE=Asia/Jakarta`.

**6. Jalankan migrasi**

```bash
php artisan migrate
```

**7. Buat akun admin**

```bash
php artisan make:filament-user
php artisan tinker
>>> App\Models\User::where('email', 'emailmu@contoh.com')->update(['role' => 'admin']);
```

**8. Jalankan**

```bash
php artisan serve
```

Dashboard: <http://127.0.0.1:8000/admin>

**9. Cek cepat**

```bash
php artisan migrate:status
php artisan tinker
>>> DB::connection()->select('select 1');
```

## Konfigurasi

### Variabel lingkungan

| Variabel | Keterangan |
|---|---|
| `APP_TIMEZONE` | Wajib `Asia/Jakarta`. Jadwal alarm bergantung pada zona waktu |
| `DB_*` | Database milik sistem (`alarm_tbc`) |

Contoh blok di `.env`:

```env
APP_TIMEZONE=Asia/Jakarta
```

## Peran pengguna

| Peran | Hak utama |
|---|---|
| `admin` | Semua fitur dashboard |
| `tb_dots` | Mendaftarkan pasien, resep, jadwal, **membuat kode aktivasi** |
| `farmasi` | Mengelola resep dan jadwal. Tidak dapat membuat kode aktivasi |

Akun dengan `is_active = false` tidak dapat masuk dashboard.

## Alur kerja petugas

1. **Daftarkan pasien:** menu *Pasien Program*, klik *Daftarkan pasien*, isi data pasien secara manual (no. RM, nama, tanggal lahir, dll), lalu lengkapi PMO dan status.
2. **Isi resep:** buka pasien, tab *Resep obat*. Salin teks dosis persis dari resep dokter, tentukan fase, tanggal mulai, dan **tanggal selesai** (wajib, ditentukan bersama dokter atau petugas TB).
3. **Buat jadwal:** tekan *Buat/perbarui jadwal*. Tombol ini aman ditekan berulang. Jadwal mendatang diganti sesuai resep dan jam minum terbaru, sedangkan jadwal yang sudah punya catatan konfirmasi tidak dihapus.
4. **Buat kode aktivasi:** tekan *Buat kode aktivasi*. Kode (format `XXXX-XXXX`) hanya ditampilkan sekali dan berlaku 48 jam. Tuliskan di kartu pasien.
5. **Pantau:** dashboard utama menampilkan pasien dengan dosis yang belum terkonfirmasi. Hubungi pasien atau PMO untuk menindaklanjuti.

Catatan: status "belum terkonfirmasi" belum tentu berarti pasien tidak minum obat. HP yang belum tersinkron (misalnya tanpa kuota) juga tampil di sini.

## Struktur proyek

```
app/
├── Filament/
│   ├── Resources/PasienPrograms/
│   │   ├── PasienProgramResource.php
│   │   ├── Pages/
│   │   ├── RelationManagers/ResepRelationManager.php
│   │   ├── Schemas/PasienProgramForm.php
│   │   └── Tables/PasienProgramsTable.php
│   └── Widgets/PasienBermasalahWidget.php
├── Models/                  # Pmo, PasienProgram, Resep, Jadwal, LogMinum, ...
├── Providers/AppServiceProvider.php   # pembatas laju API
└── Services/
    ├── ActivationCodeService.php
    ├── AuditLogger.php
    └── ScheduleGenerator.php
database/
└── migrations/
docs/                        # dokumen rancangan dan rangkuman
```

## Model data

| Tabel | Isi |
|---|---|
| `users` | Petugas, dengan `role` dan `is_active` |
| `pmo` | Pengawas Menelan Obat (nama, HP, hubungan) |
| `pasien_program` | Pasien program, data mandiri hasil input petugas (no. RM, nama, tempat/tanggal lahir, jenis kelamin, no. HP, alamat) |
| `resep` | Resep obat (dosis berupa teks) beserta fase dan rentang tanggal |
| `jadwal` | Jadwal minum, kontrol, dan ambil obat. Unik per pasien, jenis, tanggal, jam |
| `log_minum` | Konfirmasi minum. `client_uuid` unik untuk mencegah data ganda |
| `kode_aktivasi` | Hash kode aktivasi, masa berlaku, waktu dipakai |
| `device_token` | HP yang terhubung, token FCM |
| `consent` | Persetujuan penggunaan data |
| `audit_log` | Jejak aksi petugas |

## API (rencana)

Prefix `/api/v1`, autentikasi token Sanctum untuk pasien.

| Endpoint | Fungsi |
|---|---|
| `POST /aktivasi` | Tukar kode petugas dengan token |
| `GET /profil` | Profil pasien, PMO, resep aktif, status consent |
| `POST /consent` | Catat persetujuan |
| `GET /jadwal` | Jadwal 30 hari ke depan |
| `POST /log-minum/sinkron` | Kirim konfirmasi minum (berkelompok, anti-dobel) |
| `PUT /perangkat` | Perbarui token FCM |

Tanggal dan jam dikirim sebagai teks lokal (`2026-10-25`, `07:00`), bukan UTC, agar alarm tidak bergeser hari.

## Pengujian manual

Sebelum menambah fitur baru, pengujian dilakukan manual per bagian: fondasi, database, dashboard pasien, resep dan jadwal, widget pemantauan, dan kesiapan (data uji bersih dan cadangan database).

Beberapa pemeriksaan yang berguna:

```bash
php artisan migrate:status
php artisan optimize:clear
```

Uji alarm di aplikasi Android harus memakai **HP fisik** dari beberapa merek (Xiaomi, Oppo, Vivo, Samsung), bukan hanya emulator.

## Keamanan dan privasi

Status TBC termasuk data kesehatan yang dilindungi **UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi**.

- **Jangan mengunggah** `.env`, data pasien nyata, atau hasil `mysqldump` ke repositori.
- Sistem tidak terhubung ke SIMRS. Data identitas pasien hanya berasal dari input petugas, jadi kumpulkan seperlunya saja (prinsip minimisasi data).
- Kode aktivasi disimpan sebagai hash (SHA-256), hanya ditampilkan sekali, berlaku 48 jam, dan sekali pakai.
- Akses dashboard berbasis peran dan seluruh aksi penting tercatat di `audit_log`.
- Notifikasi aplikasi dibuat netral ("Waktunya minum obat") tanpa kata TBC, untuk mengurangi stigma di layar kunci.
- Produksi wajib memakai HTTPS, `APP_DEBUG=false`, dan cadangan database terjadwal.
- Hapus akun dan data uji sebelum dipakai di produksi.

Bila menemukan celah keamanan, laporkan langsung ke tim melalui kontak di bawah, jangan lewat *issue* publik.

## Pemecahan masalah

| Gejala | Kemungkinan penyebab dan solusi |
|---|---|
| Akun nonaktif masih bisa masuk dashboard | Pastikan model `User` `implements FilamentUser` dengan method `canAccessPanel` |
| `User::create()` tidak menyimpan `role` atau `is_active` | Daftarkan kedua kolom di `$fillable` (atau atribut `#[Fillable]`) |
| Hasil di `tinker` tidak berubah setelah mengedit kode | Tutup (`exit`) dan buka lagi tinker, karena kode tidak dimuat ulang |
| Jadwal tidak berubah setelah jam minum diganti | Simpan perubahan, lalu tekan *Buat/perbarui jadwal*. Pasien tanpa resep tidak menghasilkan jadwal. Jadwal yang sudah punya log sengaja tidak diganti |
| Dashboard tidak menampilkan data terbaru | Muat ulang halaman (Ctrl+Shift+R). Perubahan lewat tinker atau MySQL baru tampil setelah dimuat ulang |
| Tanggal pada JSON mundur satu hari | Pastikan cast `date:Y-m-d` pada model `Jadwal` dan `Resep` |
| Pasien tidak muncul saat dicari | Pencarian memakai nama atau no. RM persis seperti yang diketik saat pendaftaran. Cek ejaan dan spasi |

## Roadmap

- [x] Setup Laravel, Filament, Sanctum
- [x] Skema database dan model
- [x] Data pasien mandiri (tanpa SIMRS), input manual oleh petugas
- [x] Dashboard pasien, PMO, kode aktivasi, audit log
- [x] Resep, pembuat jadwal, widget pemantauan
- [ ] Menuntaskan pengujian manual dan serah terima Fase 1
- [ ] API untuk aplikasi Android
- [ ] Aplikasi Android (Flutter): alarm lokal, konfirmasi, sinkronisasi
- [ ] Deteksi dosis terlewat dan notifikasi FCM
- [ ] Jadwal kontrol dan pengingat ambil obat
- [ ] Foto bukti minum (opsional, dikompres)
- [ ] Deploy produksi (HTTPS, cadangan), panduan petugas, dan dokumentasi serah terima

## Tim dan kontak

| Peran | Nama |
|---|---|
| Backend dan dashboard | `____` |
| Aplikasi Android dan desain tampilan | `____` |
| Pembimbing / koordinator KKN | `____` |
| Kontak | `____` |

## Lisensi

`____` (belum ditentukan).

Proyek ini berkaitan dengan data kesehatan dan institusi rumah sakit. Tentukan lisensi atau status kepemilikan kode bersama pihak rumah sakit dan pembimbing sebelum repositori dibuka untuk umum.
