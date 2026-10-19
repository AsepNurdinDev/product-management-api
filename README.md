Product Management API
REST API untuk mengelola kategori dan produk menggunakan Laravel, Laravel Sanctum, Form Request, API Resources, Policies, dan penyimpanan gambar pada disk public.

README ini menjelaskan instalasi, konfigurasi, autentikasi, seluruh endpoint, format request, pengujian menggunakan Insomnia, serta troubleshooting.

Daftar Isi
Fitur
Teknologi
Persyaratan
Instalasi dan Konfigurasi
Menjalankan Aplikasi
Autentikasi
Daftar Endpoint
Contoh Penggunaan API
Pencarian, Filter, dan Pagination
Upload dan Penghapusan Gambar
Pengujian dengan Insomnia
Pengujian Otomatis
Respons dan Kode HTTP
Keamanan dan Batasan Data
Troubleshooting
Catatan Deployment
Fitur
Registrasi akun dan login menggunakan token Bearer.
Logout dengan mencabut token aktif.
Melihat profil pengguna yang sedang login.
CRUD kategori: membuat, melihat daftar/detail, memperbarui, dan menghapus.
CRUD produk dengan relasi kategori.
Setiap pengguna hanya dapat mengakses kategori dan produk miliknya sendiri.
Validasi input menggunakan Form Request.
Format respons resource untuk kategori dan produk.
Pencarian produk berdasarkan nama, SKU, dan deskripsi.
Filter produk berdasarkan kategori dan rentang harga.
Pengurutan hasil dan pagination.
Upload gambar produk dengan validasi tipe dan ukuran file.
Penghapusan gambar ketika produk dihapus atau gambar diganti/dihapus.
Rate limiting untuk autentikasi dan endpoint data.
Teknologi
PHP dan Laravel
MySQL untuk database aplikasi
Laravel Sanctum untuk personal access token
Laravel Form Request untuk validasi
Laravel API Resources untuk format respons
Laravel Policies/Gate untuk otorisasi
Insomnia untuk pengujian endpoint
Persyaratan
Pastikan lingkungan pengembangan sudah memiliki:

PHP versi yang didukung versi Laravel pada proyek.
Composer.
MySQL atau database yang dikonfigurasi pada proyek.
Ekstensi PHP yang diperlukan Laravel dan driver database yang digunakan.
Insomnia (opsional, untuk pengujian API melalui GUI).
Instalasi dan Konfigurasi
1. Masuk ke direktori proyek
Sesuaikan path dengan lokasi proyek di komputer:

cd ~/projects/college/product-management-api
2. Pasang dependency
Jika folder vendor/ belum tersedia:

composer install
Jika dependency sudah terpasang, langkah ini dapat dilewati.

3. Siapkan file environment
Pastikan file .env tersedia. Jika belum, buat dari template:

cp .env.example .env
php artisan key:generate
Jangan menjalankan cp di atas jika .env sudah berisi konfigurasi penting karena file yang ada dapat tertimpa. Jika .env sudah ada, cukup periksa dan sesuaikan nilainya.

Contoh konfigurasi database lokal:

APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=product_api_db
DB_USERNAME=your_mysql_user
DB_PASSWORD=your_mysql_password
Buat database product_api_db di MySQL terlebih dahulu, atau ganti nama database dan kredensial di atas sesuai lingkunganmu. Contoh tersebut hanya template; jangan menyalin username/password secara membabi buta.

4. Bersihkan cache konfigurasi
php artisan optimize:clear
5. Jalankan migration
php artisan migrate
Migration pada patch membuat tabel categories dan products. Pastikan migration bawaan untuk tabel users sudah berjalan karena kedua tabel tersebut memiliki relasi ke pengguna.

Jangan menggunakan php artisan migrate:fresh pada database yang berisi data penting. Perintah itu menghapus seluruh tabel lalu membuatnya ulang.

6. Aktifkan public storage
php artisan storage:link
Langkah ini diperlukan agar gambar produk pada disk public dapat diakses melalui URL publik di lingkungan lokal.

7. Verifikasi route
php artisan route:list --path=api
Route autentikasi yang benar menggunakan prefix /api/auth, sehingga endpoint register adalah /api/auth/register, bukan /api/register.

Menjalankan Aplikasi
Jalankan server pengembangan:

php artisan serve
Base URL default:

