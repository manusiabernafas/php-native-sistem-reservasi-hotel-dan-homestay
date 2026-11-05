<?php
$cari = trim($_GET['cari'] ?? '');
$rows = q("SELECT t.*, COUNT(r.id) inap, COALESCE(SUM(CASE WHEN r.status = 'checkout' THEN r.malam END),0) malam,
           COALESCE(SUM(CASE WHEN r.status = 'checkout' THEN r.malam * r.harga_malam - r.diskon END),0) belanja, MAX(r.check_in) terakhir
           FROM tamu t LEFT JOIN reservasi r ON r.tamu_id = t.id AND r.status <> 'batal'
           WHERE t.nama LIKE ? OR t.telepon LIKE ? OR t.kota LIKE ? GROUP BY t.id ORDER BY belanja DESC", ["%$cari%", "%$cari%", "%$cari%"])->fetchAll();
$title = 'Data Tamu';
$eyebrow = count($rows) . ' tamu';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-head"><h2>Tamu & riwayat menginap</h2><form class="filters" method="get"><input type="hidden" name="page" value="tamu"><input type="search" name="cari" value="<?= e($cari) ?>" placeholder="Nama, telepon, kota"></form></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Tamu</th><th>Kontak</th><th>Kota</th><th>Kunjungan</th><th>Total malam</th><th class="text-right">Total belanja kamar</th></tr></thead>
        <tbody><?php foreach ($rows as $t): ?>
            <tr><td><strong><?= e($t['nama']) ?></strong><small><?= e($t['identitas'] ?: '-') ?></small></td><td><?= e($t['telepon']) ?><small><?= e($t['email']) ?></small></td><td><?= e($t['kota'] ?: '-') ?></td>
                <td><?= $t['inap'] ?>x<?= $t['inap'] >= 3 ? ' <span class="badge badge-amber">Tamu setia</span>' : '' ?><small><?= $t['terakhir'] ? 'terakhir ' . tgl($t['terakhir']) : '' ?></small></td>
                <td><?= $t['malam'] ?></td><td class="text-right"><strong><?= rupiah($t['belanja']) ?></strong></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
