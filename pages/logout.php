<?php
require_once __DIR__ . '/../config/config.php';
logout();
setFlash('success', 'Anda telah logout.');
redirect(APP_URL . '/pages/login.php');
