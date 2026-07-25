<?php
// ============================================================
// includes/auth.php — FIXED VERSION
// ============================================================
// CATATAN: isLoggedIn() & isAdminLoggedIn() HANYA di sini,
// tidak boleh didefinisikan lagi di config.php

/**
 * Cek apakah user sudah login
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Cek apakah admin sudah login
 */
function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']);
}

/**
 * Paksa user login (redirect ke login jika belum)
 */
function requireLogin(string $redirectTo = ''): void
{
    if (!isLoggedIn()) {
        $url = BASE_URL . '/pages/login.php';
        if ($redirectTo) $url .= '?redirect=' . urlencode($redirectTo);
        redirect($url);
    }
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
 * Login user biasa
 */
function loginUser(array $user): void
{
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['nama_lengkap'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = 'user';

    db()->execute(
        "UPDATE users SET last_login = NOW() WHERE id = ?",
        'i',
        [$user['id']]
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
 * Ambil data user yang sedang login
 */
function currentUser(): array|null
{
    if (!isLoggedIn()) return null;
    return db()->fetchOne(
        "SELECT * FROM users WHERE id = ?",
        'i',
        [$_SESSION['user_id']]
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
