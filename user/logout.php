<?php
// user/logout.php
// Logout TERPUSAT untuk semua peran (admin, superadmin, customer) — 1 file
// yang sama, supaya tidak ada lagi admin/logout.php terpisah yang menumpuk.
require_once __DIR__ . '/../config/config.php';

// Simpan dulu perannya SEBELUM sesi dihapus, supaya tahu mau diarahkan
// ke mana setelah logout.
$wasAdmin = isAdminLoggedIn();

// logout() menghapus SELURUH data sesi (session_unset + session_destroy),
// jadi aman dipakai untuk sesi admin maupun customer -- tidak ada sisa
// data sesi peran manapun yang tertinggal.
logout();

if ($wasAdmin) {
    // Admin/superadmin diarahkan balik ke halaman login (mereka memang
    // mau kembali kerja, bukan sekadar browsing).
    redirect(BASE_URL . '/user/login.php', 'Anda berhasil logout.', 'sukses');
} else {
    // Customer diarahkan ke beranda (biar tetap bisa lanjut browsing).
    redirect(BASE_URL . '/index.php');
}