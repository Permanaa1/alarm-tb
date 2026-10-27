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
- Data pasien dan resep dibaca dari **SIMRS rumah sakit secara read-only**. Semua data baru disimpan di database sendiri.
- Login memakai **kode aktivasi dari petugas**, bukan OTP berbayar.
- Aplikasi **tidak menghitung dosis**. Dosis hanya teks yang disalin petugas dari resep dokter.

## Fitur

**Sudah tersedia (dashboard petugas)**

- Pencarian pasien dari SIMRS (no. RM atau nama), hanya pasien dengan diagnosis TBC.
- Pendaftaran pasien ke program, termasuk data PMO (Pengawas Menelan Obat).
- Pengelolaan resep obat (dosis berupa teks dari dokter) dan tampilan resep SIMRS sebagai acuan.
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
                                            └───────┬───────┬──────┘
                                                    │       │ SELECT saja
                                                    ▼       ▼
                                             DB sendiri   DB SIMRS (read-only)
                                             alarm_tbc    pasien, diagnosis, resep
```

Keputusan penting:

- Alarm dijadwalkan **lokal di HP**, sehingga tetap berbunyi tanpa kuota atau sinyal.
- Dua koneksi database: koneksi default (baca dan tulis) dan koneksi `simrs` (hanya baca).
- Akses SIMRS lewat satu kelas repository dan pemetaan di `config/simrs.php`. Bila skema SIMRS asli berbeda, cukup ubah config.
- Tidak ada foreign key lintas database. Penghubung kedua database adalah `no_rm`.

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

**4. Buat dua database**

```sql
CREATE DATABASE alarm_tbc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE simrs_dummy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**5. Isi `.env`**

