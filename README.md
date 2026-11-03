# Smart Waste Management System

Sistem pelaporan tumpukan sampah. Warga mengirim laporan (foto, lokasi GPS, deskripsi) lewat aplikasi Android. Admin memantau dan menindaklanjuti laporan lewat website. Setiap foto diklasifikasikan oleh model machine learning menjadi organik, plastik, atau kertas sebagai saran kategori.

Dibuat untuk technical test Fullstack Developer, Bilcode Technology.

| | |
|---|---|
| Demo website | [diisi nanti] |
| APK Android | [diisi nanti] |
| Video demo | [diisi nanti] |
| Dokumentasi API | [`docs/api.md`](docs/api.md), Swagger FastAPI di `/docs` |
| Skema database | [`docs/erd.png`](docs/erd.png) |
| Dataset | [`ml/DATASET.md`](ml/DATASET.md) |
| Hasil evaluasi model | [`ml/reports/`](ml/reports/) |

## Daftar isi

1. [Fitur](#fitur)
2. [Arsitektur](#arsitektur)
3. [Struktur repository](#struktur-repository)
4. [Instalasi lokal](#instalasi-lokal)
5. [Akun demo](#akun-demo)
6. [Model machine learning](#model-machine-learning)
7. [Keterbatasan model](#keterbatasan-model)
8. [Deploy](#deploy)
9. [Testing](#testing)
10. [Dokumentasi](#dokumentasi)
11. [Kredit](#kredit)

## Fitur

**Admin (website)**
- Dashboard: jumlah laporan per status, grafik laporan per hari (30 hari terakhir), jumlah per kategori, laporan terbaru.
- Daftar laporan dengan pencarian, filter status, kategori, dan rentang tanggal.
- Detail laporan: foto, peta lokasi (Leaflet + OpenStreetMap), hasil deteksi AI, riwayat status.
- Ubah status (Menunggu → Diproses → Selesai, atau Ditolak). Catatan wajib saat menolak. Setiap perubahan dicatat di riwayat status.
- Ubah dan hapus laporan, koreksi kategori.
- Manajemen pengguna: tambah, ubah, hapus, cari.

**User (aplikasi Android dan website)**
- Register dan login.
- Buat laporan: foto dari kamera atau galeri, deteksi kategori otomatis yang bisa diganti, lokasi GPS, deskripsi, alamat/patokan opsional.
- Riwayat dan detail laporan miliknya, termasuk status, catatan admin, dan riwayat status.
- Ubah atau hapus laporan sendiri selama statusnya masih Menunggu (website).

User hanya bisa mengakses laporannya sendiri. Pembatasan ini dicek di server lewat Policy, tidak hanya dengan menyembunyikan tombol.

## Arsitektur

```
 Aplikasi Android (Expo)            Browser
   React Native + TypeScript          Livewire + Tailwind
            |                               |
            |  REST API, token Sanctum      |  sesi web
            v                               v
 +------------------------------------------------------+
 |  Laravel 12  (website + REST API)                    |
 |  - autentikasi, Policy, validasi, riwayat status     |
 |  - meneruskan foto ke servis ML (/api/classify)      |
 +------------------------------------------------------+
        |                    |                    |
        | Eloquent           | HTTP + X-ML-Token  | Storage
        v                    v                    v
    MySQL / MariaDB     FastAPI + LiteRT      Foto laporan
                        (model .tflite,       (lokal: storage/app/public,
                         MobileNetV2)          production: Cloudinary)
```

- Aplikasi mobile hanya berbicara dengan Laravel. Servis ML tidak dipanggil langsung oleh mobile, sehingga mobile cukup memakai satu base URL dan satu token.
- Jika servis ML tidak bisa dihubungi, `/api/classify` mengembalikan 503 dengan pesan yang jelas, dan laporan tetap bisa dikirim dengan kategori yang dipilih manual.
- `category` menyimpan kategori final (boleh dikoreksi user dan admin). `ai_label` dan `ai_confidence` menyimpan hasil asli model.

| Bagian | Teknologi |
|---|---|
| Web + API | Laravel 12, PHP 8.2, Livewire 4, Tailwind CSS 4, Laravel Sanctum, Chart.js, Leaflet |
| Database | MySQL 8 (demo, Aiven), MariaDB 10.4 (lokal, XAMPP) |
| Mobile | Expo SDK 58, React Native, TypeScript, Expo Router |
| ML training | Python 3.11, TensorFlow 2.21, MobileNetV2 |
| Servis prediksi | FastAPI, Uvicorn, LiteRT (`ai-edge-litert`) |

## Struktur repository

```
smart-waste/
├── docs/            # api.md, erd.png, erd.py
├── web/             # Laravel: website + REST API
├── mobile/          # Expo React Native
├── ml/
│   ├── train.py     # training dan evaluasi
│   ├── models/      # waste_classifier.keras, waste_classifier.tflite, labels.json
│   ├── reports/     # confusion matrix, classification report, grafik training
│   └── api/         # FastAPI
└── render.yaml      # Blueprint deploy Render
```

## Instalasi lokal

Urutan yang disarankan: servis ML, lalu web, lalu mobile. Perintah di bawah ditulis untuk Windows (PowerShell). Di macOS/Linux, ganti `.venv\Scripts\python` dengan `.venv/bin/python`.

### 1. Servis ML (FastAPI)

Kebutuhan: Python 3.11.

```
py -3.11 -m venv ml\api\.venv
ml\api\.venv\Scripts\python -m pip install -r ml\api\requirements-dev.txt
ml\api\.venv\Scripts\python -m uvicorn main:app --app-dir ml\api --port 8001
```

Cek `http://127.0.0.1:8001/health` (harus `"model_loaded": true`). Swagger ada di `http://127.0.0.1:8001/docs`. Model `.tflite` sudah ada di repository, jadi training tidak wajib untuk menjalankan aplikasi.

Env opsional: `MODEL_PATH`, `LABELS_PATH`, dan `ML_SERVICE_TOKEN` (jika diisi, `/predict` hanya menerima request dengan header `X-ML-Token` yang sama).

### 2. Website dan API (Laravel)

Kebutuhan: PHP 8.2 dengan ekstensi `pdo_mysql`, `gd`, dan `fileinfo`; Composer; Node.js 20.19 atau lebih baru (dipakai Vite 7); MySQL 8 atau MariaDB 10.4.

1. Buat database kosong `smart_waste` (collation `utf8mb4_unicode_ci`).
2. Siapkan aplikasi:
   ```
   cd web
   composer install
   copy .env.example .env
   php artisan key:generate
   ```
3. Sesuaikan `.env` jika perlu: `DB_*`, dan `ML_SERVICE_URL` (default `http://127.0.0.1:8001`).
4. Buat tabel, isi data contoh, dan siapkan asset:
   ```
   php artisan migrate --seed
   php artisan storage:link
   npm install
   npm run build
   php artisan serve
   ```
5. Buka `http://127.0.0.1:8000`.

Data contoh berisi 1 admin, 4 user, dan 40 laporan di sekitar Wonosobo dengan status bervariasi. Fotonya diambil dari `web/database/seeders/photos/`. Nama file berawalan `organic`, `plastic`, atau `paper` dipasangkan ke laporan dengan kategori yang sama.

### 3. Aplikasi mobile (Expo)

Kebutuhan: Node.js 20.19 atau lebih baru, aplikasi Expo Go (SDK 58) di HP Android, HP dan laptop di jaringan Wi-Fi yang sama.

1. Jalankan Laravel agar bisa diakses dari HP:
   ```
   cd web
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   Isi `APP_URL` di `web/.env` dengan IP LAN laptop (misalnya `http://192.168.1.5:8000`) agar URL foto bisa dibuka dari HP. Jika HP tidak bisa membuka `http://<ip-laptop>:8000/up`, izinkan port 8000 dan 8081 di firewall.
2. Siapkan aplikasi:
   ```
   cd mobile
   npm install
   copy .env.example .env
   ```
3. Isi `EXPO_PUBLIC_API_URL` di `mobile/.env`, misalnya `http://192.168.1.5:8000/api` (lengkap dengan port).
4. Jalankan `npx expo start`, lalu scan QR code dengan Expo Go. Setelah mengubah `.env`, jalankan ulang dengan `npx expo start --clear`.

### 4. Training ulang model (opsional)

Kebutuhan: Python 3.11 dan dataset dari Kaggle (lihat [`ml/DATASET.md`](ml/DATASET.md)). Venv ini terpisah dari venv servis ML karena berisi TensorFlow penuh.

```
py -3.11 -m venv ml\.venv
ml\.venv\Scripts\python -m pip install -r ml\requirements.txt
ml\.venv\Scripts\python ml\train.py
```

Training memakai seed 42 sehingga hasilnya dapat diulang. Di CPU laptop, satu run memakan sekitar 7 menit. Output ditulis ke `ml/models/` dan `ml/reports/`.

## Akun demo

Semua akun memakai password `password`.

| Email | Peran |
|---|---|
| `admin@smartwaste.test` | Admin |
| `budi@smartwaste.test` | User |
| `siti@smartwaste.test` | User |
| `agus@smartwaste.test` | User |
| `dewi@smartwaste.test` | User |

## Model machine learning

- Transfer learning MobileNetV2 (pretrained ImageNet), input 224×224, augmentasi ringan pada data train.
- Dataset: Kaggle "Garbage Classification" (12 kelas). Dipakai kelas `biological` (sebagai `organic`), `plastic`, dan `paper`, masing-masing 300 gambar, dibagi 70/15/15. Detail di [`ml/DATASET.md`](ml/DATASET.md).
- Model diekspor ke `.keras` dan `.tflite`. Servis prediksi memakai `.tflite` dengan LiteRT supaya tidak perlu TensorFlow penuh dan muat di RAM 512 MB.

Hasil pada data test (135 gambar, tidak dipakai saat training maupun tuning):

| | |
|---|---|
| Akurasi `.keras` | 97,04% |
| Akurasi `.tflite` | 97,04% |
| Prediksi `.keras` dan `.tflite` yang sama | 100% |

| Kelas | Precision | Recall | F1 |
|---|---|---|---|
| organic | 1,0000 | 1,0000 | 1,0000 |
| plastic | 0,9767 | 0,9333 | 0,9545 |
| paper | 0,9362 | 0,9778 | 0,9565 |

Kesalahan yang terjadi: 3 gambar plastik diprediksi kertas, dan 1 gambar kertas diprediksi plastik. Confusion matrix, classification report, dan grafik training ada di [`ml/reports/`](ml/reports/).

## Keterbatasan model

Angka 97% di atas adalah akurasi pada data test dari dataset yang sama. Akurasi pada foto laporan sungguhan lebih rendah, karena tiga hal berikut.

1. **Domain shift.** Dataset berisi foto satu benda dengan latar yang relatif bersih. Foto laporan biasanya berupa tumpukan sampah campuran di luar ruangan, dengan pencahayaan dan latar yang berbeda.
2. **Closed-set.** Model hanya mengenal tiga kelas dan tidak bisa menjawab "bukan sampah". Gambar apa pun akan dipaksa masuk ke organik, plastik, atau kertas. Contoh nyata: gambar confusion matrix diprediksi `paper` dengan confidence 0,9869.
3. **Confidence bukan kepastian.** Contoh nyata: foto kantong kresek hitam di lantai diprediksi "Kertas" dengan confidence 93,8%. Nilai confidence adalah output softmax, yaitu seberapa yakin model di antara tiga pilihan yang dikenalnya, bukan peluang bahwa jawabannya benar.

Karena itu, hasil model diperlakukan sebagai **saran**, tidak langsung menjadi kategori final:
- Di form laporan, kategori terisi otomatis dari hasil deteksi, tetapi user bisa menggantinya sebelum mengirim.
- Admin bisa mengoreksi kategori kapan saja.
- `ai_label` dan `ai_confidence` tetap menyimpan hasil asli model, sehingga koreksi bisa dilihat di halaman detail dan dipakai untuk mengevaluasi model pada data nyata.

## Deploy

Demo berjalan di layanan gratis:

| Komponen | Layanan |
|---|---|
| Laravel | Render free, Docker (`web/Dockerfile`) |
| FastAPI | Render free, Docker (`ml/api/Dockerfile`) |
| Database | Aiven for MySQL, free tier |
| Foto laporan | Cloudinary, free tier |

Kedua service didefinisikan di [`render.yaml`](render.yaml). Nilai rahasia (`APP_KEY`, kredensial database, `CLOUDINARY_URL`, `ML_SERVICE_TOKEN`) diisi di dashboard Render, dan sertifikat CA Aiven diunggah sebagai Secret File `ca.pem`. Saat container start, migrasi dijalankan otomatis. Seeder hanya dijalankan jika database masih kosong.

Hal yang perlu diketahui saat mencoba demo:
- **Cold start.** Service Render free tidur setelah 15 menit tanpa trafik, dan butuh sekitar satu menit untuk bangun lagi. Request pertama ke website bisa lambat.
- **Deteksi AI pertama bisa gagal.** Servis ML tidur terpisah dari Laravel. Jika sedang tidur, deteksi pertama bisa menampilkan "Layanan deteksi AI sedang tidak tersedia". Tekan **Deteksi ulang** setelah beberapa detik, atau pilih kategori manual. Laravel juga membangunkan servis ML setiap kali Laravel sendiri start.
- **Database bisa dimatikan.** Aiven dapat mematikan layanan free yang lama tidak dipakai, dan pemiliknya bisa menyalakannya kembali dari console.
- **Penyimpanan foto.** Disk Render free terhapus setiap restart, sehingga foto disimpan di Cloudinary (`PHOTO_DISK=cloudinary`). Secara lokal, foto tetap di `storage/app/public`.

## Testing

| Bagian | Perintah | Isi |
|---|---|---|
| Laravel | `cd web` lalu `php artisan test` | Feature test API dan website (SQLite in-memory, servis ML dipalsukan) |
| Gaya kode PHP | `cd web` lalu `php vendor\bin\pint --test` | Laravel Pint, tanpa mengubah file |
| FastAPI | `ml\api\.venv\Scripts\python -m pytest ml\api` | `/health`, `/predict`, validasi file, token |
| Mobile | `cd mobile` lalu `npx tsc --noEmit`, `npx expo lint`, `npx jest` | Typecheck, lint, dan unit test klien API |

## Dokumentasi

| File | Isi |
|---|---|
| [`docs/api.md`](docs/api.md) | Semua endpoint REST API: method, URL, header, body, contoh respons sukses dan error |
| [`docs/erd.png`](docs/erd.png) | Diagram relasi tabel |
| [`docs/erd.py`](docs/erd.py) | Skrip pembuat `erd.png` |
| [`docs/welcome_illustration.py`](docs/welcome_illustration.py) | Skrip pembuat ilustrasi halaman Welcome (`mobile/assets/welcome.png`) |
| [`ml/DATASET.md`](ml/DATASET.md) | Sumber, lisensi, dan pembagian dataset |

`erd.png` digambar dengan matplotlib dari daftar tabel di `docs/erd.py`. Daftar itu disalin dari `web/database/migrations`, jadi perlu diperbarui jika migrasi berubah. Untuk membuat ulang gambarnya, jalankan dari root repository dengan venv training, yang sudah berisi matplotlib:

```
ml\.venv\Scripts\python docs\erd.py
```

Ilustrasi Welcome dibuat ulang dengan cara yang sama (butuh Pillow, juga sudah ada di venv training):

```
ml\.venv\Scripts\python docs\welcome_illustration.py
```

## Kredit

- Ilustrasi di halaman Welcome aplikasi mobile (`mobile/assets/welcome.png`) dibuat sendiri untuk proyek ini dengan [`docs/welcome_illustration.py`](docs/welcome_illustration.py) (Pillow), bukan dari sumber luar.
- Dataset: "Garbage Classification (12 classes)" oleh Mostafa Mohamed di Kaggle. Rinciannya di [`ml/DATASET.md`](ml/DATASET.md).