http://127.0.0.1:8000
Biarkan terminal server tetap berjalan saat mengirim request dari Insomnia.

Semua request sebaiknya menyertakan:

Accept: application/json
Untuk request JSON, tambahkan juga:

Content-Type: application/json
Untuk endpoint yang membutuhkan autentikasi, sertakan:

Authorization: Bearer TOKEN_KAMU
Autentikasi
API menggunakan Laravel Sanctum personal access token. Token dikembalikan ketika registrasi atau login berhasil.

Register
POST /api/auth/register

Body JSON:

{
  "name": "Asep Test",
  "email": "asep.test@example.com",
  "password": "Rahasia123",
  "password_confirmation": "Rahasia123"
}
Aturan penting:

name wajib, string, maksimum 100 karakter.
email wajib, format email valid, dan belum terdaftar.
password wajib, minimal 8 karakter, mengandung huruf dan angka.
password_confirmation harus sama dengan password.
Respons berhasil memiliki status 201 Created, dan token berada di data.token. Simpan token tersebut untuk request yang dilindungi autentikasi.

Login
POST /api/auth/login

Body JSON:

{
  "email": "asep.test@example.com",
  "password": "Rahasia123",
  "device_name": "insomnia"
}
device_name bersifat opsional. Jika login berhasil, respons memuat data.token.

Profil pengguna aktif
GET /api/auth/me

Memerlukan Bearer token. Mengembalikan data pengguna yang terkait dengan token tersebut.

Logout
POST /api/auth/logout

Memerlukan Bearer token. Mencabut token yang sedang digunakan. Jalankan logout setelah pengujian lain selesai jika token tersebut masih ingin digunakan.

Daftar Endpoint
Semua endpoint kategori dan produk memerlukan autentikasi.

Method	Endpoint	Keterangan
POST	/api/auth/register	Registrasi dan membuat token
POST	/api/auth/login	Login dan membuat token
GET	/api/auth/me	Profil pengguna aktif
POST	/api/auth/logout	Mencabut token aktif
GET	/api/categories	Daftar kategori pengguna
POST	/api/categories	Membuat kategori
GET	/api/categories/{id}	Detail kategori
PUT/PATCH	/api/categories/{id}	Memperbarui kategori
DELETE	/api/categories/{id}	Menghapus kategori
GET	/api/products	Daftar produk pengguna
POST	/api/products	Membuat produk
GET	/api/products/{id}	Detail produk
PUT/PATCH	/api/products/{id}	Memperbarui produk
DELETE	/api/products/{id}	Menghapus produk
Contoh Penggunaan API
1. Membuat kategori
POST /api/categories

Headers:

Accept: application/json
Content-Type: application/json
Authorization: Bearer TOKEN_KAMU
Body:

{
  "name": "Elektronik",
  "description": "Perangkat elektronik dan aksesori"
}
Nama kategori wajib diisi, maksimal 100 karakter, dan harus unik untuk pengguna yang sedang login. Deskripsi bersifat opsional, maksimal 2.000 karakter.

2. Melihat daftar kategori
GET /api/categories

Secara default daftar kategori menggunakan pagination 15 item per halaman. Gunakan query per_page untuk mengubah jumlah item per halaman, maksimum 100.

Contoh:

GET /api/categories?per_page=10
3. Melihat satu kategori
GET /api/categories/1

Ganti 1 dengan ID kategori yang benar dan dimiliki oleh pengguna yang login.

4. Memperbarui kategori
PATCH /api/categories/1

Body contoh:

{
  "name": "Elektronik dan Komputer",
  "description": "Kategori yang telah diperbarui"
}
Karena memakai PATCH, kamu dapat mengirim hanya field yang ingin diubah.

5. Menghapus kategori
DELETE /api/categories/1

Respons berhasil adalah 204 No Content. Kategori tidak dapat dihapus ketika masih digunakan oleh satu atau lebih produk; API mengembalikan 409 Conflict.

6. Membuat produk
POST /api/products

Body JSON:

{
  "category_id": 1,
  "name": "Keyboard Mekanik",
  "sku": "KEY-001",
  "description": "Keyboard untuk komputer",
  "price": 450000,
  "stock": 10
}
Aturan penting:

