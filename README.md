# 🏢 API Sistem Manajemen Cuti Karyawan

> RESTful API untuk sistem manajemen cuti karyawan — dibangun dengan **Laravel 12**, **MySQL**, **Laravel Sanctum**, dan **Spatie Permission**.

---

## 📋 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Tech Stack](#-tech-stack)
- [Arsitektur Sistem](#-arsitektur-sistem)
- [Alur Sistem](#-alur-sistem)
- [Panduan Instalasi](#-panduan-instalasi)
- [Konfigurasi .env](#-konfigurasi-env)
- [Menjalankan Aplikasi](#-menjalankan-aplikasi)
- [API Endpoints](#-api-endpoints)
- [Business Logic](#-business-logic)
- [Struktur Direktori](#-struktur-direktori)
- [Postman Documentation](#-postman-documentation)

---

## 🎯 Tentang Proyek

Sistem ini dirancang untuk mengelola pengajuan cuti karyawan secara digital. Karyawan dapat mengajukan cuti dengan menyertakan dokumen pendukung, sementara Admin dapat menyetujui atau menolak setiap pengajuan. Sistem memiliki kuota cuti otomatis sebanyak **12 hari per tahun** per karyawan.

### Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| 🔐 Authentication | Login konvensional (email & password) + OAuth Google |
| 👥 Role Management | Dua role: `karyawan` dan `admin` dengan hak akses berbeda |
| 📝 Pengajuan Cuti | Karyawan mengajukan cuti dengan file lampiran |
| ✅ Approval System | Admin approve/reject pengajuan dengan catatan |
| 📊 Kuota Otomatis | 12 hari/tahun, otomatis berkurang saat cuti disetujui |
| 🔄 Status Tracking | Pending → Approved / Rejected |

---

## 🛠 Tech Stack

```
┌─────────────────────────────────────────────────────┐
│                    TECH STACK                       │
├────────────────────┬────────────────────────────────┤
│ Framework          │ Laravel 12                     │
│ Database           │ MySQL 8.x                      │
│ Authentication     │ Laravel Sanctum (API Token)    │
│ OAuth              │ Laravel Socialite (Google)     │
│ Authorization      │ Spatie Laravel Permission      │
│ File Storage       │ Laravel Storage (local/public) │
│ Language           │ PHP 8.3+                       │
└────────────────────┴────────────────────────────────┘
```

---

## 🏛 Arsitektur Sistem

Proyek ini menggunakan pendekatan **Clean Architecture** yang memisahkan tanggung jawab setiap layer secara jelas:

```
┌─────────────────────────────────────────────────────────────┐
│                     CLIENT / POSTMAN                        │
└───────────────────────────┬─────────────────────────────────┘
                            │ HTTP Request
                            ▼
┌────────────────────────────────────────────────────────────┐
│                      ROUTES LAYER                          │
│                     routes/api.php                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │  Public      │  │  Karyawan    │  │  Admin           │  │
│  │  Routes      │  │  Routes      │  │  Routes          │  │
│  │  (auth)      │  │  (sanctum +  │  │  (sanctum +      │  │
│  │              │  │  role:kar)   │  │  role:admin)     │  │
│  └──────────────┘  └──────────────┘  └──────────────────┘  │
└───────────────────────────┬────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   MIDDLEWARE LAYER                          │
│  ┌──────────────────────┐  ┌──────────────────────────────┐ │
│  │  auth:sanctum        │  │  Spatie RoleMiddleware       | |
|  |                      |  |  (built-in)                  │ │
│  │  Token Validation    │  │  Role-Based Access Control   │ │
│  └──────────────────────┘  └──────────────────────────────┘ │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   CONTROLLER LAYER                          │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────────────┐ │
│  │     Auth     | |     Cuti     | |      AdminCuti       | |
|  |  Controller  │ │  Controller  │ │      Controller      │ │
│  │              │ │              │ │                      │ │
│  │ - register   │ │              │ │                      │ │
│  │ - login      │ │ - index      │ │ - index              │ │
│  │ - google     │ │ - store      │ │ - show               │ │
│  │ - callback   │ │ - show       │ │ - review             │ │
│  │ - logout     │ │ - kuota      │ │ - karyawan           │ │
│  └──────┬───────┘ └─────┬────────┘ └───────────┬──────────┘ │
└─────────┼───────────────┼──────────────────────┼────────────┘
          │               │                      │
          ▼               ▼                      ▼
┌─────────────────────────────────────────────────────────────┐
│                    SERVICE LAYER                            │
│              app/Services/CutiService.php                   │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  - createCuti()          → validasi dan ajukan cuti │    │
│  │  - reviewCutiRequest()   → approve/reject (Admin)   │    │
│  │  - getOrCreateQuota()    → manajemen kuota tahunan  │    │
│  │  - countWorkDays()       → hitung hari kerja        │    │
│  └─────────────────────────────────────────────────────┘    │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│                           MODEL LAYER                            │
│  ┌──────────────┐  ┌───────────────────┐  ┌───────────────────┐  │
│  │    User      │  │     Cuti          │  │    KuotaCuti      │  │
│  │              │  │                   │  │                   │  │
│  │ - name       │  │ - user_id         │  │ - user_id         │  │
│  │ - email      │  │ - tanggal_mulai   │  │ - tahun           │  │
│  │ - password   │  │ - tanggal_selesai │  │ - total_kuota     │  │
│  │ - google_id  │  │ - total_hari      │  │ - kuota_digunakan │  │
│  │ - avatar     │  │ - alasan          │  │                   │  │
│  │ - provider   │  │ - lampiran        │  │ remaining_quota   │  │
│  │              │  │ - status          |  |                   |  |
|  |              |  | - reviewed_by     |  |                   |  |
|  |              |  | - review_note     |  |                   |  |
|  |              |  | - reviewed_at     │  │                   │  │ 
│  └──────────────┘  └───────────────────┘  └───────────────────┘  │
└───────────────────────────┬──────────────────────────────────────┘
                            │
                            ▼
┌───────────────────────────────────────────────────────────────┐
│                     DATABASE LAYER                            │
│                         MySQL                                 │
│  ┌──────────┐  ┌─────────────────┐  ┌──────────────────────┐  │
│  │  users   │  │       Cuti      │  │      kuota_cuti      │  │
│  ├──────────┤  ├─────────────────┤  ├──────────────────────┤  │
│  │ id       │  │ id              │  │ id                   │  │
│  │ name     │  │ user_id (FK)    │  │ user_id (FK)         │  │
│  │ email    │  │ tanggal_mulai   │  │ tahun                │  │
│  │ password │  │ tanggal_selesai |  │ total_kuota (12)     │  │
│  │ google_id│  │ total_hari      │  │ kuota_digunakan      │  │
│  │ provider │  │ alasan          │  └──────────────────────┘  │
│  │ avatar   │  │ lampiran        │                            │
│  └──────────┘  │ status          │  ┌──────────────────────┐  │
│                │ reviewed_by     │  │  roles & permissions │  │
│                │ review_note     │  │  (Spatie tables)     │  │
│                │ reviewed_at     │  └──────────────────────┘  │
│                └─────────────────┘                            │
└───────────────────────────────────────────────────────────────┘
```

---

## 🔄 Alur Sistem

### 1. Alur Autentikasi

```
┌─────────────────────────────────────────────────┐
│              AUTHENTICATION FLOW                │
└─────────────────────────────────────────────────┘

  CONVENTIONAL LOGIN                GOOGLE OAUTH
  ─────────────────                 ─────────────
  POST /auth/login                  GET /auth/google
       │                                 │
       ▼                                 ▼
  Validasi email               Redirect ke Google
  & password                   Consent Screen
       │                                 │
       ▼                                 ▼
  Cek Hash::check()           User login di Google
       │                                 │
       ▼                                 ▼
  Buat Sanctum Token          Callback ke /auth/google/callback
       │                                 │
       └──────────────┬──────────────────┘
                      ▼
              Return Bearer Token
                      │
              Simpan di client
                      │
              Pakai di setiap request
              Header: Authorization: Bearer {token}
```

### 2. Alur Pengajuan Cuti (Karyawan)

```
Karyawan POST /api/cuti
         │
         ▼
  Validasi Input
(tanggal_mulai, tanggal_selesai, alasan, lampiran)
         │
         ▼
  Hitung Total Hari Kerja
  (exclude Sabtu & Minggu)
         │
         ▼
  ┌──────────────────────────┐
  │  Cek Kuota Tersedia?     │
  │  remaining >= total_hari │
  └──────────┬───────────────┘
             │
        ┌────┴────┐
        │ YES     │ NO
        ▼         ▼
  Cek Overlap   Return Error 422
  Tanggal       "Kuota tidak cukup"
        │
   ┌────┴────┐
   │ NO OVR  │ OVERLAP
   ▼         ▼
  Upload    Return Error 422
  File      "Tanggal bertabrakan"
  Lampiran
        │
        ▼
  Simpan Cuti
  Status: PENDING
        │
        ▼
  Return 201 Created
```

### 3. Alur Review Cuti (Admin)

```
Admin PATCH /api/admin/cuti/{id}/review
      │
      ▼
  Validasi action (approved/rejected)
      │
      ▼
  Cek status masih PENDING?
      │
  ┌───┴───┐
  │ YES   │ NO
  ▼       ▼
  Proses  Return Error 422
  Review  "Sudah diproses"
  │
  ├─── action = "approved"
  │         │
  │         ▼
  │    Update status → APPROVED
  │    Kurangi KuotaCuti.kuota_digunakan
  │    += total_hari
  │
  └─── action = "rejected"
            │
            ▼
       Update status → REJECTED
       (Kuota TIDAK berkurang)
            │
            ▼
       Simpan reviewed_by, review_note, reviewed_at
            │
            ▼
       Return 200 OK
```

### 4. Alur Manajemen Kuota

```
Awal Tahun / User Baru
        │
        ▼
  getOrCreateQuota()
  ┌─────────────────────┐
  │ kuota_cuti          │
  │ user_id: X          │
  │ tahun: 2026         │
  │ total_kuota: 12     │
  │ kuota_digunakan: 0  │
  └─────────────────────┘
        │
  Cuti Diajukan (pending) ──► Kuota BELUM berkurang
        │
  Cuti Disetujui (approved) ─► kuota_digunakan += total_hari
        │
  Cuti Ditolak (rejected) ──► Kuota TIDAK berubah
        │
  Sisa Kuota = total_kuota - kuota_digunakan
```

---

## 🚀 Panduan Instalasi

### Prasyarat

Pastikan sistem kamu sudah terinstall:

```
✅ PHP >= 8.2
✅ Composer >= 2.x
✅ MySQL >= 8.x
✅ Git
```

### Langkah Instalasi

**1. Clone Repository**

```bash
git clone https://github.com/muhilalr/api-manajemen-cuti-karyawan.git
cd api-manajemen-cuti-karyawan
```

**2. Install Dependencies**

```bash
composer install
```

**3. Copy File Environment**

```bash
cp .env.example .env
```

**4. Generate Application Key**

```bash
php artisan key:generate
```

**5. Buat Database**

**6. Konfigurasi `.env`** *(lihat bagian berikutnya)*

**7. Jalankan Migration & Seeder**

```bash
php artisan migrate --seed
```

**8. Buat Storage Link**

```bash
php artisan storage:link
```

**9. Jalankan Server**

```bash
php artisan serve
```

✅ Aplikasi berjalan di `http://localhost:8000`

---

## ⚙️ Konfigurasi `.env`

Buka file `.env` dan sesuaikan nilai-nilai berikut:

```env
# ─── Application ────────────────────────────────────────
APP_NAME="Sistem Manajemen Cuti"
APP_ENV=local
APP_KEY=                        # auto-generated oleh php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

# ─── Database ────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database       # sesuaikan dengan nama database kamu
DB_USERNAME=root                # sesuaikan dengan username MySQL kamu
DB_PASSWORD=                    # sesuaikan dengan password MySQL kamu

# ─── File Storage ────────────────────────────────────────
FILESYSTEM_DISK=public          # file lampiran disimpan di storage/app/public

# ─── Google OAuth ────────────────────────────────────────
GOOGLE_CLIENT_ID=xxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxx
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```

### Cara Mendapatkan Google OAuth Credentials

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Buat project baru → **APIs & Services** → **Credentials**
3. Buat **OAuth 2.0 Client ID** dengan tipe `Web application`
4. Tambahkan redirect URI: `http://localhost:8000/api/auth/google/callback`
5. Copy **Client ID** dan **Client Secret** ke `.env`

---

## ▶️ Menjalankan Aplikasi

```bash
# Development server
php artisan serve

# Jika ingin di port berbeda
php artisan serve --port=8080

# Clear cache jika ada perubahan config
php artisan config:clear
php artisan cache:clear
```

### Akun Default Setelah Seeder

| Role  | Email                | Password    |
|-------|----------------------|-------------|
| admin | admin@gmail.com      | password    |

> **Note:** Karyawan dapat mendaftar sendiri melalui endpoint `POST /api/auth/register`

---

## 📡 API Endpoints

### Base URL
```
http://localhost:8000/api
```

### Auth

| Method |        Endpoint         |     Akses     |            Deskripsi            |
|--------|-------------------------|---------------|---------------------------------|
| POST   | `/auth/register`        | Public        | Daftar sebagai karyawan         |
| POST   | `/auth/login`           | Public        | Login dengan email & password   |
| GET    | `/auth/google`          | Public        | Redirect URL untuk OAuth Google |
| GET    | `/auth/google/callback` | Public        | Callback OAuth Google           |
| GET    | `/auth/me`              | Authenticated | Data user yang sedang login     |
| POST   | `/auth/logout`          | Authenticated | Logout & hapus token            |

### Karyawan

> Header: `Authorization: Bearer {token}` | Role: `karyawan`

| Method |   Endpoint    |         Deskripsi          |
|--------|---------------|----------------------------|
| GET    | `/cuti/kuota` | Cek kuota cuti tahun ini   |
| GET    | `/cuti`       | Daftar pengajuan cuti saya |
| POST   | `/cuti`       | Ajukan cuti baru           |
| GET    | `/cuti/{id}`  | Detail pengajuan cuti      |

### Admin

> Header: `Authorization: Bearer {token}` | Role: `admin`

| Method |         Endpoint          |         Deskripsi           |
|--------|---------------------------|-----------------------------|
| GET    | `/admin/cuti`             | Semua pengajuan cuti        |
| GET    | `/admin/cuti/{id}`        | Detail pengajuan cuti       |
| PATCH  | `/admin/cuti/{id}/review` | Approve atau Reject         |
| GET    | `/admin/karyawan`         | List semua employee + kuota |

---

## 💼 Business Logic

### Kuota Cuti
- Setiap karyawan mendapat **12 hari cuti per tahun**
- Kuota **hanya berkurang** ketika cuti berstatus `approved`
- Cuti yang `rejected` atau `pending` **tidak mengurangi kuota**
- Kuota direset otomatis setiap tahun baru

### Perhitungan Hari
- Sistem menghitung **hari kerja saja** (Senin–Jumat)
- Sabtu dan Minggu **tidak dihitung** sebagai hari cuti

### Validasi Pengajuan
- `tanggal_mulai` tidak boleh kurang dari hari ini
- `tanggal_selesai` tidak boleh sebelum `tanggal_mulai`
- Tidak boleh ada **tanggal yang bertabrakan** dengan cuti pending/approved lain
- Kuota harus **mencukupi** jumlah hari yang diajukan
- Lampiran wajib disertakan (PDF, JPG, JPEG, PNG, max 2MB)

### Status Workflow

```
PENDING ──► APPROVED
    └──────► REJECTED
```

> Status tidak dapat diubah kembali setelah di-review oleh Admin.

---

## 📁 Struktur Direktori

```
api-sistem-cuti-karyawan/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php         # Login, Register, OAuth
│   │   │       ├── CutiController.php         # Karyawan: CRUD cuti
│   │   │       └── AdminCutiController.php    # Admin: review & monitoring
│   │   └── Request/
│   │       ├── CutiRequest.php                # Handle request cuti
|   |       ├── LoginRequest.php               # Handle request login
|   |       ├── RegisterRequest                # Handle request register
|   |       └── ReviewCutiRequest              # Handle request review
│   ├── Models/
│   │   ├── User.php                          # HasRoles, HasApiTokens
│   │   ├── Cuti.php                          # Model pengajuan cuti
│   │   └── KuotaCuti.php                     # Model kuota tahunan
│   └── Services/
│       └── CutiService.php                   # Business logic utama
├── database/
│   ├── migrations/                           # Struktur tabel database
│   └── seeders/
│       └── RoleAndAdminSeeder.php            # Seed roles & admin default
├── routes/
│   └── api.php                               # Definisi semua API routes
├── storage/
│   └── app/public/lampiran/                  # File lampiran tersimpan disini
├── .env.example                              # Template konfigurasi environment
└── README.md                                 # Dokumentasi ini
```

---

## 📮 Postman Documentation

Dokumentasi lengkap API tersedia di Postman:

🔗 **[Link Postman Documentation](#https://documenter.getpostman.com/view/37672093/2sBXqNmJPa)**

---

## 👨‍💻 Dibuat oleh

**Muhammad Hilal Ramadhan** — Backend Developer Intern Candidate  
Seleksi Magang Batch 1 2026 — SEAL Lab Singhasari
