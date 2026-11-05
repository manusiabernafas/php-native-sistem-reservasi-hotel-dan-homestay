<?php
$in = $_GET['in'] ?? $_POST['check_in'] ?? date('Y-m-d');
$out = $_GET['out'] ?? $_POST['check_out'] ?? date('Y-m-d', strtotime("$in +1 day"));
$tipeId = (int) ($_GET['tipe'] ?? 0);
$kamarPilih = (int) ($_GET['kamar'] ?? $_POST['kamar_id'] ?? 0);
$tipe = q('SELECT * FROM tipe_kamar ORDER BY harga')->fetchAll();
$valid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $in) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $out) && $out > $in;
$tersedia = $valid ? kamar_tersedia($in, $out, $tipeId) : [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!$valid) throw new RuntimeException('Tanggal check-out harus setelah check-in.');
        if ($in < date('Y-m-d')) throw new RuntimeException('Tanggal check-in tidak boleh di masa lalu.');
        db()->beginTransaction();
        // Kunci kamar lalu cek ulang bentrok di dalam transaksi (aman dari double booking)
        $kamar = q("SELECT k.*, t.harga FROM kamar k JOIN tipe_kamar t ON t.id = k.tipe_id WHERE k.id = ? AND k.status <> 'perbaikan' FOR UPDATE", [$kamarPilih])->fetch();
        if (!$kamar) throw new RuntimeException('Pilih kamar yang tersedia.');
        if ($b = kamar_bentrok($kamar['id'], $in, $out)) throw new RuntimeException("Kamar {$kamar['nomor']} sudah dipesan {$b['nama']} ({$b['kode']}) " . tgl($b['check_in']) . ' – ' . tgl($b['check_out']) . '.');

        $tamuId = (int) ($_POST['tamu_id'] ?? 0);
        if (!$tamuId) {
            if (trim($_POST['nama'] ?? '') === '' || trim($_POST['telepon'] ?? '') === '') throw new RuntimeException('Isi nama dan telepon tamu.');
            $tamuId = (int) q('SELECT id FROM tamu WHERE telepon = ?', [trim($_POST['telepon'])])->fetchColumn();
            if (!$tamuId) {
                q('INSERT INTO tamu (nama, identitas, telepon, email, kota) VALUES (?,?,?,?,?)', [trim($_POST['nama']), trim($_POST['identitas'] ?? ''), trim($_POST['telepon']), trim($_POST['email'] ?? ''), trim($_POST['kota'] ?? '')]);
                $tamuId = (int) db()->lastInsertId();
            }
        }
        $malam = malam($in, $out);
        $diskon = max(0, (int) ($_POST['diskon'] ?? 0));
        $dp = min(max(0, (int) ($_POST['dibayar'] ?? 0)), $malam * $kamar['harga'] - $diskon);
        $sumber = in_array($_POST['sumber'] ?? '', ['Walk-in', 'Telepon/WA', 'Website', 'OTA'], true) ? $_POST['sumber'] : 'Walk-in';
        q('INSERT INTO reservasi (kode, tamu_id, kamar_id, check_in, check_out, malam, dewasa, harga_malam, diskon, dibayar, sumber, catatan, user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            'TMP-' . bin2hex(random_bytes(4)), $tamuId, $kamar['id'], $in, $out, $malam, max(1, (int) $_POST['dewasa']), $kamar['harga'], $diskon, $dp, $sumber, trim($_POST['catatan'] ?? '') ?: null, user()['id'],
        ]);
        $id = (int) db()->lastInsertId();
        q('UPDATE reservasi SET kode = ? WHERE id = ?', ['RSV-' . date('ym') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT), $id]);
        db()->commit();
        flash('Reservasi tersimpan.');
        redirect('res.detail', ['id' => $id]);
    } catch (RuntimeException $ex) {
        if (db()->inTransaction()) db()->rollBack();
        $error = $ex->getMessage();
    }
}
$tamuList = q('SELECT id, nama, telepon FROM tamu ORDER BY nama')->fetchAll();
$title = 'Reservasi Baru';
$eyebrow = 'Cek ketersediaan';
require __DIR__ . '/../../includes/header.php';
?>
<form method="get" class="card" style="margin-bottom:20px"><div class="card-body filters" style="align-items:end">
    <input type="hidden" name="page" value="res.baru">
    <div><label>Check-in</label><input type="date" name="in" value="<?= e($in) ?>" min="<?= date('Y-m-d') ?>"></div>
    <div><label>Check-out</label><input type="date" name="out" value="<?= e($out) ?>"></div>
    <div><label>Tipe kamar</label><select name="tipe"><option value="0">Semua tipe</option><?php foreach ($tipe as $t): ?><option value="<?= $t['id'] ?>" <?= $tipeId === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['nama']) ?></option><?php endforeach; ?></select></div>
    <button class="btn btn-primary"><?= icon('search', 16) ?> Cek Kamar Kosong</button>
    <?php if ($valid): ?><span class="muted"><?= malam($in, $out) ?> malam · <?= count($tersedia) ?> kamar tersedia</span><?php endif; ?>
