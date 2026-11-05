<?php
$nav = [
    ['dashboard', 'Front Desk', 'home'],
    ['kalender', 'Kalender', 'calendar'],
    ['kamar', 'Status Kamar', 'grid'],
    ['reservasi', 'Reservasi', 'receipt'],
    ['tamu', 'Tamu', 'users'],
    ['laporan', 'Laporan', 'chart'],
];
if (user()['role'] === 'admin') $nav[] = ['master', 'Pengaturan', 'settings'];
$current = $_GET['page'] ?? 'dashboard';
if (str_starts_with($current, 'res.') || $current === 'invoice') $current = 'reservasi';
$flash = flash();
$set = setting();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Front Desk') ?> · <?= e($set['nama_hotel']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="masthead">
    <div class="masthead-inner">
        <div class="mast-top">
            <span class="crest"><?= icon('sparkle', 20) ?></span>
            <div class="name"><?= e($set['nama_hotel']) ?><small>Sistem Reservasi</small></div>
            <div class="grow"></div>
            <div class="mast-user"><span><?= e(user()['nama']) ?> · <?= e(ucfirst(user()['role'])) ?></span><span class="dot"><?= e(strtoupper(substr(user()['nama'], 0, 1))) ?></span>
                <a href="<?= url('logout') ?>" title="Keluar"><?= icon('logout', 18) ?></a></div>
        </div>
        <nav class="tabs">
            <?php foreach ($nav as [$key, $label, $ic]): ?><a href="<?= url($key) ?>" class="<?= $current === $key ? 'active' : '' ?>"><?= icon($ic, 16) ?> <?= $label ?></a><?php endforeach; ?>
        </nav>
    </div>
</header>
<main class="page">
    <div class="page-head">
        <div><div class="eyebrow"><?= e($eyebrow ?? tgl(date('Y-m-d'))) ?></div><h1><?= e($title ?? 'Front Desk') ?></h1></div>
        <div class="page-actions"><?= $actions ?? '' ?></div>
    </div>
    <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
