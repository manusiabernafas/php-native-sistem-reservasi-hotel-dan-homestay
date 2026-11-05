<?php
$r = q('SELECT r.*, t.nama, t.telepon, t.identitas, t.kota, k.nomor, tk.nama tipe FROM reservasi r JOIN tamu t ON t.id = r.tamu_id JOIN kamar k ON k.id = r.kamar_id JOIN tipe_kamar tk ON tk.id = k.tipe_id WHERE r.id = ?', [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$r) redirect('reservasi');
$layanan = q('SELECT * FROM layanan WHERE reservasi_id = ? ORDER BY id', [$r['id']])->fetchAll();
$t = total_tagihan($r);
$set = setting();
$title = 'Invoice ' . $r['kode'];
$actions = '<button class="btn btn-primary" onclick="window.print()">' . icon('printer', 16) . ' Cetak</button>';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card invoice">
    <div class="invoice-head">
        <div><h2><?= e($set['nama_hotel']) ?></h2><div class="muted"><?= e($set['alamat']) ?><br><?= e($set['telepon']) ?></div></div>
        <div class="text-right"><div class="eyebrow" style="font-size:11px;letter-spacing:.2em;color:var(--gold);font-weight:700">INVOICE</div><div style="font-family:var(--serif);font-size:22px"><?= e($r['kode']) ?></div><?= badge(STATUS_RES, $r['status']) ?></div>
    </div>
    <dl class="kv" style="margin-bottom:22px">
        <dt>Tamu</dt><dd><b><?= e($r['nama']) ?></b> · <?= e($r['telepon']) ?><?= $r['kota'] ? ' · ' . e($r['kota']) : '' ?></dd>
        <dt>Kamar</dt><dd><?= e($r['nomor']) ?> · <?= e($r['tipe']) ?></dd>
        <dt>Menginap</dt><dd><?= tgl($r['check_in']) ?> – <?= tgl($r['check_out']) ?> (<?= $r['malam'] ?> malam)</dd>
    </dl>
    <table>
        <thead><tr><th>Keterangan</th><th class="text-right">Jumlah</th></tr></thead>
        <tbody>
            <tr><td>Kamar <?= e($r['tipe']) ?> · <?= $r['malam'] ?> malam × <?= rupiah($r['harga_malam']) ?></td><td class="text-right"><?= rupiah($t['kamar']) ?></td></tr>
            <?php foreach ($layanan as $l): ?><tr><td><?= e($l['nama']) ?> · <?= $l['qty'] ?> × <?= rupiah($l['harga']) ?></td><td class="text-right"><?= rupiah($l['qty'] * $l['harga']) ?></td></tr><?php endforeach; ?>
            <?php if ($r['diskon']): ?><tr><td>Diskon</td><td class="text-right">- <?= rupiah($r['diskon']) ?></td></tr><?php endif; ?>
            <tr><td><b>Total</b></td><td class="text-right"><b><?= rupiah($t['total']) ?></b></td></tr>
            <tr><td>Dibayar</td><td class="text-right"><?= rupiah($r['dibayar']) ?></td></tr>
            <tr><td><b><?= $t['sisa'] > 0 ? 'Sisa tagihan' : 'Status' ?></b></td><td class="text-right"><b><?= $t['sisa'] > 0 ? rupiah($t['sisa']) : 'LUNAS' ?></b></td></tr>
        </tbody>
    </table>
    <p class="muted" style="margin-top:26px;text-align:center;font-family:var(--serif);font-size:17px;font-style:italic">Terima kasih telah menginap bersama kami. Sampai jumpa kembali.</p>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
