# Dokumentasi REST API

REST API Laravel untuk aplikasi mobile. Contoh respons sukses di bawah diambil dari pemanggilan nyata terhadap server lokal dengan data seeder.

Servis prediksi FastAPI punya dokumentasi Swagger sendiri di `http://<host-fastapi>/docs`. Aplikasi mobile tidak memanggil FastAPI secara langsung; foto dikirim lewat `POST /api/classify`.

## Ketentuan umum

| | |
|---|---|
| Base URL lokal | `http://127.0.0.1:8000/api` (dari HP: `http://<IP-LAN-laptop>:8000/api`, server dijalankan dengan `php artisan serve --host=0.0.0.0`) |
| Format | JSON. Upload foto memakai `multipart/form-data`. |
| Header yang disarankan | `Accept: application/json` |
| Autentikasi | Token Sanctum dari `register`/`login`, dikirim sebagai `Authorization: Bearer <token>` |
| Waktu | ISO 8601 dengan zona waktu WIB, contoh `2026-11-02T13:00:13+07:00` |
| Pesan | Bahasa Indonesia |

Semua respons sukses dibungkus dalam `data`. Daftar laporan menambahkan `links` dan `meta` untuk pagination.

### Format error

Error validasi (422):
```json
{
  "message": "Foto wajib diisi. (dan 3 kesalahan lainnya)",
  "errors": {
    "photo": ["Foto wajib diisi."],
    "latitude": ["Latitude harus bernilai antara -90 dan 90."],
    "longitude": ["Longitude harus bernilai antara -180 dan 180."],
    "description": ["Deskripsi wajib diisi."]
  }
}
```

Error lain hanya berisi `message`:

| Kode | Kapan | `message` |
|---|---|---|
| 401 | Token tidak ada, salah, atau sudah logout | `Sesi berakhir atau belum login. Silakan login kembali.` |
| 403 | Mengakses laporan milik user lain | `Anda tidak memiliki akses ke data ini.` |
| 404 | Data tidak ditemukan | `Data tidak ditemukan.` |
| 429 | Lebih dari 5 request per menit ke `register`/`login` (per IP, dihitung bersama) | `Terlalu banyak percobaan. Silakan coba lagi dalam satu menit.` |
| 502 | Servis ML menolak foto | `Foto tidak dapat diproses oleh layanan deteksi AI.` |
| 503 | Servis ML mati, timeout, atau error | `Layanan deteksi AI sedang tidak tersedia. Silakan pilih kategori secara manual.` |

### Nilai enum

| Field | Nilai |
|---|---|
| `status` | `pending` (Menunggu), `in_progress` (Diproses), `completed` (Selesai), `rejected` (Ditolak) |
| `category`, `ai_label` | `organic` (Organik), `plastic` (Plastik), `paper` (Kertas) |
| `role` | `admin`, `user` |

---

## Autentikasi

### POST /api/register

Mendaftarkan user baru dengan role `user` dan mengembalikan token. Field `role` di request diabaikan.

Body (form atau JSON):

| Field | Aturan |
|---|---|
| `name` | wajib, maks. 255 karakter |
| `email` | wajib, email valid, belum terdaftar |
| `phone` | opsional, maks. 20 karakter |
| `password` | wajib, min. 8 karakter |
| `password_confirmation` | wajib, sama dengan `password` |

Respons `201`:
```json
{
  "data": {
    "id": 6,
    "name": "Rahmat Hidayat",
    "email": "rahmat@smartwaste.test",
    "phone": "081390112233",
    "role": "user",
    "created_at": "2026-11-02T12:56:51+07:00"
  },
  "token": "2|Cf8xUAnT...78dacdc"
}
```

Respons `422`:
```json
{
  "message": "Nama wajib diisi. (dan 3 kesalahan lainnya)",
  "errors": {
    "name": ["Nama wajib diisi."],
    "email": ["Email sudah terdaftar."],
    "password": ["Password minimal 8 karakter.", "Konfirmasi password tidak cocok."]
  }
}
```

### POST /api/login

Body: `email` (wajib), `password` (wajib).

Respons `200`:
```json
{
  "data": {
    "id": 2,
    "name": "Budi Santoso",
    "email": "budi@smartwaste.test",
    "phone": "081325118842",
    "role": "user",
    "created_at": "2026-11-02T12:49:36+07:00"
  },
  "token": "3|tz6jlPIY...ce3e9ac"
}
```

