# PRD, Guidelines & Task — Integrasi Laravel Breeze

## 1. PRD (Product Requirement Document)

**Tujuan:** Memasang Laravel Breeze (stack Blade) sebagai sistem autentikasi utama aplikasi, lalu menyesuaikannya agar terhubung dengan skema `role_id` yang sudah dibangun — bukan sekadar install default, karena Breeze secara bawaan **tidak tahu** soal tabel `roles` dan aturan bisnis kita (default role Karyawan, verifikasi email wajib, tanpa gerbang aktivasi Admin).

**Cakupan:**
- Login, Register, Logout, Forgot Password (4 fitur wajib di soal) — disediakan Breeze secara default
- Verifikasi email — sudah tersedia strukturnya di Breeze, tinggal diaktifkan
- Auto-assign role Karyawan saat registrasi — **perlu kustomisasi**, tidak bawaan Breeze
- Middleware role per route group — sudah ada (`CheckRole`), tinggal dipasang di `routes/web.php`
- Akun testing awal (Admin, Staff, Manager) — **perlu dibuat manual via seeder**, karena aturan kita bilang hanya Admin yang bisa menaikkan role, sehingga harus ada minimal 1 Admin yang di-seed langsung ke database sebagai titik awal (bootstrap problem yang wajar di semua sistem RBAC)

**Di luar cakupan tahap ini:** desain ulang tampilan (Blade view Breeze dipakai apa adanya dulu, styling/branding Telkomsel menyusul di tahap lain), dashboard per role (fase terpisah setelah ini).

## 2. Guidelines

1. **Jangan edit file vendor/stub Breeze secara sembarangan** — semua kustomisasi cukup di `app/Http/Controllers/Auth/RegisteredUserController.php` (untuk auto-assign role) dan `app/Models/User.php` (untuk `MustVerifyEmail`). Jangan modifikasi package di `vendor/`.
2. **Route auth bawaan (`routes/auth.php`) tidak perlu diubah** — biarkan Breeze yang urus login/register/logout/reset password. Yang kita ubah cukup **logic di controller-nya**, bukan definisi route-nya.
3. **Middleware `role` yang sudah ada dipasang di `routes/web.php`**, dikelompokkan per role, konsisten dengan pola yang sudah didokumentasikan sebelumnya:
   ```php
   Route::middleware(['auth', 'verified', 'role:admin'])->group(function () { ... });
   Route::middleware(['auth', 'verified', 'role:admin,staff'])->group(function () { ... });
   ```
4. **Testing dilakukan bertahap** — jangan lanjut ke task berikutnya kalau register→verifikasi→login belum benar-benar berhasil dicoba manual di browser.
5. **Commit terpisah per task besar** — jangan ditumpuk jadi 1 commit raksasa di akhir seperti sebelumnya; kali ini pecah minimal 2-3 commit (install Breeze, kustomisasi role, seeder akun testing) supaya riwayat git lebih mudah ditelusuri kalau ada bug.
6. **Password akun testing/seeder** harus sederhana dan didokumentasikan di README nanti (requirement submission eksplisit minta "akun login testing"), tapi jangan dipakai di akun manapun yang bukan untuk testing.

## 3. Status Progress

- [x] **Task 1 — Install Breeze** — selesai, terverifikasi (`routes/auth.php` ada, route `login` terdaftar, Pest terpasang otomatis)
- [ ] Task 2 — Aktifkan verifikasi email
- [ ] Task 3 — Auto-assign role Karyawan saat registrasi
- [ ] Task 4 — Middleware role di `routes/web.php`
- [ ] Task 5 — Seeder akun testing
- [ ] Task 6 — Uji alur end-to-end

## 3a. Prompt Konsolidasi Task 2–6 (untuk diberikan langsung ke coding agent)

