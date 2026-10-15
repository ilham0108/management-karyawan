# Tambak Udang Waingapu

Aplikasi Laravel untuk mengelola data pegawai dan jabatan, termasuk foto profil, QR code, dan manajemen akun pengguna.

## Teknologi

- PHP 8.3+
- Laravel 13
- Laravel Fortify dan Jetstream untuk autentikasi dan pengelolaan profil
- MySQL
- Node.js dan npm untuk membangun aset frontend
- Gentelella untuk antarmuka aplikasi

## Menjalankan aplikasi secara lokal

Pastikan PHP, Composer, Node.js, dan npm sudah tersedia. Dari direktori project:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Select-String -Path .env -Pattern '^APP_KEY=.+')) { php artisan key:generate }
npm ci
npm run build
```

Sesuaikan konfigurasi `.env` sebelum migrasi. Contoh konfigurasi database MySQL:

```dotenv
APP_NAME="Sesuaikan Nama Aplikasi Anda"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tambak_udang_app
DB_USERNAME=root
DB_PASSWORD=

INITIAL_USER_NAME="Administrator"
INITIAL_USER_EMAIL=admin@example.com
INITIAL_USER_PASSWORD=
```

Isi `INITIAL_USER_PASSWORD` dengan password kuat milik Anda. Jangan menyimpan atau membagikan `.env`; file tersebut berisi rahasia dan kredensial.

Jalankan migrasi dan buat akun admin awal:

```powershell
php artisan migrate --seed
```

Kemudian jalankan server:

```powershell
php artisan serve
```

Buka URL yang ditampilkan oleh Artisan, biasanya `http://127.0.0.1:8000`.

### Akun administrator awal

Seeder membaca `INITIAL_USER_NAME`, `INITIAL_USER_EMAIL`, dan `INITIAL_USER_PASSWORD` dari `.env`. Seeder membuat akun sebagai `admin` dan menandainya terverifikasi. Jika email sudah terdaftar, seeder akan mengangkat role akun tersebut menjadi `admin` tanpa mengganti password yang sudah ada.

Untuk menjalankan seeder saja:

```powershell
php artisan db:seed --class=InitialUserSeeder
```

## Autentikasi

Autentikasi menggunakan Laravel Fortify dengan guard `web` dan login memakai alamat email.

| Method   | Fitur                                | URL                       |
| -------- | ------------------------------------ | ------------------------- |
| GET/POST | Login                                | `/login`                  |
| POST     | Logout                               | `/logout`                 |
| GET/POST | Registrasi                           | `/register`               |
| GET/POST | Meminta tautan reset password        | `/forgot-password`        |
| GET      | Form password baru dari tautan email | `/reset-password/{token}` |
| POST     | Menyimpan password baru              | `/reset-password`         |
| GET/POST | Tantangan autentikasi dua faktor     | `/two-factor-challenge`   |

Registrasi publik aktif. Akun baru mendapat role `user` secara default dan dapat login setelah mendaftar. Konfigurasi email harus disiapkan agar tautan reset password dapat dikirim. Pada konfigurasi `MAIL_MAILER=log`, email ditulis ke log aplikasi, bukan dikirim ke inbox.

Two-factor authentication dan passkeys diaktifkan melalui konfigurasi Fortify. Passkeys memerlukan browser/perangkat yang mendukung WebAuthn dan umumnya harus digunakan melalui HTTPS di lingkungan produksi. Fitur verifikasi email Fortify saat ini tidak diaktifkan.

Login dibatasi untuk mencegah percobaan password berulang. Pengaturan fitur, guard, broker password, serta rate limiter berada di `config/fortify.php`.

## Role dan hak akses

Aplikasi memiliki dua role:

| Role    | Hak akses                                                                                                      |
| ------- | -------------------------------------------------------------------------------------------------------------- |
| `admin` | Semua halaman pegawai dan jabatan yang tersedia, serta halaman dan aksi manajemen user.                        |
| `user`  | Halaman pegawai dan jabatan setelah login; tidak dapat membuka atau mengubah user lain melalui manajemen user. |

Dashboard, halaman pegawai, dan jabatan berada di balik middleware autentikasi. Route manajemen user juga memakai middleware `CekAuth:admin`; pembatasan ini berlaku di server, bukan hanya dengan menyembunyikan tautan navigasi.

Admin dapat membuka **Sistem → User** untuk menambah, mengubah nama/email/role, mengganti password, dan menghapus akun. Password baru minimal delapan karakter. Saat mengedit user, biarkan password kosong jika tidak ingin menggantinya. Email harus unik dan role hanya boleh `admin` atau `user`.

Akun admin yang sedang login tidak dapat menghapus dirinya sendiri atau mengubah role dirinya menjadi `user`. Untuk memberi role admin kepada akun yang sudah ada, ubah role dari halaman manajemen user menggunakan admin lain, atau jalankan seeder admin awal dengan email akun tersebut.

## Manajemen jabatan

Buka **Data Karyawan → Master Jabatan** (`/jabatan`).

- **Tambah:** pilih **Tambah Jabatan**, masukkan nama jabatan, lalu simpan.
- **Edit:** pilih **Edit** pada baris jabatan, ubah namanya, lalu simpan.
- **Hapus:** pilih **Hapus** dan konfirmasi. Jabatan yang masih dipakai pegawai tidak dapat dihapus; pindahkan pegawai ke jabatan lain terlebih dahulu.

Nama jabatan wajib diisi dan panjangnya maksimal 255 karakter.

## Manajemen pegawai

