<?php
// admin/kelola-staff.php
require_once __DIR__ . '/../config/config.php';
requireSuperadminOnly(); // admin biasa tidak boleh masuk ke halaman ini sama sekali
$pageTitle = 'Kelola Staff';

// TAMBAH AKUN BARU (selalu role 'admin' — superadmin tidak dibuat lewat sini)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah') {
    checkCsrf();
    $nama = sanitize($_POST['nama'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = 'admin';
    $errors = [];

    if (!$nama || !$email || !$password) {
        $errors[] = 'Nama, email, dan password wajib diisi.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }
    $cekEmail = db()->fetchOne("SELECT id FROM admins WHERE email = ?", 's', [$email]);
    if ($cekEmail) {
        $errors[] = 'Email ini sudah dipakai akun lain.';
    }

    if (empty($errors)) {
        db()->insert(
            "INSERT INTO admins (nama, email, password, role, status, created_at) VALUES (?, ?, ?, ?, 1, NOW())",
            'ssss',
            [$nama, $email, password_hash($password, PASSWORD_DEFAULT), $role]
        );
        redirect(BASE_URL . '/admin/kelola-staff.php', 'Akun staff baru berhasil ditambahkan.', 'sukses');
    } else {
        setFlash('error', implode(' ', $errors));
    }
}

// EDIT AKUN (ubah nama, email, opsional password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'edit') {
    checkCsrf();
    $editId = (int) ($_POST['id'] ?? 0);
    $nama = sanitize($_POST['nama'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $errors = [];

    $target = db()->fetchOne("SELECT id FROM admins WHERE id = ? AND role = 'admin'", 'i', [$editId]);
    if (!$target) {
        redirect(BASE_URL . '/admin/kelola-staff.php', 'Akun ini tidak bisa dikelola dari halaman Kelola Staff.', 'error');
    }

    if (!$nama || !$email) {
        $errors[] = 'Nama dan email wajib diisi.';
    }
    $cekEmail = db()->fetchOne("SELECT id FROM admins WHERE email = ? AND id != ?", 'si', [$email, $editId]);
    if ($cekEmail) {
        $errors[] = 'Email ini sudah dipakai akun lain.';
    }
    if ($password !== '' && strlen($password) < 6) {
        $errors[] = 'Password baru minimal 6 karakter.';
    }

    if (empty($errors)) {
        if ($password !== '') {
            db()->execute(
                "UPDATE admins SET nama = ?, email = ?, password = ? WHERE id = ?",
                'sssi',
                [$nama, $email, password_hash($password, PASSWORD_DEFAULT), $editId]
            );
        } else {
            db()->execute(
                "UPDATE admins SET nama = ?, email = ? WHERE id = ?",
                'ssi',
                [$nama, $email, $editId]
            );
        }
        redirect(BASE_URL . '/admin/kelola-staff.php', 'Data akun staff berhasil diperbarui.', 'sukses');
    } else {
        redirect(BASE_URL . '/admin/kelola-staff.php?edit=' . $editId, implode(' ', $errors), 'error');
    }
}

// NONAKTIFKAN / AKTIFKAN AKUN
if (isset($_GET['nonaktifkan']) || isset($_GET['aktifkan'])) {
    $targetId = (int) ($_GET['nonaktifkan'] ?? $_GET['aktifkan']);
    $aksiNonaktif = isset($_GET['nonaktifkan']);

    // Halaman ini cuma boleh ubah status akun dengan role 'admin' (satu-satunya
    // role staff yang ada), bukan superadmin — jaga-jaga kalau id di-utak-atik
    // lewat URL. Dicek langsung di query supaya konsisten dengan staffList.
    $target = db()->fetchOne("SELECT id FROM admins WHERE id = ? AND role = 'admin'", 'i', [$targetId]);
    if (!$target) {
        redirect(BASE_URL . '/admin/kelola-staff.php', 'Akun ini tidak bisa dikelola dari halaman Kelola Staff.', 'error');
    }

    db()->execute("UPDATE admins SET status = ? WHERE id = ?", 'ii', [$aksiNonaktif ? 0 : 1, $targetId]);
    redirect(BASE_URL . '/admin/kelola-staff.php', 'Status akun berhasil diperbarui.', 'sukses');
}

$staffList = db()->fetchAll("SELECT * FROM admins WHERE role = 'admin' ORDER BY status DESC, created_at DESC", '', []);

// Data untuk mode EDIT (kalau ada ?edit=ID di URL)
$edit = null;
if (isset($_GET['edit'])) {
    $edit = db()->fetchOne("SELECT * FROM admins WHERE id = ? AND role = 'admin'", 'i', [(int) $_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Staff — Admin SAH Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <?php include __DIR__ . '/inc/admin-style.php'; ?>
</head>

<body>
    <?php include __DIR__ . '/inc/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/inc/topbar.php'; ?>
        <div class="content">
            <?= renderFlash() ?>

            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <div>
                    <h4 class="page-title">Kelola Staff</h4>
                    <p class="text-muted mb-0" style="font-size:.85rem">Tambah, nonaktifkan, atau aktifkan kembali
                        akun staff admin</p>
                </div>
                <button class="btn-admin btn-hijau"
                    onclick="document.getElementById('formTambah').scrollIntoView({behavior:'smooth'})">
                    <i class="bi bi-person-plus-fill"></i> Tambah Akun
                </button>
            </div>

            <!-- TABEL STAFF -->
            <div class="section-card mb-4">
                <div class="sc-header">
                    <div class="sc-title"><i class="bi bi-people me-2" style="color:var(--emas)"></i>Daftar Akun</div>
                </div>
                <div style="overflow-x:auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Login Terakhir</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($staffList)): ?>
                                <?php foreach ($staffList as $s): ?>
                                    <tr style="<?= $s['status'] == 0 ? 'opacity:.5' : '' ?>">
                                        <td style="font-weight:600;font-size:.85rem">
                                            <?= htmlspecialchars($s['nama']) ?>
                                        </td>
                                        <td style="font-size:.83rem"><?= htmlspecialchars($s['email']) ?></td>
                                        <td>
                                            <span class="badge bg-success">Admin</span>
                                        </td>
                                        <td style="font-size:.78rem;color:#94a3b8">
                                            <?= $s['last_login'] ? tglIndo(date('Y-m-d', strtotime($s['last_login']))) : 'Belum pernah login' ?>
                                        </td>
                                        <td>
                                            <?php if ($s['status'] == 1): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="kelola-staff.php?edit=<?= $s['id'] ?>#formTambah"
                                                    class="btn-admin-sm btn-biru">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <?php if ($s['status'] == 1): ?>
                                                    <a href="kelola-staff.php?nonaktifkan=<?= $s['id'] ?>"
                                                        class="btn-admin-sm btn-merah"
                                                        onclick="return confirm('Nonaktifkan akun <?= htmlspecialchars($s['nama']) ?>?')">
                                                        <i class="bi bi-slash-circle"></i> Nonaktifkan
                                                    </a>
                                                <?php else: ?>
                                                    <a href="kelola-staff.php?aktifkan=<?= $s['id'] ?>"
                                                        class="btn-admin-sm btn-hijau">
                                                        <i class="bi bi-check-circle"></i> Aktifkan
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-cell">Belum ada akun staff</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FORM TAMBAH / EDIT -->
            <div class="section-card" id="formTambah">
                <div class="sc-header">
                    <div class="sc-title">
                        <i class="bi bi-<?= $edit ? 'pencil-square' : 'person-plus-fill' ?> me-2" style="color:var(--emas)"></i>
                        <?= $edit ? 'Edit Akun: ' . htmlspecialchars($edit['nama']) : 'Tambah Akun Baru' ?>
                    </div>
                    <?php if ($edit): ?>
                        <a href="kelola-staff.php" class="btn-admin-sm btn-abu"><i class="bi bi-x"></i> Batal</a>
                    <?php endif; ?>
                </div>
                <div style="padding:22px">
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="aksi" value="<?= $edit ? 'edit' : 'tambah' ?>">
                        <?php if ($edit): ?>
                            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                        <?php endif; ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama" class="form-control"
                                    value="<?= $edit ? htmlspecialchars($edit['nama']) : '' ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control"
                                    value="<?= $edit ? htmlspecialchars($edit['email']) : '' ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Password <?= $edit ? '' : '<span class="text-danger">*</span>' ?>
                                </label>
                                <input type="password" name="password" class="form-control" minlength="6"
                                    <?= $edit ? '' : 'required' ?>
                                    placeholder="<?= $edit ? 'Kosongkan jika tidak ingin mengubah password' : '' ?>">
                                <div style="font-size:.72rem;color:#94a3b8;margin-top:4px">
                                    <?= $edit ? 'Isi hanya jika ingin mengganti password (minimal 6 karakter)' : 'Minimal 6 karakter' ?>
                                </div>
                            </div>
                            <div class="col-12 pt-1">
                                <button type="submit" class="btn-admin btn-hijau">
                                    <i class="bi bi-check-circle-fill me-1"></i><?= $edit ? 'Simpan Perubahan' : 'Tambah Akun' ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($edit): ?>
        <script>document.getElementById('formTambah').scrollIntoView();</script>
    <?php endif; ?>
</body>

</html>