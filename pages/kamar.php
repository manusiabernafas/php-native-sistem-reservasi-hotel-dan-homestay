<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $k = q('SELECT * FROM kamar WHERE id = ?', [(int) $_POST['id']])->fetch();
    $ke = $_POST['status'] ?? '';
    // Housekeeping: kotor -> siap; admin bisa menandai perbaikan / selesai perbaikan
    $boleh = ($k['status'] === 'kotor' && $ke === 'tersedia')
        || (user()['role'] === 'admin' && $k['status'] === 'tersedia' && $ke === 'perbaikan')
        || (user()['role'] === 'admin' && $k['status'] === 'perbaikan' && $ke === 'tersedia');
    if ($k && $boleh) {
        q('UPDATE kamar SET status = ? WHERE id = ?', [$ke, $k['id']]);
        flash("Kamar {$k['nomor']} sekarang " . STATUS_KAMAR[$ke][0] . '.');
    } else {
        flash('Perubahan status kamar tidak diizinkan.', 'error');
    }
    redirect('kamar');
}
$kamar = q("SELECT k.*, t.nama tipe, t.harga, (SELECT tm.nama FROM reservasi r JOIN tamu tm ON tm.id = r.tamu_id WHERE r.kamar_id = k.id AND r.status = 'checkin' LIMIT 1) tamu,
            (SELECT r.check_out FROM reservasi r WHERE r.kamar_id = k.id AND r.status = 'checkin' LIMIT 1) sampai,
            (SELECT MIN(r.check_in) FROM reservasi r WHERE r.kamar_id = k.id AND r.status = 'booking' AND r.check_in >= CURDATE()) berikut
            FROM kamar k JOIN tipe_kamar t ON t.id = k.tipe_id ORDER BY k.lantai, k.nomor")->fetchAll();
$hitung = array_count_values(array_column($kamar, 'status'));
$lantai = [];
foreach ($kamar as $k) $lantai[$k['lantai']][] = $k;

$title = 'Status Kamar';
$eyebrow = 'Housekeeping';
require __DIR__ . '/../includes/header.php';
?>
<div class="kpis" style="grid-template-columns:repeat(4,1fr)">
    <?php foreach (STATUS_KAMAR as $s => [$l, $c]): ?><div class="kpi"><small><?= $l ?></small><strong><?= $hitung[$s] ?? 0 ?></strong><span>kamar</span></div><?php endforeach; ?>
</div>
<?php foreach ($lantai as $lt => $rooms): ?>
    <div class="card" style="margin-bottom:18px">
        <div class="card-head"><h2>Lantai <?= $lt ?></h2></div>
        <div class="card-body"><div class="rooms">
            <?php foreach ($rooms as $k): ?>
                <div class="room <?= $k['status'] ?>">
                    <div class="no"><?= e($k['nomor']) ?></div><small><?= e($k['tipe']) ?> · <?= rupiah($k['harga']) ?></small>
                    <div class="who"><?= badge(STATUS_KAMAR, $k['status']) ?><br>
                        <?php if ($k['tamu']): ?><?= e($k['tamu']) ?> <span class="muted">s/d <?= tgl($k['sampai']) ?></span>
                        <?php elseif ($k['berikut']): ?><span class="muted">Tamu berikutnya <?= tgl($k['berikut']) ?></span><?php endif; ?></div>
                    <?php if ($k['status'] === 'kotor'): ?>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $k['id'] ?>"><input type="hidden" name="status" value="tersedia"><button class="btn btn-sm btn-primary btn-block"><?= icon('check', 14) ?> Sudah bersih</button></form>
                    <?php elseif (user()['role'] === 'admin' && in_array($k['status'], ['tersedia', 'perbaikan'], true)): ?>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $k['id'] ?>"><input type="hidden" name="status" value="<?= $k['status'] === 'tersedia' ? 'perbaikan' : 'tersedia' ?>"><button class="btn btn-sm btn-block"><?= $k['status'] === 'tersedia' ? 'Tandai perbaikan' : 'Selesai perbaikan' ?></button></form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
