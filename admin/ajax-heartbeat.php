<?php
// admin/ajax-heartbeat.php
// Dipanggil otomatis lewat JavaScript (lihat inc/topbar.php) tiap 25 detik
// selama admin membuka halaman panel manapun. Tugasnya cuma update
// "jam berapa terakhir admin ini masih aktif" — dipakai buat status online.
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Belum login']);
    exit;
}

db()->execute(
    "UPDATE admins SET last_activity = NOW() WHERE id = ?",
    'i',
    [$_SESSION['admin_id']]
);

echo json_encode(['ok' => true]);