@extends('gentelella::page')

@section('title', 'Panduan Penggunaan')
@section('page_key', 'docs')
@section('breadcrumb', 'Home > Panduan Penggunaan')

@section('content')
    <x-gentelella::page-header title="Panduan Penggunaan" pretitle="Dokumentasi" />

    <div class="row docs-page">
        <aside class="docs-page__sidebar">
            <x-gentelella::card title="Daftar Isi">
                <nav class="docs-page__navigation" aria-label="Daftar isi dokumentasi">
                    <ul>
                        <li><a href="#mulai">Mulai menggunakan aplikasi</a></li>
                        <li><a href="#akses">Login dan hak akses</a></li>
                        <li><a href="#jabatan">Mengelola jabatan</a></li>
                        <li><a href="#pegawai">Mengelola pegawai</a></li>
                        <li><a href="#profil-publik">Profil publik dan QR</a></li>
                        <li><a href="#route">Route utama</a></li>
                    </ul>
                </nav>
            </x-gentelella::card>
        </aside>

        <div class="docs-page__content">
            <section id="mulai">
                <x-gentelella::card title="Mulai menggunakan aplikasi">
                    <p>
                        Aplikasi Tambak Udang Waingapu digunakan untuk mengelola data pegawai, jabatan,
                        foto profil, QR code, dan akun pengguna.
                    </p>
                    <p>
                        Untuk menjalankan aplikasi secara lokal, pastikan PHP 8.3+, Composer, Node.js,
                        dan npm tersedia. Jalankan perintah berikut dari direktori project:
                    </p>
                    <pre><code>composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Select-String -Path .env -Pattern '^APP_KEY=.+')) { php artisan key:generate }
npm ci
npm run build</code></pre>
                    <p>
                        Sesuaikan konfigurasi database dan akun admin awal pada <code>.env</code>.
                        Isi <code>INITIAL_USER_NAME</code>, <code>INITIAL_USER_EMAIL</code>, dan
                        <code>INITIAL_USER_PASSWORD</code> dengan nilai milik Anda. Jangan membagikan
                        file <code>.env</code> karena berisi rahasia aplikasi.
                    </p>
                    <pre><code>php artisan migrate --seed
