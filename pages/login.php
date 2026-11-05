<?php
if (user()) redirect('dashboard');
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = q('SELECT * FROM users WHERE username = ?', [trim($_POST['username'] ?? '')])->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        unset($u['password']);
        $_SESSION['user'] = $u;
        redirect('dashboard');
    }
    $error = 'Username atau password salah.';
}
$set = setting();
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk · <?= e($set['nama_hotel']) ?></title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<div class="login-page">
    <div class="login-art">
        <span class="crest" style="width:58px;height:58px"><?= icon('sparkle', 26) ?></span>
        <div><h1><?= e($set['nama_hotel']) ?></h1><p>Kelola reservasi, kamar, dan tamu dari satu meja depan yang tenang dan rapi.</p></div>
        <small style="letter-spacing:.2em;text-transform:uppercase;color:#c9bfa9"><?= e($set['alamat']) ?></small>
    </div>
    <div class="login-form">
        <form method="post">
            <?= csrf_field() ?>
            <h2>Selamat datang</h2>
            <p class="muted" style="margin:0 0 24px">Masuk ke sistem front desk</p>
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <div class="field"><label>Username</label><input type="text" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>"></div>
            <div class="field"><label>Password</label><input type="password" name="password" required></div>
            <button class="btn btn-primary btn-block">Masuk</button>
            <div class="note">Akun demo: <b>admin</b> / <b>admin123</b> atau <b>resepsionis</b> / <b>admin123</b></div>
        </form>
    </div>
</div>
</body>
</html>
