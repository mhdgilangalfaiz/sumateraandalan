<?php
require_once __DIR__ . '/../config/config.php';
logout();
redirect(BASE_URL . '/admin/login.php');
