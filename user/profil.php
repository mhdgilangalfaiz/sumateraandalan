<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
requireUserLogin();

$user = currentUser();
$errors = [];
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'update_profil') {
        $nama = sanitize($_POST['nama_lengkap'] ?? '');
        $telepon = sanitize($_POST['telepon'] ?? '');
        $alamat = sanitize($_POST['alamat'] ?? '');
        $kota = sanitize($_POST['kota'] ?? '');
        $provinsi = sanitize($_POST['provinsi'] ?? '');
        $noKtp = sanitize($_POST['no_ktp'] ?? '');
        $noPaspor = sanitize($_POST['no_paspor'] ?? '');
        $masaBerlaku = $_POST['masa_berlaku_paspor'] ?: null;

        if (!$nama || !$telepon) {
            $errors[] = 'Nama dan telepon wajib diisi.';
        }

        $fotoProfil = $user['foto_profil'];
        if (!empty($_FILES['foto_profil']['name'])) {
            $up = uploadFile($_FILES['foto_profil'], 'profil', ALLOWED_IMAGE_TYPES);
            if ($up['success']) {
                $fotoProfil = $up['filename'];
            } else {
                $errors[] = $up['message'];
            }
        }

        if (empty($errors)) {
            db()->execute(
                "UPDATE users SET nama_lengkap=?, telepon=?, alamat=?, kota=?, provinsi=?, no_ktp=?, no_paspor=?, masa_berlaku_paspor=?, foto_profil=? WHERE id=?",
                'sssssssssi',
                [$nama, $telepon, $alamat, $kota, $provinsi, $noKtp, $noPaspor, $masaBerlaku, $fotoProfil, $user['id']]
            );
            $_SESSION['user_nama'] = $nama;
            $sukses = 'Profil berhasil diperbarui.';
            $user = currentUser();
        }
    }

    if ($aksi === 'ubah_password') {
        $lama = $_POST['password_lama'] ?? '';
        $baru = $_POST['password_baru'] ?? '';
        $konfirmasi = $_POST['konfirmasi_baru'] ?? '';

        if (!password_verify($lama, $user['password'])) {
            $errors[] = 'Password lama salah.';
        } elseif (strlen($baru) < 6) {
            $errors[] = 'Password baru minimal 6 karakter.';
        } elseif ($baru !== $konfirmasi) {
            $errors[] = 'Konfirmasi password baru tidak cocok.';
        } else {
            db()->execute("UPDATE users SET password=? WHERE id=?", 'si', [password_hash($baru, PASSWORD_DEFAULT), $user['id']]);
            $sukses = 'Password berhasil diubah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya – SAH Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <?php include __DIR__ . '/../includes/navbar-style.php'; ?>
    <style>
        :root {
            --hijau-tua: #1B4D2E;
            --hijau: #1B6B3A;
            --emas: #C9A84C;
            --krem: #F9F5EE;
            --font-display: 'Playfair Display', serif;
            --font-body: 'DM Sans', sans-serif
        }

        .navbar {
            background: rgba(27, 77, 46, .97) !important
        }

        body {
            font-family: var(--font-body);
            background: var(--krem);
            min-height: 100vh
        }

        .page-title {
            font-family: var(--font-display);
            font-weight: 700;
            color: var(--hijau-tua)
        }

        .profil-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.8rem;
            border: 1px solid #f0ead9;
            margin-bottom: 1.5rem
        }

        .profil-card h6 {
            font-family: var(--font-display);
            color: var(--hijau-tua);
            font-weight: 700;
            margin-bottom: 1.2rem
        }

        .foto-profil {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--krem);
            border: 2px solid var(--emas)
        }

        .form-label {
            font-size: .82rem;
            font-weight: 600
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #e5ddc9;
            padding: .6rem .85rem;
            font-size: .88rem
        }

        .form-control:focus {
            border-color: var(--hijau);
            box-shadow: none
        }

        .btn-simpan {
            background: var(--hijau);
            color: #fff;
            border: none;
            border-radius: 30px;
            padding: .65rem 1.6rem;
            font-weight: 600;
            font-size: .88rem
        }

        .btn-simpan:hover {
            background: var(--hijau-tua);
            color: #fff
        }
    </style>
</head>

<body>
    <?php $navActive = ''; include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container py-5" style="max-width:760px">
        <h4 class="page-title mb-4"><i class="bi bi-person-circle me-2"></i>Profil Saya</h4>

        <?php if ($sukses): ?>
            <div class="alert alert-success" style="font-size:.85rem"><?= htmlspecialchars($sukses) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="font-size:.85rem">
                <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="profil-card">
            <h6>Data Pribadi</h6>
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="aksi" value="update_profil">

                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="<?= !empty($user['foto_profil']) ? UPLOAD_URL . $user['foto_profil'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['nama_lengkap']) . '&background=1B6B3A&color=fff' ?>"
                        class="foto-profil" alt="Foto profil">
                    <div>
                        <label class="form-label d-block mb-1">Ganti Foto Profil</label>
                        <input type="file" name="foto_profil" class="form-control form-control-sm" accept="image/*">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control"
                            value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"
                            disabled>
                        <div style="font-size:.72rem;color:#94a3b8;margin-top:3px">Email tidak bisa diubah</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. Telepon/WhatsApp</label>
                        <input type="text" name="telepon" class="form-control"
                            value="<?= htmlspecialchars($user['telepon'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kota</label>
                        <input type="text" name="kota" class="form-control"
                            value="<?= htmlspecialchars($user['kota'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Provinsi</label>
                        <input type="text" name="provinsi" class="form-control"
                            value="<?= htmlspecialchars($user['provinsi'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control"
                            rows="2"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                    </div>
                </div>

                <hr class="my-4">
                <div style="font-size:.78rem;font-weight:700;color:var(--hijau-tua);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px">
                    Dokumen Perjalanan (opsional, mempercepat proses visa/tiket nanti)</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">No. KTP</label>
                        <input type="text" name="no_ktp" class="form-control"
                            value="<?= htmlspecialchars($user['no_ktp'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">No. Paspor</label>
                        <input type="text" name="no_paspor" class="form-control"
                            value="<?= htmlspecialchars($user['no_paspor'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Masa Berlaku Paspor</label>
                        <input type="date" name="masa_berlaku_paspor" class="form-control"
                            value="<?= $user['masa_berlaku_paspor'] ?? '' ?>">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn-simpan"><i class="bi bi-check-circle-fill me-1"></i>Simpan
                        Perubahan</button>
                </div>
            </form>
        </div>

        <div class="profil-card">
            <h6>Ubah Password</h6>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="aksi" value="ubah_password">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="password_lama" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password_baru" class="form-control" minlength="6" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="konfirmasi_baru" class="form-control" minlength="6" required>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn-simpan"><i class="bi bi-shield-lock-fill me-1"></i>Ubah
                        Password</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>