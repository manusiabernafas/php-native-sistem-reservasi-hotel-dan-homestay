<?php
$r = q('SELECT r.*, t.nama, t.telepon, t.identitas, t.email, t.kota, k.nomor, k.status kamar_status, tk.nama tipe FROM reservasi r JOIN tamu t ON t.id = r.tamu_id
        JOIN kamar k ON k.id = r.kamar_id JOIN tipe_kamar tk ON tk.id = k.tipe_id WHERE r.id = ?', [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$r) redirect('reservasi');
$layanan = q('SELECT * FROM layanan WHERE reservasi_id = ? ORDER BY id', [$r['id']])->fetchAll();
$t = total_tagihan($r);
$bisaCheckin = $r['status'] === 'booking' && $r['check_in'] <= date('Y-m-d') && $r['kamar_status'] === 'tersedia';

$title = $r['nama'];
$eyebrow = $r['kode'] . ' · Kamar ' . $r['nomor'];
$actions = '<a class="btn" href="' . url('invoice', ['id' => $r['id']]) . '" target="_blank">' . icon('printer', 16) . ' Invoice</a>';
require __DIR__ . '/../../includes/header.php';
?>
<div class="grid-2">
    <div>
        <div class="card">
            <div class="card-head"><h2>Detail menginap</h2><?= badge(STATUS_RES, $r['status']) ?></div>
            <div class="card-body"><dl class="kv">
                <dt>Kamar</dt><dd><b><?= e($r['nomor']) ?></b> · <?= e($r['tipe']) ?> <?= badge(STATUS_KAMAR, $r['kamar_status']) ?></dd>
                <dt>Check-in</dt><dd><?= tgl($r['check_in']) ?><?= $r['checkin_at'] ? ' <span class="muted">(tiba ' . date('H.i', strtotime($r['checkin_at'])) . ')</span>' : '' ?></dd>
                <dt>Check-out</dt><dd><?= tgl($r['check_out']) ?><?= $r['checkout_at'] ? ' <span class="muted">(keluar ' . date('H.i', strtotime($r['checkout_at'])) . ')</span>' : '' ?></dd>
                <dt>Durasi</dt><dd><?= $r['malam'] ?> malam · <?= $r['dewasa'] ?> tamu</dd>
                <dt>Kontak</dt><dd><?= e($r['telepon']) ?><?= $r['email'] ? ' · ' . e($r['email']) : '' ?><?= $r['kota'] ? ' · ' . e($r['kota']) : '' ?></dd>
                <dt>Identitas</dt><dd><?= e($r['identitas'] ?: '-') ?></dd>
                <dt>Sumber</dt><dd><?= e($r['sumber']) ?></dd>
                <?php if ($r['catatan']): ?><dt>Catatan</dt><dd><?= e($r['catatan']) ?></dd><?php endif; ?>
            </dl></div>
        </div>
        <div class="card mt">
            <div class="card-head"><h2>Layanan tambahan</h2></div>
            <div class="table-wrap"><table><tbody>
                <?php foreach ($layanan as $l): ?><tr><td><strong><?= e($l['nama']) ?></strong><small><?= tgl($l['created_at']) ?></small></td><td><?= $l['qty'] ?> x <?= rupiah($l['harga']) ?></td><td class="text-right"><strong><?= rupiah($l['qty'] * $l['harga']) ?></strong></td></tr><?php endforeach; ?>
                <?php if (!$layanan): ?><tr><td class="empty">Belum ada layanan tambahan.</td></tr><?php endif; ?>
            </tbody></table></div>
            <?php if ($r['status'] === 'checkin'): ?>
                <form method="post" action="<?= url('res.aksi') ?>" class="card-body filters" style="border-top:1px solid var(--line)"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="aksi" value="layanan">
                    <input type="text" name="nama" list="lay" placeholder="Laundry, makan malam, extra bed..." required style="flex:1">
                    <datalist id="lay"><option value="Laundry"><option value="Makan malam"><option value="Extra bed"><option value="Antar jemput bandara"><option value="Sewa motor"></datalist>
                    <input type="number" name="qty" value="1" min="1" style="width:70px"><input type="number" name="harga" placeholder="Harga" min="0" step="1000" required style="width:120px">
                    <button class="btn btn-primary btn-sm"><?= icon('plus', 14) ?></button></form>
            <?php endif; ?>
        </div>
    </div>
    <div>
        <div class="card">
            <div class="card-head"><h2>Tagihan</h2></div>
            <div class="card-body">
                <div class="sum">
                    <div><span class="muted">Kamar <?= $r['malam'] ?> malam × <?= rupiah($r['harga_malam']) ?></span><span><?= rupiah($t['kamar']) ?></span></div>
                    <div><span class="muted">Layanan tambahan</span><span><?= rupiah($t['layanan']) ?></span></div>
                    <div><span class="muted">Diskon</span><span>- <?= rupiah($r['diskon']) ?></span></div>
                    <div class="total"><span>Total</span><span><?= rupiah($t['total']) ?></span></div>
                    <div><span class="muted">Sudah dibayar</span><span><?= rupiah($r['dibayar']) ?></span></div>
                    <div><b><?= $t['sisa'] > 0 ? 'Sisa' : 'Status' ?></b><b><?= $t['sisa'] > 0 ? rupiah($t['sisa']) : 'LUNAS' ?></b></div>
                </div>
                <?php if (in_array($r['status'], ['booking', 'checkin'], true) && $t['sisa'] > 0): ?>
                    <form method="post" action="<?= url('res.aksi') ?>" class="filters mt"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="aksi" value="bayar">
                        <input type="number" name="jumlah" value="<?= $t['sisa'] ?>" min="1000" step="1000" style="flex:1"><button class="btn btn-sm">Terima Pembayaran</button></form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card mt">
            <div class="card-head"><h2>Tindakan</h2></div>
            <div class="card-body" style="display:grid;gap:10px">
                <?php if ($r['status'] === 'booking'): ?>
                    <form method="post" action="<?= url('res.aksi') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="aksi" value="checkin">
                        <button class="btn btn-primary btn-block" <?= $bisaCheckin ? '' : 'disabled' ?>><?= icon('login2', 16) ?> Check-in Sekarang</button></form>
                    <?php if (!$bisaCheckin): ?><small class="muted"><?= $r['check_in'] > date('Y-m-d') ? 'Check-in dibuka mulai ' . tgl($r['check_in']) . '.' : 'Kamar ' . e($r['nomor']) . ' belum siap (' . STATUS_KAMAR[$r['kamar_status']][0] . ').' ?></small><?php endif; ?>
                    <form method="post" action="<?= url('res.aksi') ?>" data-confirm="Batalkan reservasi <?= e($r['kode']) ?>?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="aksi" value="batal"><button class="btn btn-block">Batalkan Reservasi</button></form>
                <?php elseif ($r['status'] === 'checkin'): ?>
                    <form method="post" action="<?= url('res.aksi') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="aksi" value="checkout">
                        <button class="btn btn-gold btn-block" <?= $t['sisa'] > 0 ? 'disabled title="Lunasi tagihan dulu"' : '' ?>><?= icon('logout', 16) ?> Check-out</button></form>
                    <?php if ($t['sisa'] > 0): ?><small class="muted">Lunasi sisa tagihan sebelum check-out.</small><?php endif; ?>
                <?php else: ?><p class="muted" style="margin:0">Reservasi sudah <?= strtolower(STATUS_RES[$r['status']][0]) ?>.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
