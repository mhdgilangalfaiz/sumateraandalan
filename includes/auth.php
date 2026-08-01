<?php
// ============================================================
// includes/auth.php — FIXED VERSION
// ============================================================
// CATATAN: Sistem akun untuk user/jamaah sudah dihapus.
// Booking sekarang sepenuhnya guest-checkout (tanpa login),
// status booking dicek lewat pages/cek-booking.php.
// Fungsi login di sini HANYA untuk admin.
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
        redirect(BASE_URL . '/admin/login.php');
    }
}

// Alias lama agar file yang pakai cekAdmin() tidak error
function cekAdmin(): void
{
    requireAdmin();
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