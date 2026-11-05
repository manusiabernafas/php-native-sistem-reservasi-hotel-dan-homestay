<?php
require __DIR__ . '/includes/bootstrap.php';

$routes = [
    'login'      => 'pages/login.php',
    'logout'     => 'pages/logout.php',
    'dashboard'  => 'pages/dashboard.php',
    'kalender'   => 'pages/kalender.php',
    'kamar'      => 'pages/kamar.php',
    'reservasi'  => 'pages/reservasi/index.php',
    'res.baru'   => 'pages/reservasi/baru.php',
    'res.detail' => 'pages/reservasi/detail.php',
    'res.aksi'   => 'pages/reservasi/aksi.php',
    'invoice'    => 'pages/reservasi/invoice.php',
    'tamu'       => 'pages/tamu.php',
    'laporan'    => 'pages/laporan.php',
    'master'     => 'pages/master/index.php',
];

$page = $_GET['page'] ?? 'dashboard';
if (!isset($routes[$page])) {
    http_response_code(404);
    $page = 'dashboard';
}
if ($page !== 'login') require_login();
verify_csrf();

require __DIR__ . '/' . $routes[$page];
