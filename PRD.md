# PRD — Smart Waste Management System

Technical test Fullstack Developer, Bilcode Technology.
Tenggat pengumpulan: **Jumat, 6 November 2026, 16.55 WIB**.

---

## 1. Ringkasan

Sistem untuk warga melaporkan tumpukan sampah (foto, lokasi GPS, deskripsi) lewat aplikasi mobile. Admin mengelola dan menindaklanjuti laporan lewat website. Foto laporan diklasifikasikan otomatis oleh model machine learning menjadi kategori sampah.

Tiga komponen:

1. **Website** (Laravel) untuk admin dan user. Sekaligus menjadi backend REST API untuk mobile.
2. **Aplikasi mobile** (React Native / Expo) untuk user.
3. **Machine learning**: model klasifikasi gambar dan servis prediksi (FastAPI).

## 2. Bobot penilaian (acuan prioritas)

| Aspek | Bobot |
|---|---|
| Website Development | 25% |
| Mobile Development | 20% |
| Machine Learning | 20% |
| Backend & API Integration | 15% |
| Code Quality & Documentation | 10% |
| Database Design | 5% |
| UI/UX | 5% |

Nilai minimum lulus 75/100. Fitur wajib harus selesai dan berfungsi sebelum ada tambahan apa pun.

## 3. Tech stack

| Bagian | Teknologi |
|---|---|
| Web + API | Laravel 12, Livewire, Tailwind CSS, Laravel Sanctum |
| Database | MySQL |
| Mobile | React Native dengan Expo (SDK stabil terbaru), TypeScript |
| ML | Python, TensorFlow/Keras, MobileNetV2 (transfer learning) |
| Servis prediksi | FastAPI + Uvicorn, runtime LiteRT (TFLite) |
| Version control | Git & GitHub, satu repo (monorepo) |

Versi pasti setiap paket dikonfirmasi ke pemilik proyek sebelum instalasi.

Versi yang disetujui pada Fase 0 (2 November 2026):

| Paket | Versi | Catatan |
|---|---|---|
| PHP | 8.2 (XAMPP) | Karena itu dipakai Laravel 12, bukan Laravel 13 yang butuh PHP ≥ 8.3 |
| Laravel | 12.x (skeleton `laravel/laravel` 12.12.2, framework 12.70.2) | |
| Livewire | 4.4.8 | |
| Laravel Sanctum | 4.3.3 | |
| Tailwind CSS | 4.x, mengikuti skeleton Laravel 12 | |
| Chart.js | 4.6.1 | |
| Database lokal | MariaDB 10.4 (XAMPP) | Demo memakai MySQL 8 (Aiven) |
| Expo SDK | 58 (`expo` 58.0.22) | |
| EAS CLI | 27.2.0 | dijalankan lewat `npx` |
| Python | 3.11 (venv) | Python 3.14 belum didukung TensorFlow |
| TensorFlow | 2.21.0 | hanya untuk training |
| FastAPI / Uvicorn | 0.162.0 / 0.54.0 | |
| ai-edge-litert | 2.3.0 | runtime TFLite untuk servis prediksi |

## 4. Struktur repo

```
smart-waste/
├── CLAUDE.md
├── PRD.md
├── README.md
├── docs/
│   ├── api.md              # dokumentasi API (atau koleksi Postman)
│   ├── erd.png             # ERD
│   └── schema.sql          # dump skema database
├── web/                    # Laravel (website + REST API)
├── mobile/                 # Expo React Native
└── ml/
    ├── dataset/            # tidak di-commit jika besar; sumber dicatat di ml/DATASET.md
    ├── notebooks/ atau train.py
    ├── models/             # model hasil training + labels.json
    ├── reports/            # confusion matrix, classification report, grafik training
    └── api/                # FastAPI
```

## 5. Peran pengguna