```
Tolong kerjakan 5 task berikut secara berurutan di project Laravel ini.
Jalankan verifikasi di akhir tiap task sebelum lanjut ke task berikutnya,
dan laporkan hasil tiap verifikasi.

TASK 2 — Aktifkan verifikasi email
- Buka app/Models/User.php
- Hapus komentar pada baris: // use Illuminate\Contracts\Auth\MustVerifyEmail;
- Tambahkan "implements MustVerifyEmail" ke deklarasi class User
- Jangan ubah bagian lain di file ini
- Verifikasi: jalankan `php artisan route:list --path=verify` dan pastikan
  muncul GET verify-email serta POST email/verification-notification

TASK 3 — Auto-assign role Karyawan saat registrasi
- Buka app/Http/Controllers/Auth/RegisteredUserController.php
- Di method store(), sebelum User::create(...), tambahkan query untuk 
  mengambil id role 'karyawan' dari tabel roles (gunakan App\Models\Role)
- Tambahkan 'role_id' => (id yang didapat) ke array yang dikirim ke 
  User::create()
- Verifikasi: register 1 user baru via tinker atau factory, lalu cek 
  role_id user tersebut merujuk ke role 'karyawan'

TASK 4 — Middleware role di routes/web.php
- Bungkus route dashboard yang sudah ada dengan middleware ['auth', 'verified']
  (kemungkinan sudah begitu secara default dari Breeze, cukup pastikan)
- Tambahkan 2 route group baru sebagai kerangka (isi controller/view 
  menyusul di fase fitur nanti, sekarang cukup closure placeholder):
  - Route::middleware('role:admin')->prefix('admin')->group(...)
  - Route::middleware('role:admin,staff')->group(...) untuk operasional
- Verifikasi: tampilkan hasil `php artisan route:list` untuk route yang baru
  ditambahkan, pastikan middleware role muncul di kolom middleware

TASK 5 — Seeder akun testing
- Buat seeder baru: php artisan make:seeder UserSeeder
- Isi run() untuk membuat 4 user: admin@telkomsel.test, staff@telkomsel.test,
  manager@telkomsel.test, karyawan@telkomsel.test - semua password 'password',
  role_id sesuai nama masing-masing, dan email_verified_at diisi now()
  (supaya akun testing tidak perlu verifikasi email manual)
- Daftarkan UserSeeder di DatabaseSeeder.php SETELAH RoleSeeder
- Jalankan: php artisan db:seed --class=UserSeeder
- Verifikasi: query 4 user ini via tinker, tampilkan name, email, dan 
  role->name masing-masing untuk konfirmasi benar

TASK 6 — Laporan akhir
- Jalankan php artisan route:list --path=login,register,verify,dashboard,admin
- Tampilkan isi lengkap RegisteredUserController.php dan routes/web.php 
  setelah semua perubahan
- Jalankan test suite (php artisan test atau vendor/bin/pest) dan laporkan 
  hasilnya
- JANGAN mencoba login/register manual lewat browser - itu akan saya 
  lakukan sendiri secara manual setelah kode ini siap
```

## 3b. Task Checklist (detail per task, untuk referensi)

### Task 1 — Install Breeze
```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
```
Saat prompt muncul: pilih **Pest** kalau ditanya testing framework (sesuaikan dengan yang sudah dipakai project berdasarkan hasil test sebelumnya), dan boleh pilih **Ya** untuk dark mode support (opsional, tidak wajib).
```bash
npm install
npm run build
php artisan migrate
```

**Verifikasi:**
```bash
ls routes/          # harus muncul auth.php
php artisan route:list --path=login   # harus muncul GET|HEAD login
```
Commit: `feat(auth): install laravel breeze blade stack`

---

### Task 2 — Aktifkan verifikasi email di User Model
Buka `app/Models/User.php`, ubah:
```php
// use Illuminate\Contracts\Auth\MustVerifyEmail;
```
menjadi (hapus komentar) dan tambahkan implements:
```php
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    // isi class tetap sama, tidak ada yang perlu diubah selain baris di atas
}
```
Breeze blade stack **sudah otomatis** menyediakan route dan controller verifikasi email (`verify-email`, `verification-notification`) — cukup dengan mengaktifkan interface ini, seluruh alurnya langsung berfungsi.

**Verifikasi:**
```bash
php artisan route:list --path=verify
# harus muncul GET verify-email dan POST email/verification-notification
```

---

