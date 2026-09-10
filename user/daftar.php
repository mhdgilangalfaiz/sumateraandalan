<?php
// user/daftar.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (isUserLoggedIn()) {
    redirect(BASE_URL . '/user/dashboard.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $nama = sanitize($_POST['nama_lengkap'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $telepon = sanitize($_POST['telepon'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if (!$nama || !$email || !$telepon || !$password) {
        $errors[] = 'Semua field wajib diisi.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }
    if ($password !== $konfirmasi) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }
    if (empty($errors)) {
        $cek = db()->fetchOne("SELECT id FROM users WHERE email = ?", 's', [$email]);
        if ($cek) {
            $errors[] = 'Email ini sudah terdaftar. Silakan login.';
        }
    }

    if (empty($errors)) {
        // Catatan: email_verified langsung diset 1 karena sistem ini belum
        // punya infrastruktur pengiriman email. Kalau nanti ada, tinggal
        // ubah jadi 0 + kirim token_verifikasi ke email pendaftar.
        $userId = db()->insert(
            "INSERT INTO users (nama_lengkap, email, password, telepon, status, email_verified) VALUES (?, ?, ?, ?, 'aktif', 1)",
            'ssss',
            [$nama, $email, password_hash($password, PASSWORD_DEFAULT), $telepon]
        );

        $user = db()->fetchOne("SELECT * FROM users WHERE id = ?", 'i', [$userId]);
        loginUser($user);
        redirect(BASE_URL . '/user/dashboard.php', 'Akun berhasil dibuat, selamat datang!', 'sukses');
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — SAH Travel</title>
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

        .page-wrap {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* ===== KIRI: FORM ===== */
        .form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 2rem;
        }

        .form-box {
            width: 100%;
            max-width: 480px;
        }

        .form-logo-mobile {
            display: none;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .form-logo-mobile img {
            height: 46px;
        }

        .form-title {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--hijau-tua);
            margin-bottom: .35rem;
        }

        .form-sub {
            color: #888;
            font-size: .88rem;
            margin-bottom: 1.8rem;
            line-height: 1.5;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .9rem;
        }

        .field {
            margin-bottom: .9rem;
        }

        .form-label {
            font-size: .82rem;
            font-weight: 600;
            color: #444;
            margin-bottom: .35rem;
            display: block;
        }

        .form-control {
            border: 1.5px solid #e5e5e5;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: .88rem;
            font-family: var(--font-body);
            width: 100%;
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
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: .95rem;
            pointer-events: none;
        }

        .input-wrap .form-control {
            padding-left: 39px;
        }

        .toggle-pw {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            cursor: pointer;
            font-size: .95rem;
            background: none;
            border: none;
        }

        .toggle-pw:hover {
            color: var(--hijau);
        }

        .field-hint {
            font-size: .72rem;
            color: #aaa;
            margin-top: .3rem;
        }

        .btn-submit {
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
            margin-top: .4rem;
        }

        .btn-submit:hover {
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
            align-items: flex-start;
            gap: 8px;
        }

        .divider {
            border-top: 1px solid #f0f0f0;
            margin: 1.5rem 0;
        }

        .foot-link {
            text-align: center;
            font-size: .83rem;
            color: #888;
        }

        .foot-link a {
            color: var(--hijau);
            text-decoration: none;
            font-weight: 600;
        }

        .foot-link a:hover {
            text-decoration: underline;
        }

        .back-link {
            text-align: center;
            font-size: .83rem;
            margin-top: .9rem;
        }

        .back-link a {
            color: var(--hijau);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        /* ===== KANAN: BRANDING ===== */
        .brand-panel {
            width: 440px;
            flex-shrink: 0;
            background: linear-gradient(200deg, #1B6B3A 0%, #1B4D2E 55%, #0D2B1A 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: .06;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23C9A84C'%3E%3Cpath d='M30 0L39 20.5H60L42.5 33.2L49.5 53.5L30 40.5L10.5 53.5L17.5 33.2L0 20.5H21L30 0Z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .brand-glow {
            position: absolute;
            width: 340px;
            height: 340px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201, 168, 76, .16), transparent 70%);
            top: -60px;
            right: -100px;
        }

        .brand-logo-badge {
            position: relative;
            z-index: 2;
            width: 230px;
            margin-bottom: .3rem;
        }

        .brand-logo-badge img {
            width: 100%;
            height: auto;
            display: block;
            filter: drop-shadow(0 10px 25px rgba(0, 0, 0, .3));
        }

        .brand-headline {
            position: relative;
            z-index: 2;
            font-family: var(--font-display);
            font-size: 1.65rem;
            font-weight: 700;
            color: #fff;
            text-align: center;
            line-height: 1.3;
            margin-bottom: .6rem;
        }

        .brand-headline span {
            color: var(--emas-muda);
        }

        .brand-desc {
            position: relative;
            z-index: 2;
            color: rgba(255, 255, 255, .65);
            font-size: .86rem;
            text-align: center;
            line-height: 1.55;
            max-width: 300px;
            margin-bottom: 2.2rem;
        }

        .brand-stats {
            position: relative;
            z-index: 2;
            display: flex;
            gap: 12px;
            width: 100%;
            flex-wrap: wrap;
            justify-content: center;
        }

        .brand-stat-card {
            flex: 1;
            min-width: 118px;
            background: rgba(255, 255, 255, .07);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 14px;
            padding: 14px 10px;
            text-align: center;
        }

        .brand-stat-icon {
            color: var(--emas);
            font-size: 1.15rem;
            margin-bottom: 6px;
        }

        .brand-stat-label {
            color: rgba(255, 255, 255, .8);
            font-size: .72rem;
            line-height: 1.4;
        }

        .brand-quote {
            position: relative;
            z-index: 2;
            margin-top: 2.2rem;
            padding-top: 1.6rem;
            border-top: 1px solid rgba(255, 255, 255, .12);
            width: 100%;
        }

        .brand-quote-text {
            color: rgba(255, 255, 255, .75);
            font-size: .8rem;
            font-style: italic;
            text-align: center;
            line-height: 1.6;
        }

        .brand-quote-text i {
            color: var(--emas);
            opacity: .5;
        }

        /* ======================================================
           RESPONSIVE — 3 tingkat: desktop (>=992px), tablet
           (576–991px), mobile (<576px)
           ====================================================== */

        /* TABLET: branding panel jadi banner horizontal pendek di atas */
        @media (max-width: 991.98px) {
            .page-wrap {
                flex-direction: column;
            }

            .brand-panel {
                width: 100%;
                flex-direction: row;
                justify-content: center;
                align-items: center;
                gap: 1.5rem;
                padding: 1.8rem 2rem;
                flex-shrink: 0;
            }

            .brand-glow {
                display: none;
            }

            .brand-logo-badge {
                width: 130px;
                margin-bottom: 0;
                flex-shrink: 0;
            }

            .brand-headline {
                font-size: 1.25rem;
                text-align: left;
                margin-bottom: .25rem;
            }

            .brand-desc {
                text-align: left;
                margin-bottom: 0;
                max-width: 420px;
                font-size: .8rem;
            }

            .brand-stats,
            .brand-quote {
                display: none;
            }

            .form-panel {
                padding: 2.2rem 1.5rem;
            }

            .form-logo-mobile {
                display: none;
            }
        }

        /* MOBILE: banner disederhanakan jadi logo + judul saja (stack) */
        @media (max-width: 575.98px) {
            .brand-panel {
                flex-direction: column;
                text-align: center;
                gap: .6rem;
                padding: 1.6rem 1.2rem;
            }

            .brand-logo-badge {
                width: 105px;
            }

            .brand-headline {
                text-align: center;
                font-size: 1.1rem;
            }

            .brand-desc {
                text-align: center;
                font-size: .78rem;
            }

            .form-panel {
                padding: 1.8rem 1.1rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="page-wrap">

        <!-- KIRI: BRANDING -->
        <div class="brand-panel">
            <div class="brand-glow"></div>
            <div class="brand-logo-badge">
                <img src="<?= BASE_URL ?>/assets/img/logo-sah.png" alt="Logo SAH Travel">
            </div>
            <div>
                <div class="brand-headline">Gabung Bersama <span>SAH Travel</span></div>
                <div class="brand-desc">Daftar sekali, kelola semua perjalanan ibadah Anda — dari booking sampai
                    dokumen visa, semua terpantau rapi.</div>
            </div>

            <div class="brand-stats">
                <div class="brand-stat-card">
                    <div class="brand-stat-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="brand-stat-label">Ribuan Jamaah<br>Terlayani</div>
                </div>
                <div class="brand-stat-card">
                    <div class="brand-stat-icon"><i class="bi bi-patch-check-fill"></i></div>
                    <div class="brand-stat-label">PPIU Resmi<br>Kemenag RI</div>
                </div>
                <div class="brand-stat-card">
                    <div class="brand-stat-icon"><i class="bi bi-headset"></i></div>
                    <div class="brand-stat-label">Support<br>Setiap Hari</div>
                </div>
            </div>

            <div class="brand-quote">
                <div class="brand-quote-text"><i class="bi bi-quote"></i> Sekali daftar, seluruh perjalanan ibadah
                    keluarga bisa dipantau dari satu akun.</div>
            </div>
        </div>

        <!-- KANAN: FORM -->
        <div class="form-panel">
            <div class="form-box">
                <div class="form-title">Buat Akun Baru</div>
                <div class="form-sub">Untuk jamaah maupun travel/agen — pantau semua pesanan Anda di satu tempat.
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?= renderFlash() ?>

                <form method="POST" action="">
                    <?= csrfField() ?>

                    <div class="field">
                        <label class="form-label">Nama Lengkap</label>
                        <div class="input-wrap">
                            <i class="bi bi-person-fill input-icon"></i>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama sesuai KTP"
                                value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required autofocus>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label class="form-label">Email</label>
                            <div class="input-wrap">
                                <i class="bi bi-envelope-fill input-icon"></i>
                                <input type="email" name="email" class="form-control" placeholder="nama@email.com"
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="field">
                            <label class="form-label">No. Telepon/WhatsApp</label>
                            <div class="input-wrap">
                                <i class="bi bi-whatsapp input-icon"></i>
                                <input type="text" name="telepon" class="form-control" placeholder="08xxxxxxxxxx"
                                    value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label class="form-label">Password</label>
                            <div class="input-wrap">
                                <i class="bi bi-lock-fill input-icon"></i>
                                <input type="password" name="password" id="pw1" class="form-control"
                                    placeholder="Minimal 6 karakter" minlength="6" required>
                                <button type="button" class="toggle-pw" onclick="togglePw('pw1','eye1')">
                                    <i class="bi bi-eye" id="eye1"></i>
                                </button>
                            </div>
                        </div>
                        <div class="field">
                            <label class="form-label">Konfirmasi Password</label>
                            <div class="input-wrap">
                                <i class="bi bi-lock-fill input-icon"></i>
                                <input type="password" name="konfirmasi_password" id="pw2" class="form-control"
                                    placeholder="Ulangi password" minlength="6" required>
                                <button type="button" class="toggle-pw" onclick="togglePw('pw2','eye2')">
                                    <i class="bi bi-eye" id="eye2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="bi bi-person-plus-fill"></i> Daftar Sekarang
                    </button>
                </form>

                <div class="foot-link" style="margin-top:1.3rem">
                    Sudah punya akun? <a href="login.php">Login di sini</a>
                </div>

                <div class="divider"></div>
                <div class="back-link">
                    <a href="<?= BASE_URL ?>/index.php">
                        <i class="bi bi-arrow-left me-1"></i>Kembali ke halaman utama
                    </a>
                </div>
            </div>
        </div>

    </div>

    <script>
        function togglePw(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
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