category_id wajib dan harus merujuk kategori milik pengguna yang sedang login.
name wajib, maksimal 150 karakter.
sku wajib, maksimal 64 karakter, unik untuk pengguna tersebut.
description opsional, maksimal 5.000 karakter.
price wajib berupa angka, minimal 0, maksimal sesuai batas kolom database, dan menerima hingga dua angka desimal.
stock opsional, bilangan bulat non-negatif. Nilai default database adalah 0.
Jika SKU sudah pernah digunakan oleh pengguna yang sama, gunakan SKU berbeda.

7. Melihat daftar produk
GET /api/products

Contoh request sederhana:

GET /api/products?per_page=15&page=1
8. Melihat detail produk
GET /api/products/1

Ganti 1 dengan ID produk milik pengguna yang login.

9. Memperbarui produk
PATCH /api/products/1

Body contoh:

{
  "name": "Keyboard Mekanik RGB",
  "price": 475000,
  "stock": 12
}
PATCH dapat dipakai untuk memperbarui sebagian field. SKU baru, bila dikirim, harus tetap unik di antara produk pengguna tersebut.

10. Menghapus produk
DELETE /api/products/1

Respons berhasil adalah 204 No Content. Jika produk memiliki gambar, file gambar terkait juga dihapus dari disk public.

Pencarian, Filter, dan Pagination
Endpoint: GET /api/products

Parameter	Contoh	Keterangan
q	q=keyboard	Mencari nama, SKU, atau deskripsi
category_id	category_id=1	Filter berdasarkan kategori
min_price	min_price=100000	Harga minimum
max_price	max_price=500000	Harga maksimum
sort_by	sort_by=price	Field urutan: name, price, stock, created_at
sort_dir	sort_dir=asc	Arah urutan: asc atau desc
per_page	per_page=20	Jumlah data per halaman, 1–100
page	page=2	Nomor halaman
Contoh gabungan:

GET /api/products?q=keyboard&category_id=1&min_price=100000&max_price=1000000&sort_by=price&sort_dir=asc&per_page=10&page=1
max_price tidak boleh lebih kecil dari min_price. Parameter yang tidak diperlukan boleh dihilangkan.

Upload dan Penghapusan Gambar
Membuat produk dengan gambar
Gunakan POST /api/products, lalu pilih Body → Multipart Form atau form-data di Insomnia.

Tambahkan field berikut:

Field	Tipe	Contoh
category_id	Text	1
name	Text	Mouse Wireless
sku	Text	MOU-001
description	Text	Mouse tanpa kabel
price	Text	150000
stock	Text	5
image	File	Pilih file gambar
Ketentuan gambar:

Format yang diterima: JPG, JPEG, PNG, atau WebP.
Ukuran maksimum: 2 MB.
Field gambar bersifat opsional.
Pastikan php artisan storage:link sudah dijalankan agar URL publik gambar dapat diakses.
Saat produk memiliki gambar, respons resource menyediakan image_url.

Mengganti atau menghapus gambar
Untuk mengganti gambar, kirim PATCH /api/products/{id} sebagai multipart form dengan field yang ingin diubah dan file baru pada field image.

Untuk menghapus gambar tanpa menggantinya, kirim body JSON:

{
  "remove_image": true
}
Gambar lama akan dihapus setelah perubahan database berhasil.

Pengujian dengan Insomnia
Collection siap impor tersedia pada file product-management-api-insomnia.json.

Import collection
Buka Insomnia.
Pilih Import lalu File.
Pilih product-management-api-insomnia.json.
Pastikan base_url pada Base Environment adalah http://127.0.0.1:8000.
Jalankan 01 Register di folder 01 - Auth, atau jalankan 02 Login jika akun sudah tersedia.
Salin data.token dari respons dan tempelkan ke variabel token di Base Environment.
Uji 03 Get current user.
Jalankan CRUD kategori terlebih dahulu, kemudian sesuaikan category_id dengan ID kategori yang dibuat.
Jalankan CRUD produk, lalu sesuaikan product_id dengan ID produk yang dibuat.
Jalankan logout terakhir.
Urutan pengujian yang disarankan
Register.
Login.
Get current user.
Create category.
List categories.
Get category by ID.
Update category.
Create product.
List/search/filter products.
Get product by ID.
Update product.
Upload atau hapus gambar jika dibutuhkan.
Delete product.
Delete category setelah tidak digunakan.
Logout.
Perhatikan bahwa request update dan delete benar-benar mengubah atau menghapus data. Gunakan database development dan data uji.

