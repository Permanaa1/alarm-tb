# Batch 6: API untuk Aplikasi Flutter (pasang dan uji)

Isi paket ini (salin ke akar proyek Laravel, gabungkan dengan folder yang sudah ada):

```
app/Http/Middleware/EnsureTokenPasien.php
app/Http/Controllers/Api/V1/AktivasiController.php
app/Http/Controllers/Api/V1/PasienController.php
app/Http/Controllers/Api/V1/LogMinumController.php
routes/api.php                      <- MENIMPA file lama
docs/Alarm-TBC-API.postman_collection.json   <- untuk Postman / Thunder Client
```

## 0. Titik kembali (sebelum memasang)

```bash
git add -A
git commit -m "Fase 1 selesai: backend dan dashboard lolos uji"
git tag v0.1.0-fase1
git checkout -b feature/api
```

## 1. Edit `bootstrap/app.php`

Pastikan ada import `use Illuminate\Http\Request;` di bagian atas, lalu isi `withMiddleware` dan `withExceptions`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'pasien' => \App\Http\Middleware\EnsureTokenPasien::class,
    ]);
})
->withExceptions(function (Exceptions $exceptions): void {
    // Semua error di /api/* selalu berbentuk JSON (tidak redirect ke halaman login)
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request, \Throwable $e) => $request->is('api/*') || $request->expectsJson()
    );
})
```

Bagian `api: __DIR__.'/../routes/api.php'` di `withRouting` sudah ada dari `install:api`. Jangan diubah. Bila `alias([...])` sudah berisi isi lain, tambahkan baris `'pasien' => ...` di dalamnya.

## 2. Edit `app/Providers/AppServiceProvider.php`

Tambahkan import:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
```

Tambahkan dua baris ini di dalam `boot()` (di bawah pagar read-only SIMRS yang sudah ada):

```php
RateLimiter::for('aktivasi', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
RateLimiter::for('api-pasien', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->getAuthIdentifier() ?: $request->ip()));
```

## 3. Bersihkan cache dan cek rute

```bash
php artisan optimize:clear
php artisan route:list --path=api
```

Harus tampil 6 rute: `aktivasi`, `profil`, `jadwal`, `consent`, `perangkat`, `log-minum/sinkron`.

## 4. Checkpoint

Jalankan `php artisan serve`. Impor `Alarm-TBC-API.postman_collection.json` ke Postman atau Thunder Client (VS Code). Semua request sudah memakai header `Accept: application/json`.

Siapkan: di dashboard, klik **Buat kode aktivasi** untuk Sutrisno, lalu isi variabel `kode` di koleksi dengan kode itu.

| # | Request | Hasil yang benar |
|---|---|---|
| 1 | Aktivasi | 200, ada `token` dan `profil`. (Di Postman token tersimpan otomatis ke variabel `token`. Di Thunder Client, salin manual.) |
| 2 | Aktivasi ulang (kode sama) | **422**, pesan kode tidak valid |
| 3 | Profil | 200, nama, resep, `consent_disetujui: false` |
| 4 | Consent | 200, lalu Profil lagi menunjukkan `consent_disetujui: true` |
| 5 | Jadwal | 200, sekitar 14 jadwal, `tanggal` berformat `2026-10-26` (tidak mundur sehari). Catat satu `id` ke variabel `jadwal_id` |
| 6 | Sinkron | `dicatat` untuk item pertama, `ditolak` (`jadwal_tidak_ditemukan`) untuk item kedua |
| 7 | Sinkron ulang | Item pertama menjadi `sudah_ada`, tidak ada baris dobel |
| 8 | Profil tanpa token | **401 berbentuk JSON** (bukan HTML/redirect/500) |
| 9 | Perangkat (PUT) | 200 |

Uji tambahan (manual):

10. **Token lama dicabut:** buat kode baru untuk Sutrisno di dashboard, aktivasi lagi, lalu pakai **token lama** di Profil. Harus **401**.
11. **Pembatas:** kirim 11 aktivasi dengan kode ngawur dalam satu menit. Yang ke-11 harus **429**.
12. **Pasien tidak aktif:** ubah status Sutrisno ke `selesai` di dashboard, lalu panggil Profil dengan token yang masih valid. Harus **403**. Kembalikan ke `aktif`.

Cek database setelahnya:

```sql
SELECT id, jadwal_id, status, waktu_konfirmasi, diterima_server_at, client_uuid FROM log_minum ORDER BY id DESC LIMIT 3;
SELECT id, nama_perangkat, terakhir_aktif, dicabut_pada FROM device_token ORDER BY id;
SELECT aksi, objek_tipe, objek_id FROM audit_log ORDER BY id DESC LIMIT 5;
```

Harapan: log minum tercatat dengan `waktu_konfirmasi` sesuai, perangkat pertama sudah berisi `dicabut_pada` dan perangkat kedua kosong, dan ada entri `aktivasi_perangkat`.

Bila semua lolos: `git add -A && git commit -m "Batch 6: API pasien lolos uji"`.