Buka **Data Karyawan → Data Karyawan** (`/pegawai`).

- **Tambah:** pilih **Tambah Pegawai**, isi nama, NPP, jabatan, status, dan opsional foto.
- **Edit:** pilih **Edit** pada baris pegawai. Foto yang sudah ada tetap dipakai bila tidak memilih foto pengganti.
- **Hapus:** pilih **Hapus** dan konfirmasi. Data foto dan QR milik pegawai ikut dibersihkan.
- Gunakan filter jabatan/status dan pencarian tabel untuk menemukan pegawai.
- **Export Excel (CSV)** mengunduh `pegawai.csv` dengan filter/pencarian yang sedang diterapkan.
- Kolom **Profile Photo** dan **QR Code** pada CSV berisi URL jika media tersedia; jika tidak tersedia, nilainya `Tidak tersedia`.
- **QR Code** membuka QR pegawai dan menyediakan unduhan. QR lama yang tidak tersedia akan dibuat ulang saat QR diminta.

Nama, NPP, dan jabatan wajib diisi. Status yang tersedia adalah `aktif` dan `tidak aktif`. Foto menerima JPG/JPEG, PNG, atau WebP dengan batas validasi aplikasi 5.048 KB (sekitar 5 MB). Mengubah nama, NPP, jabatan, atau status membuat QR lama dibuat ulang ketika QR diminta berikutnya.

### Foto pegawai pada server

Foto disimpan pada disk `public` Laravel dan disajikan melalui route foto pegawai. Jika Nginx membalas `413 Request Entity Too Large`, request ditolak Nginx sebelum mencapai Laravel. Sesuaikan `client_max_body_size` pada blok `server` virtual host, misalnya:

```nginx
server {
    client_max_body_size 10m;
    # Konfigurasi virtual host lainnya...
}
```

Periksa juga konfigurasi PHP-FPM untuk domain tersebut. `upload_max_filesize` harus lebih besar dari batas foto aplikasi dan `post_max_size` harus lebih besar dari ukuran total request. Setelah mengubah konfigurasi, uji Nginx dengan `sudo nginx -t`, lalu reload Nginx dan restart PHP-FPM jika konfigurasinya berubah.

## Halaman profil publik dan QR

URL Pegawai memakai token ID terenkripsi, bukan ID database dalam bentuk polos. Contohnya, route profil berbentuk `/pegawai/{token-terenkripsi}` dan foto `/pegawai/{token-terenkripsi}/foto`. Token yang tidak valid atau ID polos akan menghasilkan 404.

Halaman profil dan foto bersifat publik agar dapat dibuka dari QR tanpa login. Enkripsi ID menyamarkan ID database pada URL, tetapi bukan pengganti kontrol akses: siapa pun yang memiliki tautan masih dapat melihat nama, jabatan, NPP, status, dan foto profil.

QR mengarah ke halaman profil publik pegawai. Pertahankan nilai `APP_KEY` yang sama setelah aplikasi digunakan. Jika kunci berubah, datatable dan CSV menandai QR lama sebagai tidak tersedia; meminta QR kembali akan membuat QR baru. QR yang sudah dicetak atau dibagikan tidak dapat diperbarui otomatis dan perlu diganti dengan QR baru.

## Route aplikasi utama

| Method     | URL                              | Keterangan                            |
| ---------- | -------------------------------- | ------------------------------------- |
| GET        | `/dashboard`                     | Dashboard                             |
| GET/POST   | `/jabatan`                       | Daftar jabatan dan tambah jabatan     |
| PUT/DELETE | `/jabatan/{id}`                  | Edit atau hapus jabatan               |
| GET/POST   | `/pegawai`                       | Daftar pegawai dan tambah pegawai     |
| GET        | `/pegawai/data`                  | Data tabel pegawai                    |
| GET        | `/pegawai/export`                | Ekspor CSV pegawai                    |
| PUT/DELETE | `/pegawai/{token-terenkripsi}`                  | Edit atau hapus pegawai               |
| GET        | `/pegawai/{token-terenkripsi}`                  | Profil publik pegawai                 |
| GET        | `/pegawai/{token-terenkripsi}/foto`             | Foto publik pegawai                   |
| GET        | `/pegawai/{token-terenkripsi}/qr-code.png`      | Menampilkan QR pegawai                |
| GET        | `/pegawai/{token-terenkripsi}/qr-code/download` | Mengunduh QR pegawai                  |
| GET/POST   | `/user`                          | Daftar dan tambah user (khusus admin) |
| PUT/DELETE | `/user/{id}`                     | Edit atau hapus user (khusus admin)   |

Daftar route dapat diperiksa dengan:

```powershell
php artisan route:list --except-vendor
```

## Pengembangan dan pengujian

Build aset frontend:

```powershell
npm run dev
```

Untuk build produksi:

```powershell
npm run build
```

Sebelum deployment, siapkan `.env` produksi dengan `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS, database dan mail yang benar, serta password admin yang kuat. Jangan deploy `.env` ke repository. Build aset dan jalankan migrasi setelah konfigurasi server siap:

```powershell
npm ci
npm run build
php artisan migrate --force
```

Seeder admin dijalankan terpisah hanya jika memang diperlukan:

```powershell
php artisan db:seed --class=InitialUserSeeder --force
```

Pastikan direktori `storage` dan `bootstrap/cache` dapat ditulis oleh proses PHP. Pada lingkungan produksi, simpan foto dan backup database pada media penyimpanan yang dicadangkan secara teratur.

Jalankan test:

```powershell
php artisan test
```
