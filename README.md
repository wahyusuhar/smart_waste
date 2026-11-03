# Smart Waste Management System

Sistem pelaporan tumpukan sampah. Warga mengirim laporan (foto, lokasi GPS, deskripsi) lewat aplikasi Android. Admin memantau dan menindaklanjuti laporan lewat website. Setiap foto diklasifikasikan oleh model machine learning menjadi organik, plastik, atau kertas sebagai saran kategori.

Dibuat untuk technical test Fullstack Developer, Bilcode Technology.

| | |
|---|---|
| Demo website | https://smart-waste-web1.vercel.app |
| APK Android | https://github.com/wahyusuhar/smart_waste/releases/latest/download/smart-waste.apk |
| Video demo | [diisi nanti] |
| Dokumentasi API | [`docs/api.md`](docs/api.md) |
| API ML (Swagger) | https://smart-waste-ml.vercel.app/docs |
| Skema database | [`docs/erd.png`](docs/erd.png), [`docs/schema.sql`](docs/schema.sql) |
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
- Halaman pembuka dengan tiga langkah cara kerja (di aplikasi dan website), lalu register dan login. Halaman pembuka, Masuk, dan Daftar di website menampilkan link unduh APK.
- Pesan berhasil setelah membuat, mengubah, atau menghapus data, dan setelah mendaftar, tampil sebagai notifikasi singkat (toast).
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
├── docs/            # api.md, erd.png, erd.py, schema.sql, welcome_illustration.py
├── web/             # Laravel: website + REST API (Dockerfile, docker/ untuk deploy)
├── mobile/          # Expo React Native (eas.json untuk build APK)
├── ml/
│   ├── Dockerfile   # image servis prediksi
│   ├── train.py     # training dan evaluasi
│   ├── models/      # waste_classifier.keras, waste_classifier.tflite, labels.json
│   ├── reports/     # confusion matrix, classification report, grafik training
│   └── api/         # FastAPI
└── render.yaml      # Blueprint Render, tidak dipakai (lihat bagian Deploy)
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

Untuk mengisi ulang hanya laporan contoh tanpa menghapus akun pengguna (misalnya di database demo), jalankan `php artisan reports:reset-demo`. Perintah ini menghapus semua laporan, riwayat status, dan foto di folder `reports/` pada disk foto, termasuk laporan uji, lalu menjalankan ulang `ReportSeeder`. Sebelum menghapus, perintah menampilkan nama database dan meminta konfirmasi.

### 3. Aplikasi mobile (Expo)

Untuk sekadar mencoba, pasang APK dari link di bagian atas. APK itu terhubung ke server demo. Langkah di bawah untuk menjalankan aplikasi dari kode sumber.

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

Untuk mencoba dengan server demo tanpa menjalankan Laravel, isi `EXPO_PUBLIC_API_URL=https://smart-waste-web1.vercel.app/api`.

### 4. Training ulang model (opsional)

Kebutuhan: Python 3.11 dan dataset dari Kaggle (lihat [`ml/DATASET.md`](ml/DATASET.md)). Venv ini terpisah dari venv servis ML karena berisi TensorFlow penuh.

```
py -3.11 -m venv ml\.venv
ml\.venv\Scripts\python -m pip install -r ml\requirements.txt
ml\.venv\Scripts\python ml\train.py
```

Training memakai seed 42 sehingga hasilnya dapat diulang. Di CPU laptop, satu run memakan sekitar 7 menit. Output ditulis ke `ml/models/` dan `ml/reports/`.

## Akun demo

Semua akun memakai password `password`, dan berlaku di demo website maupun APK.

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
- Model diekspor ke `.keras` dan `.tflite`. Servis prediksi memakai `.tflite` dengan LiteRT, sehingga tidak perlu TensorFlow penuh dan image-nya kecil.

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

Demo berjalan di layanan gratis yang tidak meminta kartu kredit:

| Komponen | Layanan | Alamat |
|---|---|---|
| Laravel (website + REST API) | Vercel Hobby, container runtime, FrankenPHP (`web/Dockerfile`) | https://smart-waste-web1.vercel.app |
| FastAPI | Vercel Hobby, container runtime (`ml/Dockerfile`) | https://smart-waste-ml.vercel.app |
| Database | Aiven for MySQL, free tier (MySQL 8) | |
| Foto laporan | Cloudinary, free tier | |
| APK | EAS Build (profil `preview` di `mobile/eas.json`), dibagikan lewat GitHub Release | lihat tabel di atas |

**Kenapa Vercel.** Rencana awal memakai Render (lihat [`render.yaml`](render.yaml)), lalu Railway. Keduanya meminta kartu kredit, termasuk untuk layanan gratisnya, dan trial Railway sedang ditutup untuk akun baru. `render.yaml` dan dukungan Railway (env `HOST` di image ML, `MYSQL_CA_CERT_BASE64` di image web) tetap ada di repository sebagai dokumentasi, tetapi tidak dipakai.

