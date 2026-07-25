<?php
// ============================================================
// config/config.php — FIXED VERSION
// ============================================================

// ── SESSION (harus pertama) ──────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── ENVIRONMENT ──────────────────────────────────────────────
define('BASE_URL', 'http://localhost/sah_umrah');   // sesuai nama folder di htdocs
define('APP_URL', BASE_URL);                        // alias agar file lama tetap jalan
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');
define('APP_NAME', 'SAH Travel');
define('BASE_PATH', dirname(__DIR__));

// ── UPLOAD ────────────────────────────────────────────────────
define('MAX_FILE_SIZE', 5 * 1024 * 1024);         // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);

// ── PAGINATION ────────────────────────────────────────────────
define('PER_PAGE', 12);
define('ADMIN_PER_PAGE', 15);

// ── WHATSAPP ──────────────────────────────────────────────────
define('WA_NUMBER', '6281311223344');
define('WA_DEFAULT_MSG', 'Assalamu\'alaikum, saya ingin bertanya tentang paket umrah SAH Travel.');

// ── LOAD FILES ────────────────────────────────────────────────
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
// csrf.php — load hanya jika ada
if (file_exists(__DIR__ . '/../includes/csrf.php')) {
    require_once __DIR__ . '/../includes/csrf.php';
}
