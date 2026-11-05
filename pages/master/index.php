<?php
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['aksi'] ?? '') {
            case 'hotel':
                q('UPDATE pengaturan SET nama_hotel=?, alamat=?, telepon=?, jam_checkin=?, jam_checkout=? WHERE id=1', [trim($_POST['nama_hotel']), trim($_POST['alamat']), trim($_POST['telepon']), $_POST['jam_checkin'], $_POST['jam_checkout']]);
                break;
            case 'tipe':
                foreach ($_POST['harga'] ?? [] as $id => $h) q('UPDATE tipe_kamar SET harga = ?, fasilitas = ? WHERE id = ?', [max(0, (int) $h), trim($_POST['fasilitas'][$id] ?? ''), (int) $id]);
                break;
            case 'tipe_baru':
                if (trim($_POST['nama']) === '' || (int) $_POST['harga'] <= 0) throw new RuntimeException('Nama dan harga tipe wajib diisi.');
                q('INSERT INTO tipe_kamar (nama, harga, kapasitas, fasilitas) VALUES (?,?,?,?)', [trim($_POST['nama']), (int) $_POST['harga'], max(1, (int) $_POST['kapasitas']), trim($_POST['fasilitas'])]);
                break;
            case 'kamar':
                if (trim($_POST['nomor']) === '') throw new RuntimeException('Nomor kamar wajib diisi.');
                q('INSERT INTO kamar (nomor, lantai, tipe_id) VALUES (?,?,?)', [trim($_POST['nomor']), max(1, (int) $_POST['lantai']), (int) $_POST['tipe_id']]);
                break;
            case 'user':
                if (strlen($_POST['password']) < 6) throw new RuntimeException('Password minimal 6 karakter.');
                q('INSERT INTO users (nama, username, password, role) VALUES (?,?,?,?)', [trim($_POST['nama']), trim($_POST['username']), password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['role'] === 'admin' ? 'admin' : 'resepsionis']);
                break;
        }
        flash('Perubahan disimpan.');
    } catch (PDOException $e) {
        flash('Gagal: data sudah ada.', 'error');
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'error');
    }
    redirect('master');
}
$set = setting();
$tipe = q('SELECT t.*, COUNT(k.id) jml FROM tipe_kamar t LEFT JOIN kamar k ON k.tipe_id = t.id GROUP BY t.id ORDER BY t.harga')->fetchAll();
$users = q('SELECT * FROM users ORDER BY id')->fetchAll();
$title = 'Pengaturan';
require __DIR__ . '/../../includes/header.php';
?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="aksi" value="tipe">
    <div class="card-head"><h2>Tipe kamar & tarif</h2></div>
    <div class="table-wrap"><table><thead><tr><th>Tipe</th><th>Kamar</th><th>Harga / malam</th><th>Fasilitas</th></tr></thead><tbody>
        <?php foreach ($tipe as $t): ?><tr><td><strong><?= e($t['nama']) ?></strong><small>maks <?= $t['kapasitas'] ?> orang</small></td><td><?= $t['jml'] ?></td>
            <td style="width:170px"><input type="number" name="harga[<?= $t['id'] ?>]" value="<?= (int) $t['harga'] ?>" step="5000"></td><td><input type="text" name="fasilitas[<?= $t['id'] ?>]" value="<?= e($t['fasilitas']) ?>"></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="card-body"><button class="btn btn-primary">Simpan Tarif</button> <span class="muted">Harga baru berlaku untuk reservasi baru; reservasi lama tetap memakai harga saat dipesan.</span></div>
</form>
<div class="grid-2 mt">
    <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="aksi" value="hotel">
        <div class="card-head"><h2>Profil hotel</h2></div>
        <div class="card-body"><div class="form-grid">
            <div class="full"><label>Nama hotel</label><input type="text" name="nama_hotel" value="<?= e($set['nama_hotel']) ?>"></div>
            <div class="full"><label>Alamat</label><input type="text" name="alamat" value="<?= e($set['alamat']) ?>"></div>
            <div><label>Telepon</label><input type="text" name="telepon" value="<?= e($set['telepon']) ?>"></div><div></div>
            <div><label>Jam check-in</label><input type="text" name="jam_checkin" value="<?= e($set['jam_checkin']) ?>"></div>
            <div><label>Jam check-out</label><input type="text" name="jam_checkout" value="<?= e($set['jam_checkout']) ?>"></div>
        </div><button class="btn btn-primary btn-block mt">Simpan</button></div>
    </form>
    <div>
        <div class="card"><div class="card-head"><h2>Tambah kamar / tipe</h2></div><div class="card-body" style="display:grid;gap:12px">
            <form method="post" class="filters"><?= csrf_field() ?><input type="hidden" name="aksi" value="kamar"><input type="text" name="nomor" placeholder="No. kamar" style="width:100px" required><input type="number" name="lantai" value="1" style="width:80px">
                <select name="tipe_id"><?php foreach ($tipe as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['nama']) ?></option><?php endforeach; ?></select><button class="btn btn-sm"><?= icon('plus', 14) ?> Kamar</button></form>
            <form method="post" class="filters"><?= csrf_field() ?><input type="hidden" name="aksi" value="tipe_baru"><input type="text" name="nama" placeholder="Nama tipe" required style="width:130px"><input type="number" name="harga" placeholder="Harga" required style="width:110px"><input type="number" name="kapasitas" value="2" style="width:70px"><input type="text" name="fasilitas" placeholder="Fasilitas"><button class="btn btn-sm"><?= icon('plus', 14) ?> Tipe</button></form>
        </div></div>
        <div class="card mt"><div class="card-head"><h2>Pengguna</h2></div>
            <div class="table-wrap"><table><tbody><?php foreach ($users as $u): ?><tr><td><strong><?= e($u['nama']) ?></strong><small><?= e($u['username']) ?></small></td><td class="text-right"><?= badge(['admin' => ['Admin', 'amber'], 'resepsionis' => ['Resepsionis', 'gray']], $u['role']) ?></td></tr><?php endforeach; ?></tbody></table></div>
            <form method="post" class="card-body filters"><?= csrf_field() ?><input type="hidden" name="aksi" value="user"><input type="text" name="nama" placeholder="Nama" required><input type="text" name="username" placeholder="Username" required><input type="password" name="password" placeholder="Password" minlength="6" required><select name="role"><option value="resepsionis">Resepsionis</option><option value="admin">Admin</option></select><button class="btn btn-sm"><?= icon('plus', 14) ?></button></form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