| Role | Akses |
|---|---|
| **Admin** | Website: dashboard statistik semua laporan, CRUD semua laporan, ubah status, manajemen pengguna. |
| **User** | Mobile: register, login, buat laporan, lihat riwayat dan status. Website: login, dashboard pribadi, lihat dan buat laporan miliknya sendiri. |

User tidak bisa melihat laporan milik user lain, tidak bisa mengubah status, dan tidak bisa mengakses manajemen pengguna. Pembatasan ini dicek di sisi server (policy/middleware), tidak hanya dengan menyembunyikan tombol.

**Pengecualian (4 November 2026, atas permintaan pemilik proyek):** halaman pembuka publik di `/` menampilkan maksimal 3 laporan terbaru berstatus **Selesai**, hanya berupa foto, kategori, alamat/patokan, tanggal selesai, catatan admin saat status diubah ke Selesai, waktu respon (selisih waktu dibuat dan waktu selesai), dan nomor laporan (`#SW-0021`). Kartu mengutamakan kategori yang berbeda. Nama pelapor, deskripsi, dan koordinat tidak ditampilkan dan tidak diambil dari database (`WelcomeController`). Halaman yang sama menampilkan jumlah laporan (total, selesai, diproses) dari database.

## 6. Kebutuhan fungsional

### 6.1 Website (Laravel + Livewire)

**Autentikasi**
- Login dengan email dan password.
- Redirect sesuai role setelah login.
- Logout.
- Registrasi user lewat web: lihat Keputusan Terbuka #3.

**Dashboard admin**
- Kartu angka: total laporan, jumlah per status (Pending, In Progress, Completed, Rejected).
- Jumlah laporan per kategori sampah (Organic, Plastic, Paper).
- Grafik jumlah laporan per hari atau per bulan.
- Daftar 5–10 laporan terbaru.

**Dashboard user (web)**
- Jumlah laporan miliknya per status.
- Daftar laporan terbaru miliknya.

