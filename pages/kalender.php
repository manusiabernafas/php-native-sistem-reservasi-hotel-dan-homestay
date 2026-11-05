<?php
$mulai = $_GET['mulai'] ?? date('Y-m-d', strtotime('-2 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $mulai)) $mulai = date('Y-m-d');
$hari = 14;
$akhir = date('Y-m-d', strtotime("$mulai +$hari days"));
$tanggal = [];
for ($i = 0; $i < $hari; $i++) $tanggal[] = date('Y-m-d', strtotime("$mulai +$i day"));

$kamar = q('SELECT k.*, t.nama tipe FROM kamar k JOIN tipe_kamar t ON t.id = k.tipe_id ORDER BY k.nomor')->fetchAll();
$res = q("SELECT r.*, t.nama FROM reservasi r JOIN tamu t ON t.id = r.tamu_id WHERE r.status <> 'batal' AND r.check_in < ? AND r.check_out > ?", [$akhir, $mulai])->fetchAll();
$peta = [];
foreach ($res as $r) $peta[$r['kamar_id']][] = $r;
$namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

$title = 'Kalender Ketersediaan';
$eyebrow = tgl($mulai) . ' — ' . tgl(date('Y-m-d', strtotime("$akhir -1 day")));
$actions = '<a class="btn" href="' . url('kalender', ['mulai' => date('Y-m-d', strtotime("$mulai -7 days"))]) . '">&lsaquo; Minggu lalu</a>'
    . '<a class="btn" href="' . url('kalender') . '">Hari ini</a>'
    . '<a class="btn" href="' . url('kalender', ['mulai' => date('Y-m-d', strtotime("$mulai +7 days"))]) . '">Minggu depan &rsaquo;</a>';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-head"><div class="legend"><span><i style="background:var(--gold)"></i>Booking</span><span><i style="background:var(--green)"></i>Sedang menginap</span><span><i style="background:#b9b2a5"></i>Sudah check-out</span></div>
        <span class="muted">Klik sel kosong untuk membuat reservasi</span></div>
    <div class="gantt"><table>
        <thead><tr><th style="width:120px">Kamar</th>
            <?php foreach ($tanggal as $d): $w = (int) date('w', strtotime($d)); ?>
                <th class="<?= $d === date('Y-m-d') ? 'today' : '' ?> <?= in_array($w, [5, 6], true) ? 'weekend' : '' ?>"><?= $namaHari[$w] ?><b><?= date('d', strtotime($d)) ?></b></th>
            <?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($kamar as $k): ?>
            <tr><td class="room-col"><?= e($k['nomor']) ?> <small><?= e($k['tipe']) ?></small></td>
            <?php
            $i = 0;
            while ($i < $hari) {
                $d = $tanggal[$i];
                $hit = null;
                foreach ($peta[$k['id']] ?? [] as $r) if ($r['check_in'] <= $d && $r['check_out'] > $d) { $hit = $r; break; }
                if ($hit) {
                    // Gabungkan sel sepanjang durasi menginap yang terlihat di layar
                    $span = 0;
                    while ($i + $span < $hari && $tanggal[$i + $span] < $hit['check_out']) $span++;
                    echo '<td colspan="' . $span . '"><a class="bk ' . $hit['status'] . '" href="' . url('res.detail', ['id' => $hit['id']]) . '" title="' . e($hit['kode'] . ' · ' . $hit['nama']) . '">' . e($hit['nama']) . '</a></td>';
                    $i += $span;
                } elseif ($k['status'] === 'perbaikan' || $d < date('Y-m-d')) {
                    echo '<td class="off"></td>';
                    $i++;
                } else {
                    echo '<td class="free"><a href="' . url('res.baru', ['kamar' => $k['id'], 'in' => $d, 'out' => date('Y-m-d', strtotime("$d +1 day"))]) . '"></a></td>';
                    $i++;
                }
            }
            ?></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
