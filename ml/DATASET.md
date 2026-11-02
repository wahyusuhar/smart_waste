# Dataset

## Sumber

| | |
|---|---|
| Nama | Garbage Classification (12 classes) |
| Pembuat | Mostafa Mohamed |
| URL | https://www.kaggle.com/datasets/mostafaabla/garbage-classification |
| Lisensi | Database: Open Database (ODbL), Contents: © Original Authors |
| Ukuran unduhan | ±250 MB (zip), 12 kelas, sekitar 15.000 gambar |

Menurut deskripsi di Kaggle, sebagian gambar berasal dari dataset Kaggle lain dan sisanya dikumpulkan dengan web scraping. Karena hak cipta tiap gambar tetap milik pemilik aslinya, gambar tidak disimpan di repository ini. Folder `ml/dataset/` di-ignore oleh git.

## Kelas yang dipakai

Dari 12 kelas, hanya tiga yang dipakai:

| Kelas di aplikasi | Folder di dataset |
|---|---|
| `organic` | `biological` |
| `plastic` | `plastic` |
| `paper` | `paper` |

## Cara menyiapkan

1. Login ke Kaggle, buka URL di atas, klik **Download**.
2. Ekstrak zip sehingga strukturnya menjadi:
   ```
   ml/dataset/garbage_classification/
   ├── biological/
   ├── plastic/
   ├── paper/
   └── ... (kelas lain boleh ada, tidak dibaca)
   ```
3. Jalankan `ml\.venv\Scripts\python ml\train.py`.

## Pemilihan dan pembagian data

Semua langkah di bawah dijalankan otomatis oleh `train.py` dengan seed 42, sehingga hasilnya sama setiap kali dijalankan.

1. File yang tidak bisa dibuka sebagai gambar dilewati.
2. Duplikat persis (hash SHA-1 sama) dibuang supaya gambar yang sama tidak masuk ke train dan test sekaligus. Gambar yang sama tetapi berada di dua kelas berbeda dibuang seluruhnya karena labelnya tidak bisa dipercaya.
3. Dari setiap kelas diambil acak 300 gambar.
4. Setiap kelas dibagi 70 / 15 / 15 (train / validation / test).

Daftar file per split tersimpan di [`reports/split.csv`](reports/split.csv).

## Jumlah gambar

Hasil run `train.py` (seed 42):

| Kelas | Gambar unik tersedia | Dipakai | Train | Validation | Test |
|---|---|---|---|---|---|
| organic | 985 | 300 | 210 | 45 | 45 |
| plastic | 865 | 300 | 210 | 45 | 45 |
| paper | 1050 | 300 | 210 | 45 | 45 |
| **Total** | 2900 | 900 | 630 | 135 | 135 |

Tidak ada file yang tidak terbaca dan tidak ditemukan duplikat persis di ketiga kelas ini.

## Keterbatasan

Sebagian besar gambar di dataset ini berisi satu benda dengan latar yang relatif bersih. Foto dari aplikasi biasanya berupa tumpukan sampah campuran di luar ruangan. Akurasi pada data test karena itu belum tentu sama dengan akurasi pada foto laporan sebenarnya, dan kategori dari model diperlakukan sebagai saran yang bisa dikoreksi oleh user dan admin.
