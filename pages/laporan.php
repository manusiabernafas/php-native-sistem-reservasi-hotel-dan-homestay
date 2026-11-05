<?php
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$hari = max(1, malam($dari, $sampai) + 1);
$totalKamar = (int) q("SELECT COUNT(*) FROM kamar")->fetchColumn();

// Malam terjual = irisan malam menginap dengan periode laporan (reservasi booking/menginap/checkout)
$akhirEks = date('Y-m-d', strtotime("$sampai +1 day"));
$res = q("SELECT r.*, tk.nama tipe FROM reservasi r JOIN kamar k ON k.id = r.kamar_id JOIN tipe_kamar tk ON tk.id = k.tipe_id
          WHERE r.status IN ('checkin','checkout') AND r.check_in < ? AND r.check_out > ?", [$akhirEks, $dari])->fetchAll();
$malamTerjual = 0; $pendapatanKamar = 0; $perTipe = []; $perSumber = [];
foreach ($res as $r) {
    $a = max($r['check_in'], $dari); $b = min($r['check_out'], $akhirEks);
    $m = malam($a, $b);
    $nilai = $m * $r['harga_malam'] - ($r['malam'] ? round($r['diskon'] * $m / $r['malam']) : 0);
    $malamTerjual += $m; $pendapatanKamar += $nilai;
    $perTipe[$r['tipe']]['malam'] = ($perTipe[$r['tipe']]['malam'] ?? 0) + $m;
    $perTipe[$r['tipe']]['nilai'] = ($perTipe[$r['tipe']]['nilai'] ?? 0) + $nilai;
    $perSumber[$r['sumber']] = ($perSumber[$r['sumber']] ?? 0) + 1;
}
$layanan = (int) q("SELECT COALESCE(SUM(l.qty * l.harga),0) FROM layanan l WHERE l.created_at BETWEEN ? AND ?", [$dari, $sampai . ' 23:59:59'])->fetchColumn();
$okupansi = round($malamTerjual / ($totalKamar * $hari) * 100, 1);
$adr = $malamTerjual ? round($pendapatanKamar / $malamTerjual) : 0;                 // Average Daily Rate
$revpar = round($pendapatanKamar / ($totalKamar * $hari));                            // Revenue per Available Room
arsort($perSumber);
$title = 'Laporan Kinerja';
$eyebrow = tgl($dari) . ' — ' . tgl($sampai) . ' · ' . $hari . ' hari';
$actions = '<button class="btn" onclick="window.print()">' . icon('printer', 16) . ' Cetak</button>';
require __DIR__ . '/../includes/header.php';
?>
<form class="card no-print" method="get" style="margin-bottom:20px"><div class="card-body filters"><input type="hidden" name="page" value="laporan">
    <input type="date" name="dari" value="<?= e($dari) ?>"><input type="date" name="sampai" value="<?= e($sampai) ?>"><button class="btn btn-primary">Tampilkan</button></div></form>
<div class="kpis">
    <div class="kpi"><small>Okupansi</small><strong><?= $okupansi ?>%</strong><span><?= $malamTerjual ?> dari <?= $totalKamar * $hari ?> malam kamar</span></div>
    <div class="kpi"><small>ADR</small><strong style="font-size:26px"><?= rupiah($adr) ?></strong><span>rata-rata harga per malam terjual</span></div>
    <div class="kpi"><small>RevPAR</small><strong style="font-size:26px"><?= rupiah($revpar) ?></strong><span>pendapatan per kamar tersedia</span></div>
    <div class="kpi"><small>Pendapatan kamar</small><strong style="font-size:26px"><?= rupiah($pendapatanKamar) ?></strong></div>
    <div class="kpi"><small>Layanan tambahan</small><strong style="font-size:26px"><?= rupiah($layanan) ?></strong></div>
</div>
<div class="grid-2">
    <div class="card"><div class="card-head"><h2>Per tipe kamar</h2></div><div class="table-wrap"><table>
        <thead><tr><th>Tipe</th><th>Malam terjual</th><th>ADR</th><th class="text-right">Pendapatan</th></tr></thead>
        <tbody><?php foreach ($perTipe as $tp => $v): ?><tr><td><strong><?= e($tp) ?></strong></td><td><?= $v['malam'] ?></td><td><?= rupiah($v['malam'] ? $v['nilai'] / $v['malam'] : 0) ?></td><td class="text-right"><strong><?= rupiah($v['nilai']) ?></strong></td></tr><?php endforeach; ?></tbody>
    </table></div></div>
    <div class="card"><div class="card-head"><h2>Sumber reservasi</h2></div><div class="card-body">
        <?php $maxS = max(1, ...array_values($perSumber ?: [1])); foreach ($perSumber as $s => $n): ?>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px"><span style="width:100px"><?= e($s) ?></span>
                <span style="flex:1;height:10px;background:var(--gold-soft);border-radius:6px;overflow:hidden"><span style="display:block;height:100%;width:<?= round($n / $maxS * 100) ?>%;background:var(--green)"></span></span><b style="width:30px;text-align:right"><?= $n ?></b></div>
        <?php endforeach; ?>
        <p class="muted" style="margin:14px 0 0;font-size:12.5px">ADR = pendapatan kamar ÷ malam terjual. RevPAR = pendapatan kamar ÷ (jumlah kamar × hari).</p>
    </div></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