php artisan serve</code></pre>
                    <p>
                        Buka alamat lokal yang ditampilkan Artisan. Seeder membuat akun admin awal
                        dari variabel <code>INITIAL_USER_*</code>.
                    </p>
                </x-gentelella::card>
            </section>

            <section id="akses">
                <x-gentelella::card title="Login dan hak akses">
                    <p>Login menggunakan alamat email. Registrasi publik aktif; akun baru mendapat role <code>user</code>.</p>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Akses</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>admin</code></td>
                                    <td>Halaman pegawai dan jabatan, serta manajemen user.</td>
                                </tr>
                                <tr>
                                    <td><code>user</code></td>
                                    <td>Halaman pegawai dan jabatan; tidak dapat mengelola user lain.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p>
                        Admin dapat membuka menu <strong>Sistem → User</strong> untuk menambah,
                        mengubah, atau menghapus akun. Password baru minimal delapan karakter.
                        Saat mengedit user, biarkan password kosong jika tidak ingin menggantinya.
                        Admin yang sedang login tidak dapat menghapus akunnya sendiri atau mengubah
                        rolenya menjadi <code>user</code>.
                    </p>
                    <p>
                        Jika email untuk reset password belum dikonfigurasi, pada
                        <code>MAIL_MAILER=log</code> pesan reset disimpan di log aplikasi dan tidak
                        dikirim ke inbox.
                    </p>
                </x-gentelella::card>
            </section>

            <section id="jabatan">
                <x-gentelella::card title="Mengelola jabatan">
                    <p>
                        Buka <a href="{{ route('jabatan.index') }}">Data Pegawai → Master Jabatan</a>
                        untuk menambah, mengubah, atau menghapus jabatan.
                    </p>
                    <ul>
                        <li>Nama jabatan wajib diisi dan maksimal 255 karakter.</li>
                        <li>Jabatan yang masih digunakan pegawai tidak dapat dihapus.</li>
                        <li>Pindahkan pegawai ke jabatan lain sebelum menghapus jabatan tersebut.</li>
                    </ul>
                </x-gentelella::card>
            </section>

            <section id="pegawai">
                <x-gentelella::card title="Mengelola pegawai">
                    <p>
                        Buka <a href="{{ route('pegawai.index') }}">Data Pegawai → Data Pegawai</a>
                        untuk mengelola data pegawai.
                    </p>
                    <ul>
                        <li>Nama, NPP, jabatan, dan status wajib diisi.</li>
                        <li>Status yang tersedia adalah <code>aktif</code> dan <code>tidak aktif</code>.</li>
                        <li>Foto menerima JPG/JPEG, PNG, atau WebP dengan batas validasi sekitar 5 MB.</li>
                        <li>Foto yang sudah ada tetap digunakan jika tidak memilih foto pengganti saat edit.</li>
                        <li>Gunakan filter jabatan/status dan pencarian tabel untuk menemukan pegawai.</li>
                        <li>Export Excel (CSV) menggunakan filter dan pencarian yang sedang diterapkan.</li>
                        <li>Kolom foto dan QR pada CSV berisi URL jika tersedia, atau “Tidak tersedia”.</li>
                        <li>Mengubah nama, NPP, jabatan, atau status akan membuat QR diperbarui saat diminta berikutnya.</li>
                    </ul>
                    <p>
                        Menghapus pegawai juga membersihkan file foto dan QR miliknya. Jika Nginx
                        menolak unggahan besar dengan status <code>413</code>, administrator server
                        perlu menyesuaikan batas unggahan Nginx dan PHP-FPM.
                    </p>
                </x-gentelella::card>
            </section>

            <section id="profil-publik">
                <x-gentelella::card title="Profil publik dan QR">
                    <p>
                        QR code membuka halaman profil publik pegawai. Profil dan foto dapat diakses
                        tanpa login agar tautannya bisa dibuka dari hasil pemindaian QR.
                    </p>
                    <p>
                        URL profil menggunakan token ID terenkripsi, bukan ID database secara langsung.
                        Enkripsi menyamarkan ID tetapi bukan pengganti kontrol akses: siapa pun yang
                        memiliki tautan dapat melihat nama, jabatan, NPP, status, dan foto profil.
                    </p>
                    <p>
                        Pertahankan <code>APP_KEY</code> yang sama setelah aplikasi digunakan. Jika
                        kunci berubah, QR lama perlu dibuat ulang dan QR yang sudah dicetak atau
                        dibagikan perlu diganti.
                    </p>
                </x-gentelella::card>
            </section>

            <section id="route">
                <x-gentelella::card title="Route utama">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>URL</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>GET</td><td><code>/dashboard</code></td><td>Dashboard</td></tr>
                                <tr><td>GET</td><td><code>/docs</code></td><td>Panduan penggunaan</td></tr>
                                <tr><td>GET/POST</td><td><code>/jabatan</code></td><td>Daftar dan tambah jabatan</td></tr>
                                <tr><td>GET/POST</td><td><code>/pegawai</code></td><td>Daftar dan tambah pegawai</td></tr>
                                <tr><td>GET</td><td><code>/pegawai/data</code></td><td>Data tabel pegawai</td></tr>
                                <tr><td>GET</td><td><code>/pegawai/export</code></td><td>Ekspor CSV</td></tr>
                                <tr><td>GET/POST</td><td><code>/user</code></td><td>Manajemen user khusus admin</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p>Daftar route lengkap dapat diperiksa dengan:</p>
                    <pre><code>php artisan route:list --except-vendor</code></pre>
                </x-gentelella::card>
            </section>
        </div>
    </div>
@endsection