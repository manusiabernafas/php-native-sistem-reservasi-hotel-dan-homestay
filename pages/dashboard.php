<?php
$hari = date('Y-m-d');
$totalKamar = (int) q("SELECT COUNT(*) FROM kamar WHERE status <> 'perbaikan'")->fetchColumn();
$inhouse = q("SELECT r.*, t.nama, k.nomor FROM reservasi r JOIN tamu t ON t.id = r.tamu_id JOIN kamar k ON k.id = r.kamar_id WHERE r.status = 'checkin' ORDER BY r.check_out, k.nomor")->fetchAll();
$datang = q("SELECT r.*, t.nama, t.telepon, k.nomor, k.status kamar_status, tk.nama tipe FROM reservasi r JOIN tamu t ON t.id = r.tamu_id JOIN kamar k ON k.id = r.kamar_id JOIN tipe_kamar tk ON tk.id = k.tipe_id
             WHERE r.status = 'booking' AND r.check_in <= ? ORDER BY r.check_in, k.nomor", [$hari])->fetchAll();
$pergi = array_values(array_filter($inhouse, fn ($r) => $r['check_out'] <= $hari));
$okupansi = $totalKamar ? round(count($inhouse) / $totalKamar * 100) : 0;
$pendapatan = (int) q("SELECT COALESCE(SUM(malam * harga_malam - diskon),0) FROM reservasi WHERE status = 'checkout' AND checkout_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
$kotor = (int) q("SELECT COUNT(*) FROM kamar WHERE status = 'kotor'")->fetchColumn();

// Proyeksi okupansi 14 hari ke depan dari booking yang sudah masuk
$proyeksi = [];
for ($i = 0; $i < 14; $i++) {
    $d = date('Y-m-d', strtotime("+$i day"));
    $n = (int) q("SELECT COUNT(*) FROM reservasi WHERE status IN ('booking','checkin') AND check_in <= ? AND check_out > ?", [$d, $d])->fetchColumn();
    $proyeksi[$d] = $totalKamar ? round($n / $totalKamar * 100) : 0;
}

$title = 'Front Desk';
$actions = '<a class="btn" href="' . url('kalender') . '">' . icon('calendar', 16) . ' Kalender</a><a class="btn btn-gold" href="' . url('res.baru') . '">' . icon('plus', 16) . ' Reservasi Baru</a>';
require __DIR__ . '/../includes/header.php';
?>
<div class="kpis">
    <div class="kpi"><small>Okupansi malam ini</small><strong><?= $okupansi ?>%</strong><span><?= count($inhouse) ?> dari <?= $totalKamar ?> kamar</span></div>
    <div class="kpi"><small>Kedatangan</small><strong><?= count($datang) ?></strong><span>menunggu check-in</span></div>
    <div class="kpi"><small>Keberangkatan</small><strong><?= count($pergi) ?></strong><span>check-out hari ini</span></div>
    <div class="kpi"><small>Perlu dibersihkan</small><strong><?= $kotor ?></strong><span>kamar</span></div>
    <div class="kpi"><small>Pendapatan kamar</small><strong style="font-size:26px"><?= rupiah($pendapatan) ?></strong><span>bulan ini</span></div>
</div>
<div class="grid-2">
    <div class="card">
        <div class="card-head"><h2>Kedatangan hari ini</h2><span class="muted">Check-in mulai <?= e(setting()['jam_checkin']) ?></span></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Tamu</th><th>Kamar</th><th>Menginap</th><th></th></tr></thead>
            <tbody><?php foreach ($datang as $r): ?>
                <tr><td><strong><?= e($r['nama']) ?></strong><small><?= e($r['kode']) ?> · <?= e($r['sumber']) ?></small></td>
                    <td><strong><?= e($r['nomor']) ?></strong><small><?= e($r['tipe']) ?> · <?= badge(STATUS_KAMAR, $r['kamar_status']) ?></small></td>
                    <td><?= $r['malam'] ?> malam<small>s/d <?= tgl($r['check_out']) ?></small></td>
                    <td class="actions"><a class="btn btn-sm btn-primary" href="<?= url('res.detail', ['id' => $r['id']]) ?>"><?= icon('login2', 14) ?> Check-in</a></td></tr>
            <?php endforeach; ?>
            <?php if (!$datang): ?><tr><td colspan="4" class="empty">Tidak ada tamu yang dijadwalkan datang.</td></tr><?php endif; ?></tbody>
        </table></div>
    </div>
    <div class="card">
        <div class="card-head"><h2>Keberangkatan</h2><span class="muted">Check-out maks <?= e(setting()['jam_checkout']) ?></span></div>
        <div class="table-wrap"><table><tbody>
            <?php foreach ($pergi as $r): $t = total_tagihan($r); ?>
                <tr><td><strong><?= e($r['nama']) ?></strong><small>Kamar <?= e($r['nomor']) ?> · <?= $t['sisa'] > 0 ? 'sisa ' . rupiah($t['sisa']) : 'lunas' ?></small></td>
                    <td class="actions"><a class="btn btn-sm" href="<?= url('res.detail', ['id' => $r['id']]) ?>">Check-out</a></td></tr>
            <?php endforeach; ?>
            <?php if (!$pergi): ?><tr><td class="empty">Tidak ada check-out hari ini.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</div>
<div class="grid-2 mt">
    <div class="card">
        <div class="card-head"><h2>Proyeksi okupansi 14 hari</h2><span class="legend"><span><i style="background:var(--gold)"></i>Akhir pekan</span><span><i style="background:var(--green)"></i>Hari kerja</span></span></div>
        <div class="card-body"><div class="bars">
            <?php foreach ($proyeksi as $d => $p): $we = in_array(date('w', strtotime($d)), ['5', '6'], true); ?>
                <div class="bar-col" title="<?= tgl($d) ?>: <?= $p ?>%"><small><?= $p ?>%</small><div class="bar <?= $we ? 'hi' : '' ?>" style="height:<?= max(2, $p) ?>%"></div><small><?= date('d/m', strtotime($d)) ?></small></div>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="card">
        <div class="card-head"><h2>Tamu menginap</h2><span class="muted"><?= count($inhouse) ?> kamar</span></div>
        <div class="table-wrap" style="max-height:290px;overflow:auto"><table><tbody>
            <?php foreach ($inhouse as $r): ?><tr><td><strong><?= e($r['nomor']) ?></strong></td><td><?= e($r['nama']) ?><small>sampai <?= tgl($r['check_out']) ?></small></td><td class="actions"><a class="icon-btn" href="<?= url('res.detail', ['id' => $r['id']]) ?>"><?= icon('eye', 16) ?></a></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