Pengujian Otomatis
Lihat daftar route:

php artisan route:list --path=api
Jalankan test suite:

php artisan test
Test feature pada patch mencakup autentikasi, kategori, produk, validasi, otorisasi antar pengguna, filter/pencarian, serta upload gambar. Hasil pengujian bergantung pada konfigurasi PHP, ekstensi, dependency, dan file proyek lokal. Jika test gagal, baca pesan error yang ditampilkan dan periksa konfigurasi yang disebutkan di dalamnya.

Respons dan Kode HTTP
Kode	Makna umum
200 OK	Request berhasil
201 Created	Resource berhasil dibuat atau registrasi berhasil
204 No Content	Resource berhasil dihapus
401 Unauthorized	Token tidak ada atau tidak valid
403 Forbidden	Pengguna tidak memiliki izin untuk resource tersebut
404 Not Found	Route atau resource tidak ditemukan
409 Conflict	Kategori masih digunakan oleh produk
422 Unprocessable Content	Data request gagal validasi
429 Too Many Requests	Batas request tercapai
500 Internal Server Error	Terjadi error pada aplikasi/server
Contoh respons validasi biasanya berisi message dan objek errors yang menjelaskan field bermasalah.

Keamanan dan Batasan Data
Endpoint kategori dan produk memerlukan token Sanctum.
Setiap pengguna hanya dapat melihat dan mengubah resource miliknya sendiri.
Kategori tidak bisa dihapus jika masih direferensikan oleh produk.
Nama kategori unik per pengguna.
SKU produk unik per pengguna.
Register dan login dibatasi hingga 6 request per menit.
Endpoint kategori dan produk dibatasi hingga 120 request per menit.
Masa berlaku token dapat dikonfigurasi. Patch mengatur default 10.080 menit (7 hari) melalui SANCTUM_EXPIRATION, apabila nilai tersebut diterapkan pada konfigurasi environment.
Jangan membagikan token atau memasukkan .env ke repositori publik.
Troubleshooting
The route api/register could not be found
Gunakan endpoint yang benar:

POST http://127.0.0.1:8000/api/auth/register
Periksa route yang terdaftar:

php artisan route:list --path=api
php artisan optimize:clear
401 Unauthorized
Pastikan:

Login/register sudah berhasil.
Token yang ditempelkan lengkap dan benar.
Authorization di Insomnia memakai Bearer Token.
Token belum dicabut lewat logout.
403 Forbidden
Pastikan resource yang diminta memang milik pengguna yang sedang login. API membatasi akses data antar pengguna.

404 Not Found saat membuka kategori atau produk
Periksa ID dan pastikan resource ada serta milik pengguna yang sedang login. Periksa juga path endpoint dan prefix /api.

422 Unprocessable Content
Baca respons JSON pada errors. Pastikan semua field wajib terisi, format email benar, password memenuhi aturan, category_id valid, harga/stock sesuai format, dan nama kategori/SKU tidak duplikat.

Database connection error
Periksa DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, dan DB_PASSWORD di .env. Pastikan database tersedia, kemudian jalankan:

php artisan config:clear
php artisan migrate
Gambar tidak bisa dibuka
Jalankan:

php artisan storage:link
Pastikan disk public tersedia dan file gambar telah berhasil disimpan.

Error class atau route tidak ditemukan setelah patch dipasang
Pastikan isi patch diekstrak pada root proyek yang benar. Lalu jalankan:

composer dump-autoload
php artisan optimize:clear
php artisan route:list --path=api
Jika masalah berlanjut, periksa stack trace terbaru dan file controller/model yang disebutkan oleh error.

Catatan Deployment
Sebelum digunakan di server produksi:

Set APP_ENV=production dan APP_DEBUG=false.
Gunakan HTTPS.
Gunakan kredensial database dengan hak akses minimum.
Jangan commit .env, token, atau kredensial.
Siapkan backup database dan file gambar.
Tinjau rate limit dan masa berlaku token sesuai kebutuhan.
Jalankan migration melalui prosedur deployment yang aman.
Periksa permission direktori storage dan bootstrap/cache.
Uji API di staging sebelum memindahkan ke produksi.
Lisensi dan Kontribusi
Tambahkan informasi lisensi, pemilik proyek, dan prosedur kontribusi sesuai ketentuan proyek atau institusi yang menggunakannya.