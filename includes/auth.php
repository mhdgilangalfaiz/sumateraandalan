<?php
// ============================================================
// includes/auth.php — FIXED VERSION
// ============================================================
// CATATAN: Ada 2 jenis akun — admin/superadmin (tabel `admins`) dan
// customer/jamaah (tabel `users`). Booking tetap bisa dilakukan sebagai
// guest (tanpa login) dan dicek lewat pages/cek-booking.php, TAPI customer
// juga bisa punya akun untuk login dan melihat riwayat booking mereka
// lewat user/dashboard.php (lihat loginUser/requireUserLogin/currentUser
// di bawah).
// Login & logout untuk KEDUA peran sudah terpusat di satu file:
// user/login.php dan user/logout.php. Jangan buat admin/login.php atau
// admin/logout.php lagi — role ditentukan otomatis lewat isAdminLoggedIn()
// vs isUserLoggedIn().
// isAdminLoggedIn() HANYA didefinisikan di sini,
// tidak boleh didefinisikan lagi di config.php

/**
 * Cek apakah admin sudah login
 */
function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']);
}

/**
 * Paksa admin login
 */
function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        redirect(BASE_URL . '/user/login.php');
    }
}

// Alias lama agar file yang pakai cekAdmin() tidak error
function cekAdmin(): void
{
    requireAdmin();
}

/**
 * True jika admin yang login adalah superadmin (read-only).
 * Dipakai untuk sembunyikan tombol Tambah/Edit/Hapus di semua halaman,
 * bukan untuk memblokir akses halamannya.
 */
function isSuperadmin(): bool
{
    return ($_SESSION['admin_role'] ?? '') === 'superadmin';
}

/**
 * Pasang di awal setiap blok AKSI (POST simpan, ?hapus=, ?aksi=...) pada
 * halaman yang tetap boleh DILIHAT oleh superadmin. Kalau yang mengakses
 * ternyata superadmin, aksi dibatalkan & redirect balik dengan pesan error.
 * Ini lapisan keamanan backend — independen dari tombol yang disembunyikan
 * di tampilan, supaya superadmin tidak bisa mengubah data walau lewat URL
 * atau form manual.
 */
function blockIfSuperadmin(string $redirectTo): void
{
    if (isSuperadmin()) {
        redirect($redirectTo, 'Akun Superadmin hanya bisa melihat data (read-only).', 'error');
    }
}

/**
 * Halaman khusus superadmin (misal: Kelola Staff). Admin biasa tidak boleh
 * masuk sama sekali — langsung ditolak & diarahkan ke dashboard.
 */
function requireSuperadminOnly(): void
{
    requireAdmin();
    if (!isSuperadmin()) {
        redirect(BASE_URL . '/admin/dashboard.php', 'Halaman ini hanya untuk Superadmin.', 'error');
    }
}

/* ============================================================
 * AUTH UNTUK CUSTOMER/AGEN (tabel `users`) — session key sengaja
 * dibedakan dari session admin ($_SESSION['user_id'] vs
 * $_SESSION['admin_id']) supaya tidak saling tabrakan/ketimpa.
 * ============================================================ */

function isUserLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function requireUserLogin(): void
{
    if (!isUserLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect(BASE_URL . '/user/login.php');
    }
}

function loginUser(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nama'] = $user['nama_lengkap'];
    $_SESSION['user_email'] = $user['email'];
    db()->execute("UPDATE users SET last_login = NOW() WHERE id = ?", 'i', [$user['id']]);
}

function logoutUser(): void
{
    unset($_SESSION['user_id'], $_SESSION['user_nama'], $_SESSION['user_email']);
}

function currentUser(): array|null
{
    if (!isUserLoggedIn())
        return null;
    return db()->fetchOne("SELECT * FROM users WHERE id = ?", 'i', [$_SESSION['user_id']]);
}

/**
 * Login admin — simpan ke session
 */
function loginAdmin(array $admin): void
{
    $_SESSION['admin_id']    = $admin['id'];
    $_SESSION['admin_nama']  = $admin['nama'];   // untuk tampilan di dashboard
    $_SESSION['admin_name']  = $admin['nama'];   // alias
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role']  = $admin['role'];

    // Update last login
    db()->execute(
        "UPDATE admins SET last_login = NOW() WHERE id = ?",
        'i',
        [$admin['id']]
    );
}

/**
 * Logout (hapus semua session)
 */
function logout(): void
{
    session_unset();
    session_destroy();
}

/**
 * Ambil data admin yang sedang login
 */
function currentAdmin(): array|null
{
    if (!isAdminLoggedIn()) return null;
    return db()->fetchOne(
        "SELECT * FROM admins WHERE id = ?",
        'i',
        [$_SESSION['admin_id']]
    );
}

/**
 * Jumlah notifikasi admin yang belum dibaca
 */
function adminNotifCount(): int
{
    try {
        $row = db()->fetchOne(
            "SELECT COUNT(*) as c FROM notifikasi WHERE dibaca = 0"
        );
        return (int)($row['c'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}