Sesuaikan kredensial database, dan pastikan blok SIMRS ada (lihat [Konfigurasi](#konfigurasi)). Pastikan juga `APP_TIMEZONE=Asia/Jakarta`.

**6. Isi database SIMRS tiruan** (data fiktif untuk pengembangan)

```bash
mysql -u root -p simrs_dummy < database/simrs_dummy.sql
```

Catatan: di PowerShell operator `<` tidak berfungsi. Gunakan `cmd`, Git Bash, atau:

```powershell
Get-Content database/simrs_dummy.sql | mysql -u root -p simrs_dummy
```

**7. Jalankan migrasi** (hanya ke database `alarm_tbc`)

```bash
php artisan migrate
```

**8. Buat akun admin**

```bash
php artisan make:filament-user
php artisan tinker
>>> App\Models\User::where('email', 'emailmu@contoh.com')->update(['role' => 'admin']);
```

**9. Jalankan**

```bash
php artisan serve
```

Dashboard: <http://127.0.0.1:8000/admin>

**10. Cek cepat**

```bash
php artisan migrate:status
php artisan tinker
>>> DB::connection()->select('select 1');
>>> DB::connection('simrs')->select('select 1');
```

Data contoh di SIMRS tiruan: `000101` Sutrisno (TBC), `000102` Siti Aminah (TBC), `000103` pasien bukan TBC (tidak akan muncul di pencarian).

## Konfigurasi

### Variabel lingkungan

| Variabel | Keterangan |
|---|---|
| `APP_TIMEZONE` | Wajib `Asia/Jakarta`. Jadwal alarm bergantung pada zona waktu |
| `DB_*` | Database milik sistem (`alarm_tbc`) |
| `SIMRS_DB_DRIVER` | `mysql`, atau `pgsql` / `sqlsrv` bila SIMRS memakai database lain |
| `SIMRS_DB_HOST`, `SIMRS_DB_PORT` | Alamat database SIMRS |
| `SIMRS_DB_DATABASE` | Nama database SIMRS (saat pengembangan: `simrs_dummy`) |
| `SIMRS_DB_USERNAME`, `SIMRS_DB_PASSWORD` | Akun database SIMRS. **Untuk produksi harus akun yang hanya punya izin `SELECT`** |

Contoh blok yang harus ada di `.env.example`:

```env
APP_TIMEZONE=Asia/Jakarta

SIMRS_DB_DRIVER=mysql
SIMRS_DB_HOST=127.0.0.1
SIMRS_DB_PORT=3306
SIMRS_DB_DATABASE=simrs_dummy
SIMRS_DB_USERNAME=root
SIMRS_DB_PASSWORD=
```

### Menghubungkan ke SIMRS asli

1. Minta ke IT rumah sakit: jenis database, cara akses, dan **akun dengan izin `SELECT` saja**.
2. Sesuaikan nama tabel dan kolom di `config/simrs.php` (pasien, diagnosis, resep).
3. Pastikan awalan kode ICD-10 TBC (`tbc_icd_prefix`) dan cara menandai pasien TBC sesuai kondisi di SIMRS. Nilai bawaan (A15 sampai A19) adalah asumsi.
4. Ganti `SIMRS_DB_*` di `.env`, lalu `php artisan optimize:clear`.
5. Uji pencarian pasien di dashboard.

Lapisan SIMRS menolak semua query selain `SELECT`, `SHOW`, `DESCRIBE`, dan `EXPLAIN` di sisi kode. Ini pagar tambahan, bukan pengganti akun database yang hanya-baca.

**Aturan penting:** jangan pernah membuat migrasi yang menyentuh koneksi `simrs` (`Schema::connection('simrs')`). Tabel tiruan SIMRS dibuat lewat SQL manual.

## Peran pengguna

| Peran | Hak utama |
|---|---|
| `admin` | Semua fitur dashboard |
| `tb_dots` | Mendaftarkan pasien, resep, jadwal, **membuat kode aktivasi** |
| `farmasi` | Mengelola resep dan jadwal. Tidak dapat membuat kode aktivasi |

Akun dengan `is_active = false` tidak dapat masuk dashboard.

## Alur kerja petugas

1. **Daftarkan pasien:** menu *Pasien Program*, cari pasien dari SIMRS, lengkapi PMO, jam minum, dan status.
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
├── Providers/AppServiceProvider.php   # pagar read-only SIMRS
└── Services/
    ├── ActivationCodeService.php
    ├── AuditLogger.php
    ├── ScheduleGenerator.php
    └── Simrs/SimrsRepository.php
config/
└── simrs.php                # pemetaan tabel/kolom SIMRS
database/
├── migrations/
└── simrs_dummy.sql          # data tiruan SIMRS (fiktif)
docs/                        # dokumen rancangan dan rangkuman
```

## Model data

| Tabel | Isi |
|---|---|
| `users` | Petugas, dengan `role` dan `is_active` |
| `pmo` | Pengawas Menelan Obat (nama, HP, hubungan) |
| `pasien_program` | Pasien yang terdaftar di program (`no_rm` menghubungkan ke SIMRS) |
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

Sebelum menambah fitur baru, pengujian dilakukan manual per bagian: fondasi, database, lapisan SIMRS, dashboard pasien, resep dan jadwal, widget pemantauan, dan kesiapan (data uji bersih dan cadangan database).

Beberapa pemeriksaan yang berguna:

```bash
php artisan migrate:status
php artisan optimize:clear
```

```sql
-- Database SIMRS tiruan tidak boleh berisi tabel milik sistem
USE simrs_dummy; SHOW TABLES;
```

Uji alarm di aplikasi Android harus memakai **HP fisik** dari beberapa merek (Xiaomi, Oppo, Vivo, Samsung), bukan hanya emulator.

## Keamanan dan privasi

Status TBC termasuk data kesehatan yang dilindungi **UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi**.

- **Jangan mengunggah** `.env`, data pasien nyata, hasil `mysqldump`, atau kredensial SIMRS ke repositori.
- Database SIMRS tiruan hanya berisi data fiktif.
- Akun database SIMRS produksi harus hanya punya izin `SELECT`.
- Kode aktivasi disimpan sebagai hash (SHA-256), hanya ditampilkan sekali, berlaku 48 jam, dan sekali pakai.
- Akses dashboard berbasis peran dan seluruh aksi penting tercatat di `audit_log`.
- Notifikasi aplikasi dibuat netral ("Waktunya minum obat") tanpa kata TBC, untuk mengurangi stigma di layar kunci.
- Produksi wajib memakai HTTPS, `APP_DEBUG=false`, dan cadangan database terjadwal.
- Hapus akun dan data uji sebelum dipakai di produksi.

Bila menemukan celah keamanan, laporkan langsung ke tim melalui kontak di bawah, jangan lewat *issue* publik.

## Pemecahan masalah

| Gejala | Kemungkinan penyebab dan solusi |
|---|---|
| `SQLSTATE ... Connection refused` pada koneksi `simrs` | Cek `SIMRS_DB_*`, lalu `php artisan optimize:clear`. Pastikan database SIMRS dapat dijangkau dari server |
| Akun nonaktif masih bisa masuk dashboard | Pastikan model `User` `implements FilamentUser` dengan method `canAccessPanel` |
| `User::create()` tidak menyimpan `role` atau `is_active` | Daftarkan kedua kolom di `$fillable` (atau atribut `#[Fillable]`) |
| Hasil di `tinker` tidak berubah setelah mengedit kode | Tutup (`exit`) dan buka lagi tinker, karena kode tidak dimuat ulang |
| Jadwal tidak berubah setelah jam minum diganti | Simpan perubahan, lalu tekan *Buat/perbarui jadwal*. Pasien tanpa resep tidak menghasilkan jadwal. Jadwal yang sudah punya log sengaja tidak diganti |
| Dashboard tidak menampilkan data terbaru | Muat ulang halaman (Ctrl+Shift+R). Perubahan lewat tinker atau MySQL baru tampil setelah dimuat ulang |
| Tanggal pada JSON mundur satu hari | Pastikan cast `date:Y-m-d` pada model `Jadwal` dan `Resep` |
| Pencarian pasien tidak menemukan siapa pun | Cek `filter_tbc` dan awalan ICD di `config/simrs.php`, serta data di tabel `diagnosis` |

## Roadmap

- [x] Setup Laravel, Filament, Sanctum, dua koneksi database
- [x] Skema database dan model
- [x] Lapisan SIMRS read-only
- [x] Dashboard pasien, PMO, kode aktivasi, audit log
- [x] Resep, pembuat jadwal, widget pemantauan
- [ ] Menuntaskan pengujian manual dan serah terima Fase 1
- [ ] API untuk aplikasi Android
- [ ] Aplikasi Android (Flutter): alarm lokal, konfirmasi, sinkronisasi
- [ ] Deteksi dosis terlewat dan notifikasi FCM
- [ ] Jadwal kontrol dan pengingat ambil obat
- [ ] Foto bukti minum (opsional, dikompres)
- [ ] Integrasi SIMRS asli
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