Respons `422` (email atau password salah):
```json
{
  "message": "Email atau password salah.",
  "errors": { "email": ["Email atau password salah."] }
}
```

### POST /api/logout

Header: `Authorization: Bearer <token>`. Hanya mencabut token yang sedang dipakai.

Respons `200`:
```json
{ "message": "Berhasil logout." }
```

### GET /api/me

Header: `Authorization: Bearer <token>`.

Respons `200`: objek user seperti pada `login`, tanpa `token`.

---

## Dashboard

### GET /api/dashboard

Jumlah laporan milik user yang login, per status.

Respons `200`:
```json
{
  "data": {
    "total": 10,
    "pending": 3,
    "in_progress": 2,
    "completed": 3,
    "rejected": 2
  }
}
```

---

## Laporan

Semua endpoint laporan membutuhkan `Authorization: Bearer <token>`. User hanya bisa melihat laporannya sendiri.

### GET /api/reports

Riwayat laporan milik user, terbaru lebih dulu, 10 per halaman.

Query:

| Parameter | Keterangan |
|---|---|
| `status` | opsional, salah satu nilai `status`; nilai lain menghasilkan 422 |
| `page` | opsional, nomor halaman |

Respons `200` (dipotong ke satu item):
```json
{
  "data": [
    {
      "id": 41,
      "description": "Botol dan gelas plastik menumpuk di samping halte Terminal Mendolo.",
      "photo_url": "http://127.0.0.1:8000/storage/reports/vpTM6HPbatxtE0zMThpLERVBvmclozuZJdHCUAss.jpg",
      "latitude": -7.3605123,
      "longitude": 109.9026456,
      "address": null,
      "category": "plastic",
      "category_label": "Plastik",
      "ai_label": "plastic",
      "ai_confidence": 0.9893,
      "status": "pending",
      "status_label": "Menunggu",
      "admin_note": null,
      "created_at": "2026-11-02T13:00:13+07:00",
      "updated_at": "2026-11-02T13:00:13+07:00"
    }
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/reports?status=pending&page=1",
    "last": "http://127.0.0.1:8000/api/reports?status=pending&page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://127.0.0.1:8000/api/reports",
    "per_page": 10,
    "to": 4,
    "total": 4
  }
}
```

`meta` juga berisi `links` (daftar tautan halaman bawaan Laravel), dihilangkan dari contoh di atas supaya ringkas.

### POST /api/reports

Membuat laporan. Status awal selalu `pending`, dan satu entri riwayat status (`null` → `pending`) dicatat atas nama pelapor.

Body `multipart/form-data`:

| Field | Aturan |
|---|---|
| `photo` | wajib, file jpg/jpeg/png, maks. 5 MB |
| `latitude` | wajib, angka −90 sampai 90 |
| `longitude` | wajib, angka −180 sampai 180 |
| `description` | wajib, maks. 1000 karakter |
| `address` | opsional, alamat atau patokan lokasi, maks. 255 karakter |
| `category` | opsional, kategori final pilihan user |
| `ai_label` | opsional, hasil `POST /api/classify`; wajib jika `ai_confidence` diisi |
| `ai_confidence` | opsional, angka 0 sampai 1; wajib jika `ai_label` diisi |

Contoh:
```
curl -X POST http://127.0.0.1:8000/api/reports \
  -H "Authorization: Bearer <token>" -H "Accept: application/json" \
  -F photo=@sampah.jpg -F latitude=-7.3605123 -F longitude=109.9026456 \
  -F "description=Botol dan gelas plastik menumpuk di samping halte Terminal Mendolo." \
  -F category=plastic -F ai_label=plastic -F ai_confidence=0.9893
```

Respons `201`:
```json
{
  "data": {
    "id": 41,
    "description": "Botol dan gelas plastik menumpuk di samping halte Terminal Mendolo.",
    "photo_url": "http://127.0.0.1:8000/storage/reports/vpTM6HPbatxtE0zMThpLERVBvmclozuZJdHCUAss.jpg",
    "latitude": -7.3605123,
    "longitude": 109.9026456,
    "address": null,
    "category": "plastic",
    "category_label": "Plastik",
    "ai_label": "plastic",
    "ai_confidence": 0.9893,
    "status": "pending",
    "status_label": "Menunggu",
    "admin_note": null,
    "status_logs": [
      {
        "id": 85,
        "from_status": null,
        "from_status_label": null,
        "to_status": "pending",
        "to_status_label": "Menunggu",
        "note": null,
        "changed_by": "Budi Santoso",
        "created_at": "2026-11-02T13:00:13+07:00"
      }
    ],
    "created_at": "2026-11-02T13:00:13+07:00",
    "updated_at": "2026-11-02T13:00:13+07:00"
  }
}
```

