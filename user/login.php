<?php
// user/login.php
// Login TERPUSAT untuk semua peran (admin, superadmin, customer) lewat 1
// URL & 1 form yang sama. Role ditentukan otomatis dari tabel mana yang
// cocok dengan email+password yang dimasukkan:
//   1) Dicek dulu ke tabel `admins` (admin & superadmin panel kelola)
//   2) Kalau tidak cocok, dicek ke tabel `users` (customer/jamaah)
// Pesan error SENGAJA dibuat generik untuk kedua kasus (tidak bocorkan
// tabel mana yang hampir cocok), supaya tidak jadi celah user-enumeration.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Kalau salah satu sesi sudah aktif, langsung ke dashboard masing-masing
if (isAdminLoggedIn()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}
if (isUserLoggedIn()) {
    redirect(BASE_URL . '/user/dashboard.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $errors[] = 'Email dan password wajib diisi.';
    } else {
        // 1) Coba sebagai ADMIN / SUPERADMIN
        $admin = db()->fetchOne("SELECT * FROM admins WHERE email = ? AND status = 1", 's', [$email]);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nama'] = $admin['nama'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];
            db()->execute("UPDATE admins SET last_login = NOW() WHERE id = ?", 'i', [$admin['id']]);
            redirect(BASE_URL . '/admin/dashboard.php', 'Selamat datang, ' . $admin['nama'] . '!', 'sukses');
        }

        // 2) Coba sebagai CUSTOMER
        $user = db()->fetchOne("SELECT * FROM users WHERE email = ?", 's', [$email]);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'aktif') {
                $errors[] = 'Akun Anda ' . ($user['status'] === 'banned' ? 'diblokir' : 'nonaktif') . '. Hubungi admin kami.';
            } else {
                loginUser($user);
                $redirectTo = $_SESSION['redirect_after_login'] ?? (BASE_URL . '/user/dashboard.php');
                unset($_SESSION['redirect_after_login']);
                redirect($redirectTo);
            }
        }

        // Tidak cocok di kedua tabel (atau password salah) → error generik
        if (empty($errors)) {
            $errors[] = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SAH Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --hijau-tua: #1B4D2E;
            --hijau: #1B6B3A;
            --hijau-muda: #2E8B57;
            --emas: #C9A84C;
            --emas-muda: #E8C97A;
            --krem: #F9F5EE;
            --font-arab: 'Amiri', serif;
            --font-display: 'Playfair Display', serif;
            --font-body: 'DM Sans', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-body);
            min-height: 100vh;
            display: flex;
            background: var(--krem);
        }

        /* KIRI */
        .left-panel {
            width: 420px;
            flex-shrink: 0;
            background: linear-gradient(160deg, #0D2B1A 0%, #1B4D2E 50%, #1B6B3A 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: .05;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23C9A84C'%3E%3Cpath d='M30 0L39 20.5H60L42.5 33.2L49.5 53.5L30 40.5L10.5 53.5L17.5 33.2L0 20.5H21L30 0Z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .left-arabic {
            font-family: var(--font-arab);
            font-size: 2.2rem;
            color: var(--emas);
            text-align: center;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 20px rgba(201, 168, 76, .3);
            position: relative;
            z-index: 2;
        }

        .left-brand {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-align: center;
            margin-bottom: .3rem;
            position: relative;
            z-index: 2;
        }

        .left-brand span {
            color: var(--emas);
        }

        .left-sub {
            color: rgba(255, 255, 255, .55);
            font-size: .85rem;
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 2;
        }

        .left-features {
            position: relative;
            z-index: 2;
            width: 100%;
        }

        .left-feat {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255, 255, 255, .8);
            font-size: .85rem;
            margin-bottom: 1rem;
        }

        .left-feat-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(201, 168, 76, .15);
            border: 1px solid rgba(201, 168, 76, .3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--emas);
            font-size: .95rem;
            flex-shrink: 0;
        }

        .left-badge {
            margin-top: 2rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(201, 168, 76, .15);
            border: 1px solid rgba(201, 168, 76, .3);
            color: var(--emas-muda);
            padding: .4rem 1rem;
            border-radius: 50px;
            font-size: .75rem;
        }

        /* KANAN */
        .right-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
        }

        .login-title {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--hijau-tua);
            margin-bottom: .4rem;
        }

        .login-sub {
            color: #888;
            font-size: .88rem;
            margin-bottom: 2rem;
        }

        .form-label {
            font-size: .85rem;
            font-weight: 600;
            color: #444;
            margin-bottom: .4rem;
        }

        .form-control {
            border: 1.5px solid #e5e5e5;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: .9rem;
            font-family: var(--font-body);
            transition: border-color .2s;
        }

        .form-control:focus {
            border-color: var(--hijau);
            box-shadow: 0 0 0 3px rgba(27, 107, 58, .1);
            outline: none;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 1rem;
        }

        .input-wrap .form-control {
            padding-left: 42px;
        }

        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            cursor: pointer;
            font-size: 1rem;
            background: none;
            border: none;
        }

        .toggle-pw:hover {
            color: var(--hijau);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--hijau-tua), var(--hijau));
            color: white;
            border: none;
            border-radius: 50px;
            padding: 13px;
            font-size: .95rem;
            font-weight: 700;
            width: 100%;
            font-family: var(--font-body);
            cursor: pointer;
            transition: all .3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(27, 77, 46, .3);
        }

        .error-box {
            background: #fee2e2;
            color: #991b1b;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: .85rem;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .divider {
            border-top: 1px solid #f0f0f0;
            margin: 1.5rem 0;
        }

        .back-link {
            text-align: center;
            font-size: .83rem;
            color: #888;
        }

        .back-link a {
            color: var(--hijau);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .extra-link {
            text-align: center;
            font-size: .83rem;
            color: #888;
            margin-top: .9rem;
        }

        .extra-link a {
            color: var(--hijau);
            text-decoration: none;
            font-weight: 600;
        }

        .extra-link a:hover {
            text-decoration: underline;
        }

        /* RESPONSIVE */
        @media (max-width:768px) {
            .left-panel {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- KIRI -->
    <div class="left-panel">
        <div class="left-arabic">بِسْمِ اللهِ الرَّحْمٰنِ الرَّحِيْمِ</div>
        <div class="left-brand">SAH <span>Travel</span></div>
        <div class="left-sub">PT. Sumatera Andalan Haramain</div>
        <div class="left-features">
            <div class="left-feat">
                <div class="left-feat-icon"><i class="bi bi-clipboard-check-fill"></i></div>
                <span>Pantau status booking Anda</span>
            </div>
            <div class="left-feat">
                <div class="left-feat-icon"><i class="bi bi-clock-history"></i></div>
                <span>Riwayat pemesanan lengkap</span>
            </div>
            <div class="left-feat">
                <div class="left-feat-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                <span>Info jadwal & dokumen visa</span>
            </div>
            <div class="left-feat">
                <div class="left-feat-icon"><i class="bi bi-headset"></i></div>
                <span>Akses cepat untuk tim kami</span>
            </div>
        </div>
        <div class="left-badge">
            <i class="bi bi-patch-check-fill"></i> PPIU Resmi Kemenag RI
        </div>
    </div>

    <!-- KANAN -->
    <div class="right-panel">
        <div class="login-box">
            <div class="login-title">Selamat Datang</div>
            <div class="login-sub">Masuk ke akun SAH Travel Anda</div>

            <?php if (!empty($errors)): ?>
                <div class="error-box">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?= renderFlash() ?>

            <form method="POST" action="">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-wrap">
                        <i class="bi bi-envelope-fill input-icon"></i>
                        <input type="email" name="email" class="form-control" placeholder="nama@email.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" name="password" id="passwordInput" class="form-control"
                            placeholder="Masukkan password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword()">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk
                </button>
            </form>

            <div class="extra-link">
                Belum punya akun? <a href="daftar.php">Daftar di sini</a>
            </div>
            <div class="extra-link">
                <a href="<?= BASE_URL ?>/pages/cek-booking.php" style="color:#6B6B6B">Cukup mau cek 1 booking? Klik di
                    sini</a>
            </div>

            <div class="divider"></div>
            <div class="back-link">
                <a href="<?= BASE_URL ?>/index.php">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke halaman utama
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
    </script>
</body>

</html>