**Langkah deploy, ringkas:**
1. Buat layanan MySQL di Aiven, catat host, port, user, dan password, lalu unduh sertifikat CA (`ca.pem`).
2. Buat akun Cloudinary dan salin `CLOUDINARY_URL`.
3. Isi tabel dan data contoh **sekali dari laptop**, dengan env Aiven dan Cloudinary di sesi PowerShell (env sesi mengalahkan `web/.env`):
   ```
   cd web
   php artisan config:clear
   $env:DB_HOST="..."; $env:DB_PORT="..."; $env:DB_DATABASE="defaultdb"; $env:DB_USERNAME="..."; $env:DB_PASSWORD="..."
   $env:MYSQL_ATTR_SSL_CA="C:\path\ke\ca.pem"; $env:PHOTO_DISK="cloudinary"; $env:CLOUDINARY_URL="cloudinary://..."
   php artisan photos:check-disk
   php artisan migrate --seed --force
   ```
   Container tidak menjalankan migrasi saat start, karena di Vercel container baru bisa dinyalakan kapan saja.
4. Project Vercel `smart-waste-ml`: Root Directory `ml`, env `ML_SERVICE_TOKEN`, Function Region Singapore (`sin1`).
5. Project Vercel `smart-waste-web`: Root Directory `web`, Function Region `sin1`, dan env:
   - `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `APP_LOCALE=id`, `LOG_CHANNEL=stderr`;
   - `DB_*` dari Aiven dan `MYSQL_CA_CERT_BASE64` (isi `ca.pem` dalam base64, ditulis ke `/tmp` saat container start);
   - `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=sync`;
   - `PHOTO_DISK=cloudinary`, `CLOUDINARY_URL`, `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=cloudinary`, `LIVEWIRE_TEMPORARY_FILE_UPLOAD_CLEANUP=false`;
   - `ML_SERVICE_URL`, `ML_SERVICE_TOKEN` (sama dengan servis ML), `ML_SERVICE_TIMEOUT=30`;
   - `MOBILE_APK_URL` (link APK di halaman Masuk dan Daftar).
6. APK: `cd mobile` lalu `eas build -p android --profile preview`, kemudian unggah file APK ke GitHub Release dengan nama `smart-waste.apk`.

Hal yang perlu diketahui saat mencoba demo:
- **Cold start.** Vercel menghentikan container setelah beberapa menit tanpa trafik. Request pertama setelahnya lebih lambat karena container dinyalakan ulang.
- **Deteksi AI pertama bisa lambat atau gagal.** Servis ML juga berhenti saat idle. Jika muncul "Layanan deteksi AI sedang tidak tersedia", tekan **Deteksi ulang** setelah beberapa detik, atau pilih kategori manual. Laravel juga memanggil `/health` servis ML setiap kali container web start, supaya servis ML ikut bangun.
- **Foto maksimal 4 MB.** Vercel membatasi body request 4,5 MB. Foto dari aplikasi mobile sudah dikompres sebelum dikirim, jadi batas ini hanya terasa di form website.
- **Upload sementara di form website** disimpan di Cloudinary (folder `livewire-tmp`), karena request berikutnya bisa ditangani container lain. Filenya dihapus setelah laporan disimpan; upload yang tidak jadi dikirim tertinggal dan bisa dihapus manual dari Cloudinary.
- **Adapter Cloudinary.** Paket `cloudinary-labs/cloudinary-laravel` gagal mengunggah isi file berupa string dan stream tanpa nama file, sehingga aplikasi memakai adapter sendiri (`web/app/Filesystem/CloudinaryAdapter.php`). `php artisan photos:check-disk` menguji koneksi ke disk foto yang sedang aktif.
- **Database bisa dimatikan.** Aiven dapat mematikan layanan free yang lama tidak dipakai; pemiliknya bisa menyalakannya kembali dari console.
- **Batas Vercel Hobby.** Untuk penggunaan personal dan non-komersial, dengan kuota bulanan. Container runtime Vercel masih berstatus beta.

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
| [`docs/schema.sql`](docs/schema.sql) | Struktur database tanpa data, diambil dari MySQL 8.4 di Aiven dengan `php artisan db:dump-structure` |
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

Perintah artisan pendukung, dijalankan dari folder `web`:

| Perintah | Fungsi |
|---|---|
| `php artisan photos:check-disk` | Mengunggah, membaca, dan menghapus file uji di disk foto yang aktif (`PHOTO_DISK`) |
| `php artisan db:dump-structure` | Menulis struktur tabel (tanpa data) dari koneksi MySQL yang aktif ke `docs/schema.sql` |

## Kredit

- Ilustrasi di halaman Welcome aplikasi mobile (`mobile/assets/welcome.png`) dibuat sendiri untuk proyek ini dengan [`docs/welcome_illustration.py`](docs/welcome_illustration.py) (Pillow), bukan dari sumber luar.
- Ikon menu sidebar dan kartu statistik website: [Lucide](https://lucide.dev) (lisensi ISC), disalin sebagai SVG inline di `web/resources/views/components/icon.blade.php`.
- Dataset: "Garbage Classification (12 classes)" oleh Mostafa Mohamed di Kaggle. Rinciannya di [`ml/DATASET.md`](ml/DATASET.md).