**Manajemen laporan**
- Admin: tabel semua laporan dengan pagination, lihat detail, tambah, edit, hapus.
- User: tabel laporan miliknya, tambah, lihat detail. Boleh tidaknya user mengedit atau menghapus laporan sendiri: lihat Keputusan Terbuka #4.
- Detail laporan menampilkan foto, deskripsi, koordinat (dan peta jika disetujui, Keputusan Terbuka #6), hasil deteksi AI (label dan confidence), status, catatan admin, dan riwayat perubahan status.

**Filter dan pencarian**
- Pencarian teks pada deskripsi dan nama pelapor.
- Filter berdasarkan status, kategori, dan rentang tanggal.
- Filter dan pencarian bisa dikombinasikan dan tetap aktif saat berpindah halaman pagination.

**Update status**
- Admin mengubah status: Pending → In Progress → Completed, atau Rejected.
- Wajib atau tidaknya catatan saat Rejected: Keputusan Terbuka #5.
- Setiap perubahan status dicatat di tabel log (siapa, kapan, dari status apa, ke status apa, catatan).

**Manajemen pengguna (admin)**
- Daftar pengguna dengan pencarian.
- Tambah, edit (nama, email, role, reset password), hapus/nonaktifkan pengguna.
- Admin tidak bisa menghapus akunnya sendiri.

**Responsive**
- Semua halaman bisa dipakai di lebar 360px sampai desktop. Tabel di layar kecil boleh scroll horizontal di dalam kontainernya, halaman tidak boleh ikut scroll horizontal.

**Tambahan atas permintaan pemilik proyek (3 November 2026)**
- Halaman Masuk dan Daftar menampilkan link "Unduh aplikasi (APK)" ke file APK di GitHub Release. URL-nya diatur lewat env `MOBILE_APK_URL`; jika kosong, link tidak ditampilkan. APK tidak disimpan di web karena Vercel membatasi respons fungsi 4,5 MB.

### 6.2 REST API (Laravel, untuk mobile)

Semua respons JSON. Auth memakai token Sanctum (header `Authorization: Bearer <token>`).

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | `/api/register` | Register user baru, mengembalikan token |
| POST | `/api/login` | Login, mengembalikan token |
| POST | `/api/logout` | Mencabut token |
| GET | `/api/me` | Profil user login |
| GET | `/api/dashboard` | Ringkasan jumlah laporan user per status |
| GET | `/api/reports` | Riwayat laporan milik user (pagination, filter status) |
| POST | `/api/reports` | Buat laporan (multipart: foto, latitude, longitude, deskripsi, hasil AI) |
| GET | `/api/reports/{id}` | Detail laporan milik user, termasuk riwayat status |
| POST | `/api/classify` | Kirim foto, diteruskan ke servis ML, mengembalikan hasil prediksi (lihat Keputusan Terbuka #1) |

Ketentuan:
- Validasi lewat Form Request. Error validasi mengembalikan 422 dengan pesan per field.
- Format respons konsisten (API Resource).
- Foto: hanya jpg/jpeg/png, ukuran maksimal ditetapkan di Keputusan Terbuka #7.
- Koordinat divalidasi rentangnya (lat −90..90, lng −180..180).

### 6.3 Aplikasi mobile (Expo)

**Layar**
1. Login
2. Register
3. Dashboard: sapaan nama, jumlah laporan per status, tombol buat laporan, beberapa laporan terbaru.
4. Buat laporan:
   - Ambil foto dari kamera atau galeri.
   - Setelah foto dipilih, aplikasi memanggil deteksi AI dan menampilkan label + confidence.
   - Lokasi GPS diambil otomatis (dengan izin), ditampilkan sebagai koordinat.
   - Isi deskripsi.
   - Kirim. Tampilkan status loading dan pesan error yang jelas.
5. Riwayat laporan: daftar dengan badge status, pull-to-refresh.
6. Detail laporan: foto, deskripsi, lokasi, hasil AI, status, catatan admin, riwayat status.
7. Profil / logout.

**Ketentuan**
- Token disimpan di `expo-secure-store`.
- Jika izin kamera atau lokasi ditolak, tampilkan penjelasan dan jangan crash.
- Base URL API diatur lewat konfigurasi/env, tidak di-hardcode di banyak tempat.
- Hasil akhir: file APK (build EAS) dan/atau link demo Expo.

### 6.4 Machine learning

**Dataset**
- Minimal 3 kelas: `organic`, `plastic`, `paper`.
- Minimal 150 gambar total. Target sekitar 100+ gambar per kelas dan jumlah per kelas seimbang.
- Sumber dataset publik dicatat lengkap (nama, URL, lisensi) di `ml/DATASET.md`. Sumber yang dipakai: Keputusan Terbuka #8.
- Pembagian data train / validation / test (rasio di Keputusan Terbuka #9). Data test tidak dipakai saat training maupun tuning.

**Training**
- Transfer learning MobileNetV2 (pretrained ImageNet), input 224×224.
- Augmentasi ringan pada data train.
- Seed ditetapkan supaya hasil bisa direproduksi.
- Simpan model (`.keras`) dan `labels.json`.
- Ekspor model juga ke `.tflite`. Hasil prediksi `.tflite` dibandingkan dengan `.keras` pada data test untuk memastikan tidak ada penurunan akurasi.

**Evaluasi (pada data test)**
- Accuracy.
- Confusion matrix, disimpan sebagai gambar.
- Classification report (precision, recall, f1 per kelas), disimpan sebagai teks.
- Grafik akurasi dan loss training/validation.
- **Target: akurasi test ≥ 80%.** Angka yang dilaporkan harus hasil run sebenarnya.

**Servis prediksi (FastAPI)**
- `GET /health` untuk cek status.
- `POST /predict`: terima file gambar, kembalikan:
  ```json
  { "label": "plastic", "confidence": 0.93, "probabilities": { "organic": 0.02, "plastic": 0.93, "paper": 0.05 } }
  ```
- Validasi tipe file. Error dikembalikan dengan kode HTTP yang tepat.
- Model `.tflite` dimuat sekali saat startup dengan runtime LiteRT (`ai-edge-litert`), tidak di setiap request. Servis tidak bergantung pada TensorFlow penuh supaya muat di RAM 512 MB.
- Dokumentasi otomatis di `/docs` (Swagger bawaan FastAPI).

## 7. Desain database

### `users`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| name | varchar | |
| email | varchar unique | |
| phone | varchar nullable | |
| password | varchar | hash |
| role | enum(`admin`,`user`) | default `user` |
| timestamps | | |

### `reports`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | pelapor |
| description | text | |
| photo_path | varchar | |
| latitude | decimal(10,7) | |
| longitude | decimal(10,7) | |
| address | varchar nullable | |
| category | enum(`organic`,`plastic`,`paper`) nullable | kategori final |
| ai_label | varchar nullable | hasil model |
| ai_confidence | decimal(5,4) nullable | |
| status | enum(`pending`,`in_progress`,`completed`,`rejected`) | default `pending` |
| admin_note | text nullable | |
| timestamps | | |

Index: `status`, `category`, `user_id`, `created_at`.

### `report_status_logs`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| report_id | FK → reports | cascade delete |
| changed_by | FK → users, nullable | admin yang mengubah; untuk log awal (null → pending) diisi user pelapor. Null jika user tersebut sudah dihapus |
| from_status | enum nullable | null untuk status awal |
| to_status | enum | |
| note | text nullable | |
| created_at | timestamp | |

Tabel bawaan Laravel (`personal_access_tokens`, `sessions`, dll.) tetap dipakai sebagaimana adanya.

Perilaku foreign key saat data dihapus (diputuskan pada Fase 2, 2 November 2026):
- `reports.user_id`: cascade delete. Menghapus user ikut menghapus laporannya.
- `report_status_logs.changed_by`: nullable dengan `ON DELETE SET NULL`, supaya menghapus user (termasuk admin) tidak menghapus riwayat status laporan.

Seeder: 1 admin, beberapa user, dan sejumlah laporan contoh dengan status bervariasi supaya dashboard tidak kosong saat demo. Data contoh harus realistis (deskripsi wajar, koordinat di sekitar Wonosobo), tidak memakai lorem ipsum.

## 8. Arahan UI/UX

Tujuannya tampilan yang rapi dan fungsional, seperti aplikasi internal buatan tim yang serius, bukan template AI.

**Lakukan**
- Palet netral (putih, abu-abu, hitam) dengan satu warna aksen.
- Warna status konsisten di web dan mobile: Pending abu/kuning, In Progress biru, Completed hijau, Rejected merah. Warnanya kalem, tidak neon.
- Satu font sans-serif. Hierarki dibangun lewat ukuran dan ketebalan.
- Spasi konsisten. Border tipis atau shadow halus, pilih salah satu dan konsisten.
- Label dan tombol dalam Bahasa Indonesia yang wajar ("Buat Laporan", "Simpan", "Batal").
- Empty state, loading state, dan error state di setiap daftar dan form.

**Hindari**
- Gradien, glassmorphism, glow, blob dekoratif.
- Emoji di UI.
- Ikon berlebihan atau ikon di setiap judul.
- Teks pemasaran ("Revolutionize waste management with AI!"), hero section, atau tagline di dalam aplikasi.
- Kartu dengan border kiri berwarna tebal, sudut terlalu bulat di semua elemen, animasi yang tidak perlu.
- Lorem ipsum dan data palsu yang tidak masuk akal.

**Pengecualian yang disetujui (3 November 2026, aplikasi mobile)**
- Halaman pembuka (Welcome): bagian atas berlatar teal polos dengan satu ilustrasi (`mobile/assets/welcome.png`, dibuat sendiri dengan `docs/welcome_illustration.py`), bagian bawah kartu putih bersudut atas melengkung.
- Beranda: satu banner teal polos (radius 16) berisi ajakan "Ada tumpukan sampah?", tombol "Buat Laporan", dan ilustrasi yang sama dalam ukuran kecil. Isinya ajakan membuat laporan, bukan promo, dan tidak berupa carousel.
- Beranda: tiga kotak "Jenis sampah" memakai foto asli dari data contoh (`web/database/seeders/photos`), dan kartu "Laporan terbaru" memakai foto laporan.
- Ikon outline (Ionicons) di header Beranda (tombol profil) dan di langkah-langkah halaman Cara Kerja, selain di tab bar.
- Aturan lain tetap berlaku: tanpa gradien, emoji, teks pemasaran, atau banner promo.

**Pengecualian yang disetujui (4 November 2026, website)**
- Halaman pembuka untuk tamu di `/`: header lengket dengan menu jangkar (Beranda, Cara Kerja, Laporan, Jenis Sampah), foto laporan Selesai terbaru sebagai foto utama (ilustrasi `welcome.png` jika belum ada), judul, tiga langkah, tombol Masuk dan Daftar, kartu jumlah laporan (angka nyata), laporan Selesai terbaru (lihat pengecualian PRD §5), foto jenis sampah, satu blok teal polos berisi ajakan, footer empat kolom, dan link APK. Kartu memakai sudut 16 px dan bayangan halus. Tanpa foto stok, angka karangan, klaim instansi pemerintah, atau label versi.
- Satu-satunya gradien: bayangan gelap tipis di bagian bawah foto utama halaman pembuka, hanya agar teks putih di atas foto tetap terbaca.
- Halaman Masuk dan Daftar: blok teal polos dengan ilustrasi `welcome.png` (di atas pada HP, di kiri pada desktop) dan kartu form. Setelah keluar, pengguna diarahkan ke halaman pembuka, bukan ke halaman Masuk.
- Ikon outline Lucide (SVG inline lewat komponen `x-icon`, tanpa paket) **hanya** di menu sidebar, di kartu statistik (dashboard dan halaman pembuka), di halaman pembuka (logo, menu, tombol, label foto, lokasi), serta di field dan tombol halaman Masuk dan Daftar (termasuk tombol tampilkan/sembunyikan password). Kotak ikon kartu statistik memakai warna status yang sama dengan badge.
- Pesan berhasil (buat, ubah, hapus, pendaftaran) tampil sebagai toast. Toast memakai border tipis ditambah bayangan halus karena melayang di atas konten; komponen lain tetap hanya memakai border.
- Aturan lain tetap berlaku: tanpa gradien (kecuali yang disebut di atas), glassmorphism, emoji, teks pemasaran, atau banner promo; satu warna aksen teal.

## 9. Deliverables

| # | Item | Lokasi |
|---|---|---|
| 1 | GitHub repository | — |
| 2 | Link demo website | README |
| 3 | APK / link demo mobile | README / GitHub Release |
| 4 | Dokumentasi API | `docs/api.md` + Swagger FastAPI |
| 5 | Skema database / ERD | `docs/erd.png`, `docs/schema.sql` |
| 6 | Source code + model ML | `ml/` |
| 7 | Dataset + sumbernya | `ml/DATASET.md` |
| 8 | Hasil evaluasi model | `ml/reports/` |
| 9 | README panduan instalasi dan penggunaan | `README.md` |
| 10 | Video demo maks. 10 menit | dibuat pemilik proyek, link di README |

## 10. Fase pengerjaan

Setiap fase diakhiri dengan berhenti, ringkasan, dan menunggu persetujuan sebelum fase berikutnya.

| Fase | Isi | Target |
|---|---|---|
| 0 | Konfirmasi Keputusan Terbuka, inisialisasi repo dan struktur folder | Sen 2 Nov |
| 1 | ML: dataset, training, evaluasi, FastAPI `/predict` | Sen 2 Nov |
| 2 | Laravel: migrasi, model, seeder, auth + role, REST API + endpoint `/api/classify` | Sel 3 Nov |
| 3 | Website: dashboard, CRUD laporan, filter/pencarian, update status + log, manajemen pengguna, responsive | Rab 4 Nov |
| 4 | Mobile: semua layar, integrasi API dan deteksi AI, build APK | Kam 5 Nov |
| 5 | Dokumentasi: README, API docs, ERD, schema.sql, cek akhir | Jum 6 Nov pagi |
| 6 | Deploy demo (dikerjakan pemilik proyek, Claude membantu menyiapkan konfigurasi) | Jum 6 Nov pagi |

## 11. Keputusan terbuka

Semua poin di bawah sudah diputuskan pemilik proyek pada Fase 0 (2 November 2026).

| # | Pertanyaan | Keputusan final |
|---|---|---|
| 1 | Mobile memanggil servis ML lewat Laravel (`/api/classify`) atau langsung ke FastAPI? | Lewat Laravel (`/api/classify`). Mobile hanya memakai satu base URL dan token Sanctum; FastAPI tidak diakses langsung oleh mobile. |
| 2 | Hasil AI langsung menjadi `category`, atau user/admin boleh mengoreksi? | AI mengisi saran kategori, user boleh menggantinya sebelum kirim, admin boleh mengoreksi. `ai_label` dan `ai_confidence` menyimpan hasil asli model, `category` menyimpan kategori final. |
| 3 | User boleh register lewat web, atau hanya lewat mobile? | Boleh lewat web dan mobile. |
| 4 | User boleh edit/hapus laporan sendiri? | Hanya selama status masih Pending, dicek di Policy. |
| 5 | Catatan admin wajib saat status Rejected? | Wajib. Untuk status lain catatan opsional. |
| 6 | Tampilkan peta (Leaflet + OpenStreetMap) di detail laporan web? | Ya, dikerjakan setelah semua fitur wajib selesai. Sebelum itu koordinat ditampilkan sebagai teks. |
| 7 | Ukuran maksimal foto upload? | 4 MB (divalidasi di server), foto dikompres di mobile sebelum upload. Diturunkan dari 5 MB pada 3 November 2026 karena Vercel membatasi body request 4,5 MB. Servis FastAPI tetap menerima sampai 5 MB. |
| 8 | Sumber dataset yang dipakai? | Kaggle "Garbage Classification" 12 kelas (Mostafa Mohamed). Dipakai kelas `biological` (dipetakan ke `organic`), `plastic`, dan `paper`. Lisensi diverifikasi dan dicatat di `ml/DATASET.md`. |
| 9 | Rasio split data? | 70 / 15 / 15 (train / validation / test). |
| 10 | Laporan punya kolom judul atau cukup deskripsi? | Cukup deskripsi; kolom `title` tidak dipakai. |
| 11 | Tempat deploy web, FastAPI, dan database demo? | Harus gratis. MySQL di Aiven free tier; Laravel dan FastAPI di Render free (Docker). Karena RAM Render free 512 MB, model juga diekspor ke TFLite dan FastAPI memakai runtime TFLite, bukan TensorFlow penuh. Kode tanpa asumsi hosting (semua lewat `.env`). Penyimpanan foto (disk Render tidak permanen) dibahas di Fase 6. |
| 12 | Library grafik di dashboard web? | Chart.js. |
| 13 | Library UI mobile atau komponen sendiri? | Komponen sendiri dengan StyleSheet, tanpa UI kit. |

## 12. Di luar cakupan

Notifikasi push, chat, peta interaktif di mobile, multi-bahasa, dan reset password via email. Semua ini tidak dikerjakan kecuali pemilik proyek memintanya setelah fitur wajib selesai.
