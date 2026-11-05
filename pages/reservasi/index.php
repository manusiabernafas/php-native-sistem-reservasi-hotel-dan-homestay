<?php
$status = $_GET['status'] ?? 'aktif';
$cari = trim($_GET['cari'] ?? '');
$where = [];
$p = [];
if ($status === 'aktif') $where[] = "r.status IN ('booking','checkin')";
elseif (isset(STATUS_RES[$status])) { $where[] = 'r.status = ?'; $p[] = $status; }
if ($cari !== '') { $where[] = '(r.kode LIKE ? OR t.nama LIKE ? OR t.telepon LIKE ? OR k.nomor = ?)'; array_push($p, "%$cari%", "%$cari%", "%$cari%", $cari); }
$rows = q('SELECT r.*, t.nama, t.telepon, k.nomor, tk.nama tipe FROM reservasi r JOIN tamu t ON t.id = r.tamu_id JOIN kamar k ON k.id = r.kamar_id JOIN tipe_kamar tk ON tk.id = k.tipe_id'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY ' . ($status === 'aktif' ? 'r.check_in' : 'r.check_in DESC') . ' LIMIT 300', $p)->fetchAll();
$title = 'Reservasi';
$actions = '<a class="btn btn-gold" href="' . url('res.baru') . '">' . icon('plus', 16) . ' Reservasi Baru</a>';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-head">
        <div class="filters"><?php foreach (['aktif' => 'Aktif', 'booking' => 'Booking', 'checkin' => 'Menginap', 'checkout' => 'Check-out', 'batal' => 'Batal', 'semua' => 'Semua'] as $k => $l): ?>
            <a class="btn btn-sm <?= $status === $k ? 'btn-primary' : '' ?>" href="<?= url('reservasi', ['status' => $k, 'cari' => $cari]) ?>"><?= $l ?></a><?php endforeach; ?></div>
        <form class="filters" method="get"><input type="hidden" name="page" value="reservasi"><input type="hidden" name="status" value="<?= e($status) ?>"><input type="search" name="cari" value="<?= e($cari) ?>" placeholder="Kode, tamu, telepon, no. kamar"></form>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>Reservasi</th><th>Tamu</th><th>Kamar</th><th>Tanggal</th><th>Tagihan</th><th>Status</th></tr></thead>
        <tbody><?php foreach ($rows as $r): $t = total_tagihan($r); ?>
            <tr onclick="location.href='<?= url('res.detail', ['id' => $r['id']]) ?>'" style="cursor:pointer">
                <td><span class="code"><?= e($r['kode']) ?></span><small><?= e($r['sumber']) ?></small></td>
                <td><strong><?= e($r['nama']) ?></strong><small><?= e($r['telepon']) ?></small></td>
                <td><strong><?= e($r['nomor']) ?></strong><small><?= e($r['tipe']) ?></small></td>
                <td><?= tgl($r['check_in']) ?> – <?= tgl($r['check_out']) ?><small><?= $r['malam'] ?> malam</small></td>
                <td><strong><?= rupiah($t['total']) ?></strong><small><?= $r['status'] === 'batal' ? '-' : ($t['sisa'] > 0 ? 'sisa ' . rupiah($t['sisa']) : 'lunas') ?></small></td>
                <td><?= badge(STATUS_RES, $r['status']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="empty">Tidak ada reservasi.</td></tr><?php endif; ?></tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
