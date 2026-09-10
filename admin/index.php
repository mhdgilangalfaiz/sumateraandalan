<?php
// admin/index.php
// Redirect otomatis: kalau sudah login admin -> dashboard, kalau belum -> login
require_once __DIR__ . '/../config/config.php';

if (isAdminLoggedIn()) {
    redirect(BASE_URL . '/admin/dashboard.php');
} else {
    redirect(BASE_URL . '/user/login.php');
}