</div></form>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($valid): ?>
<form method="post" class="grid-2">
    <?= csrf_field() ?><input type="hidden" name="check_in" value="<?= e($in) ?>"><input type="hidden" name="check_out" value="<?= e($out) ?>">
    <div class="card">
        <div class="card-head"><h2>Pilih kamar</h2></div>
        <div class="card-body"><div class="rooms">
            <?php foreach ($tersedia as $k): ?>
                <label class="room tersedia" style="cursor:pointer;text-transform:none;letter-spacing:0;color:var(--ink);font-weight:400;margin:0">
                    <input type="radio" name="kamar_id" value="<?= $k['id'] ?>" <?= $kamarPilih === (int) $k['id'] ? 'checked' : '' ?> required style="float:right">
                    <div class="no"><?= e($k['nomor']) ?></div><small><?= e($k['tipe']) ?></small>
                    <div class="who"><b><?= rupiah($k['harga']) ?></b>/malam<br><span class="muted">Total <?= rupiah($k['harga'] * malam($in, $out)) ?></span></div>
                </label>
            <?php endforeach; ?>
            <?php if (!$tersedia): ?><p class="empty">Tidak ada kamar kosong di tanggal ini.</p><?php endif; ?>
        </div></div>
    </div>
    <div class="card" style="align-self:start">
        <div class="card-head"><h2>Data tamu</h2></div>
        <div class="card-body"><div class="form-grid">
            <div class="full"><label>Tamu terdaftar</label><select name="tamu_id"><option value="0">Tamu baru (isi di bawah)</option><?php foreach ($tamuList as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['nama']) ?> · <?= e($t['telepon']) ?></option><?php endforeach; ?></select></div>
            <div><label>Nama</label><input type="text" name="nama"></div><div><label>Telepon / WA</label><input type="text" name="telepon"></div>
            <div><label>NIK / Paspor</label><input type="text" name="identitas"></div><div><label>Kota asal</label><input type="text" name="kota"></div>
            <div><label>Jumlah tamu</label><input type="number" name="dewasa" value="2" min="1" max="6"></div>
            <div><label>Sumber</label><select name="sumber"><option>Walk-in</option><option>Telepon/WA</option><option>Website</option><option>OTA</option></select></div>
            <div><label>Diskon (Rp)</label><input type="number" name="diskon" value="0" min="0" step="5000"></div>
            <div><label>DP dibayar</label><input type="number" name="dibayar" value="0" min="0" step="5000"></div>
            <div class="full"><label>Catatan</label><input type="text" name="catatan" placeholder="Permintaan khusus, jam kedatangan, dll."></div>
        </div><button class="btn btn-gold btn-block mt"><?= icon('check', 16) ?> Simpan Reservasi</button></div>
    </div>
</form>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
