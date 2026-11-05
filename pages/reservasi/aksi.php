<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('reservasi');
$id = (int) ($_POST['id'] ?? 0);
try {
    db()->beginTransaction();
    $r = q('SELECT * FROM reservasi WHERE id = ? FOR UPDATE', [$id])->fetch();
    if (!$r) throw new RuntimeException('Reservasi tidak ditemukan.');
    switch ($_POST['aksi'] ?? '') {
        case 'checkin':
            if ($r['status'] !== 'booking') throw new RuntimeException('Hanya reservasi berstatus booking yang bisa check-in.');
            if ($r['check_in'] > date('Y-m-d')) throw new RuntimeException('Belum tanggal check-in.');
            $kamar = q('SELECT * FROM kamar WHERE id = ? FOR UPDATE', [$r['kamar_id']])->fetch();
            if ($kamar['status'] !== 'tersedia') throw new RuntimeException("Kamar {$kamar['nomor']} belum siap ditempati.");
            q("UPDATE reservasi SET status = 'checkin', checkin_at = NOW() WHERE id = ?", [$id]);
            q("UPDATE kamar SET status = 'terisi' WHERE id = ?", [$r['kamar_id']]);
            $pesan = "Check-in berhasil. Selamat datang, kamar {$kamar['nomor']}.";
            break;
        case 'layanan':
            if ($r['status'] !== 'checkin') throw new RuntimeException('Layanan hanya bisa ditambahkan saat tamu menginap.');
            $harga = (int) $_POST['harga'];
            if (trim($_POST['nama'] ?? '') === '' || $harga < 0) throw new RuntimeException('Isi nama dan harga layanan.');
            q('INSERT INTO layanan (reservasi_id, nama, qty, harga) VALUES (?,?,?,?)', [$id, trim($_POST['nama']), max(1, (int) $_POST['qty']), $harga]);
            $pesan = 'Layanan ditambahkan ke tagihan.';
            break;
        case 'bayar':
            if (!in_array($r['status'], ['booking', 'checkin'], true)) throw new RuntimeException('Reservasi sudah ditutup.');
            $sisa = total_tagihan($r)['sisa'];
            $jumlah = min(max(0, (int) $_POST['jumlah']), $sisa);
            if ($jumlah <= 0) throw new RuntimeException('Tidak ada sisa tagihan.');
            q('UPDATE reservasi SET dibayar = dibayar + ? WHERE id = ?', [$jumlah, $id]);
            $pesan = 'Pembayaran ' . rupiah($jumlah) . ' diterima.';
            break;
        case 'checkout':
            if ($r['status'] !== 'checkin') throw new RuntimeException('Tamu belum check-in.');
            if (total_tagihan($r)['sisa'] > 0) throw new RuntimeException('Masih ada sisa tagihan.');
            // Kebijakan umum hotel: malam yang sudah dipesan tetap ditagih walau tamu pulang lebih awal
            q("UPDATE reservasi SET status = 'checkout', checkout_at = NOW() WHERE id = ?", [$id]);
            q("UPDATE kamar SET status = 'kotor' WHERE id = ?", [$r['kamar_id']]);
            $pesan = 'Check-out selesai. Kamar ditandai perlu dibersihkan.';
            break;
        case 'batal':
            if ($r['status'] !== 'booking') throw new RuntimeException('Hanya reservasi booking yang bisa dibatalkan.');
            q("UPDATE reservasi SET status = 'batal' WHERE id = ?", [$id]);
            $pesan = 'Reservasi dibatalkan, kamar kembali tersedia di kalender.';
            break;
        default:
            throw new RuntimeException('Aksi tidak dikenal.');
    }
    db()->commit();
    flash($pesan);
} catch (RuntimeException $ex) {
    if (db()->inTransaction()) db()->rollBack();
    flash($ex->getMessage(), 'error');
}
redirect('res.detail', ['id' => $id]);