Respons `422`: lihat [Format error](#format-error).

### GET /api/reports/{id}

Detail laporan beserta riwayat status (urut dari yang paling lama).

Respons `200` (laporan yang sudah selesai):
```json
{
  "data": {
    "id": 21,
    "description": "Batang pisang dan daun kering menumpuk di lahan kosong dekat permukiman.",
    "photo_url": "http://127.0.0.1:8000/storage/reports/seed/report-20.jpg",
    "latitude": -7.3027,
    "longitude": 109.9293,
    "address": "Jl. Dieng, Kec. Garung, Wonosobo",
    "category": "organic",
    "category_label": "Organik",
    "ai_label": "organic",
    "ai_confidence": 0.9523,
    "status": "completed",
    "status_label": "Selesai",
    "admin_note": "Lokasi sudah dibersihkan dan dipasang papan larangan membuang sampah.",
    "status_logs": [
      {
        "id": 34,
        "from_status": null,
        "from_status_label": null,
        "to_status": "pending",
        "to_status_label": "Menunggu",
        "note": null,
        "changed_by": "Budi Santoso",
        "created_at": "2026-10-02T08:53:00+07:00"
      },
      {
        "id": 35,
        "from_status": "pending",
        "from_status_label": "Menunggu",
        "to_status": "in_progress",
        "to_status_label": "Diproses",
        "note": "Petugas kebersihan sudah dijadwalkan untuk pengangkutan.",
        "changed_by": "Rina Wulandari",
        "created_at": "2026-10-03T08:53:00+07:00"
      },
      {
        "id": 36,
        "from_status": "in_progress",
        "from_status_label": "Diproses",
        "to_status": "completed",
        "to_status_label": "Selesai",
        "note": "Lokasi sudah dibersihkan dan dipasang papan larangan membuang sampah.",
        "changed_by": "Rina Wulandari",
        "created_at": "2026-10-04T08:53:00+07:00"
      }
    ],
    "created_at": "2026-10-02T08:53:00+07:00",
    "updated_at": "2026-10-04T08:53:00+07:00"
  }
}
```

`changed_by` berisi nama user yang mengubah status, atau `null` jika akun tersebut sudah dihapus.

Respons `403` (laporan milik user lain):
```json
{ "message": "Anda tidak memiliki akses ke data ini." }
```

Respons `404`:
```json
{ "message": "Data tidak ditemukan." }
```

---

## Deteksi AI

### POST /api/classify

Meneruskan foto ke servis prediksi FastAPI dan mengembalikan hasilnya. Foto tidak disimpan; foto baru disimpan saat `POST /api/reports`. Hasil ini adalah saran kategori; user tetap boleh memilih kategori lain sebelum mengirim laporan.

Header: `Authorization: Bearer <token>`. Body `multipart/form-data`: `photo` (wajib, jpg/jpeg/png, maks. 5 MB).

Respons `200`:
```json
{
  "data": {
    "label": "plastic",
    "confidence": 0.9893,
    "probabilities": { "organic": 0.0067, "plastic": 0.9893, "paper": 0.004 }
  }
}
```

Respons `422` (bukan gambar; servis ML tidak dipanggil):
```json
{
  "message": "Foto harus berupa file bertipe: jpg, jpeg, png.",
  "errors": { "photo": ["Foto harus berupa file bertipe: jpg, jpeg, png."] }
}
```

Respons `503` (servis ML mati, timeout, atau error):
```json
{ "message": "Layanan deteksi AI sedang tidak tersedia. Silakan pilih kategori secara manual." }
```

Respons `502` (servis ML menolak foto):
```json
{ "message": "Foto tidak dapat diproses oleh layanan deteksi AI." }
```

Konfigurasi di `web/.env`:

| Variabel | Default | Keterangan |
|---|---|---|
| `ML_SERVICE_URL` | `http://127.0.0.1:8001` | Alamat servis FastAPI |
| `ML_SERVICE_TIMEOUT` | `10` | Batas waktu respons dalam detik. Batas waktu koneksi tetap 3 detik. |