### Task 3 — Auto-assign role Karyawan saat registrasi
Buka `app/Http/Controllers/Auth/RegisteredUserController.php`, cari method `store()`, ubah bagian pembuatan user:
```php
use App\Models\Role;

// ...di dalam method store(), ganti bagian User::create([...]) menjadi:

$karyawanRoleId = Role::where('name', 'karyawan')->value('id');

$user = User::create([
    'name' => $request->name,
    'email' => $request->email,
    'password' => Hash::make($request->password),
    'role_id' => $karyawanRoleId,
]);
```
Ini konsisten dengan keputusan kita: role default registrasi mandiri selalu **Karyawan**, tanpa terkecuali.

**Verifikasi:** setelah register lewat browser, cek langsung:
```bash
php artisan tinker --execute="
    \$u = App\Models\User::latest()->first();
    echo \$u->name . ' - role: ' . \$u->role->name . PHP_EOL;
"
```
Harus menunjukkan role `karyawan`.

Commit: `feat(auth): auto-assign role karyawan saat registrasi`

---

### Task 4 — Middleware role di `routes/web.php`
Tambahkan struktur dasar (isi controller/view menyusul di fase fitur, untuk sekarang cukup kerangka rute + pastikan middleware jalan):
```php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Khusus Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // route kelola user, dsb - diisi di fase berikutnya
    });

    // Staff & Admin (operasional inventaris)
    Route::middleware('role:admin,staff')->group(function () {
        // route master data barang, approval peminjaman - diisi di fase berikutnya
    });

    // Semua role yang login boleh akses (Karyawan termasuk)
    Route::get('/riwayat-peminjaman', function () {
        return 'placeholder';
    })->name('riwayat.peminjaman');
});
```
**Verifikasi:** login pakai akun dengan role selain admin, coba akses `/admin` (kalau ada route di dalamnya) → harus dapat `403 Forbidden`.

---

### Task 5 — Seeder akun testing (Admin, Staff, Manager, Karyawan)
```bash
php artisan make:seeder UserSeeder
```
Isi `run()`:
```php
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

public function run(): void
{
    $roles = Role::pluck('id', 'name');

    $accounts = [
        ['name' => 'Admin Testing',    'email' => 'admin@telkomsel.test',    'role' => 'admin'],
        ['name' => 'Staff Testing',    'email' => 'staff@telkomsel.test',    'role' => 'staff'],
        ['name' => 'Manager Testing',  'email' => 'manager@telkomsel.test',  'role' => 'manager'],
        ['name' => 'Karyawan Testing', 'email' => 'karyawan@telkomsel.test', 'role' => 'karyawan'],
    ];

    foreach ($accounts as $akun) {
        User::create([
            'name' => $akun['name'],
            'email' => $akun['email'],
            'password' => Hash::make('password'),
            'role_id' => $roles[$akun['role']],
            'email_verified_at' => now(), // akun testing langsung terverifikasi, tanpa perlu cek email
        ]);
    }
}
```
Daftarkan di `DatabaseSeeder.php` setelah `RoleSeeder::class`, lalu:
```bash
php artisan db:seed --class=UserSeeder
```

**Catatan penting untuk README nanti:** simpan kredensial ini (`admin@telkomsel.test` / `password`, dst) — ini yang akan jadi bagian "Akun Login Testing" di deliverable submission Anda.

Commit: `feat(database): tambah seeder akun testing 4 role`

---

### Task 6 — Uji alur end-to-end secara manual di browser
1. Buka `/register`, daftar akun baru → cek redirect ke halaman verifikasi email
2. Cek email (kalau `MAIL_MAILER=log` di `.env`, buka `storage/logs/laravel.log` untuk lihat link verifikasi karena email tidak benar-benar terkirim di lokal)
3. Klik link verifikasi → pastikan redirect ke `/dashboard`
4. Logout, coba login pakai salah satu akun seeder (`admin@telkomsel.test` / `password`)
5. Coba akses route yang di-protect `role:admin` pakai akun `karyawan@telkomsel.test` → harus `403`

## 4. Definition of Done (fase ini dianggap selesai kalau)

- [ ] Register → verifikasi email → login → dashboard berjalan tanpa error
- [ ] User baru hasil registrasi mandiri otomatis dapat `role_id` Karyawan
- [ ] 4 akun testing (admin/staff/manager/karyawan) sudah ter-seed dan bisa login
- [ ] Middleware `role` terbukti memblokir akses lintas-role (403 saat diakses role yang salah)
- [ ] Semua perubahan sudah di-commit terpisah dan di-push ke GitHub