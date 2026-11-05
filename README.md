# Sistem Reservasi Hotel & Homestay (PHP Native + MySQL)

Sistem Reservasi Hotel dan Homestay berbasis PHP Native dan MySQL untuk hotel kecil, guest house, homestay, villa, atau kos harian. Resepsionis bisa mengecek kamar kosong berdasarkan tanggal, membuat reservasi dalam hitungan detik, lalu melihat semua kamar dalam kalender ketersediaan model Gantt 14 hari: siapa menginap di kamar mana, sampai kapan, dan slot mana yang masih bisa dijual.

Alur front desk lengkap dari booking, DP, check-in, layanan tambahan (laundry, makan, extra bed), pelunasan, check-out, sampai housekeeping yang menandai kamar sudah bersih. Sistem otomatis menolak double booking, dan laporan menampilkan KPI hotel yang sebenarnya seperti okupansi, ADR, dan RevPAR. Tampilannya bergaya boutique hotel yang hangat dan elegan, cocok dipakai langsung atau untuk tugas kuliah dan skripsi sistem informasi perhotelan.

## Fitur

- Cek kamar kosong berdasarkan tanggal check-in, check-out, dan tipe kamar
- Kalender ketersediaan model Gantt 14 hari, klik slot kosong untuk langsung booking
- Anti double booking dengan cek tumpang tindih tanggal + penguncian database
- Reservasi dengan DP, diskon, jumlah tamu, sumber (Walk-in, Telepon/WA, Website, OTA)
- Daftarkan tamu baru langsung dari form reservasi
- Check-in dan check-out dengan validasi tanggal, status kamar, dan pelunasan
- Layanan tambahan selama menginap: laundry, makan, extra bed, antar jemput
- Status kamar untuk housekeeping: Siap, Terisi, Perlu Dibersihkan, Perbaikan
- Kamar otomatis Perlu Dibersihkan setelah check-out
- Dashboard front desk: kedatangan, keberangkatan, tamu menginap, kamar kotor
- Proyeksi okupansi 14 hari ke depan
- Invoice tamu siap cetak
- Laporan KPI hotel: okupansi, ADR, RevPAR, pendapatan per tipe kamar, sumber reservasi
- Data tamu dengan riwayat kunjungan dan penanda tamu setia
- Tipe kamar dan tarif bisa diatur (reservasi lama tetap memakai harga saat dipesan)
- Manajemen kamar, profil hotel, jam check-in/out, dan pengguna
- 2 peran: Admin (manajer) dan Resepsionis
- Keamanan: PDO prepared statement, password_hash, proteksi CSRF
- Data contoh 15 kamar, 4 tipe, 350+ reservasi (3 bulan ke belakang dan 1 bulan ke depan)
- Tampilan boutique hotel yang elegan dan responsif

## Instalasi

**Kebutuhan:** PHP 8.0+ dan MySQL/MariaDB (XAMPP atau Laragon).

1. Ekstrak ZIP, lalu pindahkan folder `reservasi-hotel` ke:
   - XAMPP: `C:\xampp\htdocs\reservasi-hotel`
   - Laragon: `C:\laragon\www\reservasi-hotel`

2. Jalankan **Apache** dan **MySQL**.

3. Buka `http://localhost/phpmyadmin` → **Import** → pilih `database/reservasi_hotel.sql` → **Import**.

   Atau lewat terminal:
   ```bash
   mysql -u root -p < database/reservasi_hotel.sql
   ```
   Data contoh menyesuaikan tanggal saat import.

4. Sesuaikan `config/database.php` jika user/password MySQL kamu berbeda:
   ```php
   return [
       'host'     => 'localhost',
       'port'     => 3306,
       'database' => 'reservasi_hotel',
       'username' => 'root',
       'password' => '',
   ];
   ```

5. Buka `http://localhost/reservasi-hotel` dan login:
   - Manajer: `admin` / `admin123`
   - Resepsionis: `resepsionis` / `admin123`

6. Masuk ke **Pengaturan** untuk mengganti nama hotel, tipe kamar, tarif, dan nomor kamar.

---
Source code ini dibagikan gratis oleh **Papicode** — https://papicode.id
