<?php
// admin/paket.php
// FIX #1: path require_once diperbaiki — dari admin/ cukup naik 1 level ke config/
require_once __DIR__ . '/../config/config.php';
cekAdmin();
$pageTitle = 'Paket Umrah';

// HAPUS
if (isset($_GET['hapus'])) {
    blockIfSuperadmin(BASE_URL . '/admin/paket.php');
    $id = (int) $_GET['hapus'];
    $paket = db()->fetchOne("SELECT * FROM paket_umrah WHERE id = ?", 'i', [$id]);
    if ($paket) {
        if ($paket['banner'])
            deleteFile($paket['banner']);
        db()->execute("DELETE FROM paket_umrah WHERE id = ?", 'i', [$id]);
        // FIX #2: redirect() sekarang sudah support 3 argumen (lihat helpers.php)
        redirect(BASE_URL . '/admin/paket.php', 'Paket berhasil dihapus.', 'sukses');
    }
    // Jika paket tidak ditemukan, tetap redirect agar tidak nyangkut
    redirect(BASE_URL . '/admin/paket.php', 'Paket tidak ditemukan.', 'error');
}

// SIMPAN (tambah/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    blockIfSuperadmin(BASE_URL . '/admin/paket.php');
    checkCsrf();
    $id = (int) ($_POST['id'] ?? 0);
    $nama = sanitize($_POST['nama_paket'] ?? '');
    $slug = makeSlug($nama) . ($id ? '' : '-' . time());
    $kategori = sanitize($_POST['kategori'] ?? 'reguler');
    $harga = (float) str_replace(['.', ','], ['', '.'], $_POST['harga'] ?? 0);
    $harga_coret = $_POST['harga_coret'] ? (float) str_replace(['.', ','], ['', '.'], $_POST['harga_coret']) : null;
    $durasi = (int) ($_POST['durasi'] ?? 0);
    $maskapai = sanitize($_POST['maskapai'] ?? '');
    $hotel_m = sanitize($_POST['hotel_mekkah'] ?? '');
    $bintang_m = (int) ($_POST['bintang_mekkah'] ?? 4);
    $hotel_mad = sanitize($_POST['hotel_madinah'] ?? '');
    $bintang_mad = (int) ($_POST['bintang_madinah'] ?? 4);
    $kuota = (int) ($_POST['kuota'] ?? 45);
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');
    $include = sanitize($_POST['include'] ?? '');
    $exclude = sanitize($_POST['exclude'] ?? '');
    $status = sanitize($_POST['status'] ?? 'aktif');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $urutan = (int) ($_POST['urutan'] ?? 0);

    // Fasilitas (textarea, satu per baris)
    $fasilitas_raw = array_filter(array_map('trim', explode("\n", $_POST['fasilitas'] ?? '')));
    $fasilitas = json_encode(array_values($fasilitas_raw));

    // Upload banner
    $banner = sanitize($_POST['banner_lama'] ?? '');
    if (!empty($_FILES['banner']['name'])) {
        $up = uploadFile($_FILES['banner'], 'paket');
        if ($up['success']) {
            if ($banner)
                deleteFile($banner);
            $banner = $up['filename'];
        }
    }

    if ($id) {
        // ============================================================
        // UPDATE
        // FIX #3: hitung ulang types — 19 kolom SET + 1 WHERE id = 20 char
        // Urutan: nama_paket(s) slug(s) kategori(s) harga(d) harga_coret(d)
        //         durasi(i) maskapai(s) hotel_m(s) bintang_m(i) hotel_mad(s)
        //         bintang_mad(i) kuota(i) deskripsi(s) fasilitas(s)
        //         include(s) exclude(s) banner(s) status(s) featured(i)
        //         urutan(i) | WHERE id(i)
        // = s s s d d i s s i s i i s s s s s s i i | i  -> 21 char? hitung lagi:
        // ============================================================
        db()->execute(
            "UPDATE paket_umrah SET nama_paket=?,slug=?,kategori=?,harga=?,harga_coret=?,durasi=?,
             maskapai=?,hotel_mekkah=?,bintang_mekkah=?,hotel_madinah=?,bintang_madinah=?,
             kuota=?,deskripsi=?,fasilitas=?,`include`=?,`exclude`=?,banner=?,status=?,featured=?,urutan=?,
             updated_at=NOW() WHERE id=?",
            'sssddisisisiisssssiii',
            [
                $nama,
                $slug,
                $kategori,
                $harga,
                $harga_coret,
                $durasi,
                $maskapai,
                $hotel_m,
                $bintang_m,
                $hotel_mad,
                $bintang_mad,
                $kuota,
                $deskripsi,
                $fasilitas,
                $include,
                $exclude,
                $banner,
                $status,
                $featured,
                $urutan,
                $id
            ]
        );
        redirect(BASE_URL . '/admin/paket.php', 'Paket berhasil diperbarui.', 'sukses');
    } else {
        // ============================================================
        // INSERT
        // FIX #3: 22 kolom = 22 placeholder = 22 karakter types
        // nama_paket(s) slug(s) kategori(s) harga(d) harga_coret(d)
        // durasi(i) maskapai(s) hotel_m(s) bintang_m(i) hotel_mad(s)
        // bintang_mad(i) kuota(i) sisa_kuota(i) deskripsi(s) fasilitas(s)
        // include(s) exclude(s) banner(s) status(s) featured(i) urutan(i)
        // created_by(i)
        // = s s s d d i s s i s i i i s s s s s s i i i = 22 char
        // ============================================================
        db()->insert(
            "INSERT INTO paket_umrah (nama_paket,slug,kategori,harga,harga_coret,durasi,maskapai,
             hotel_mekkah,bintang_mekkah,hotel_madinah,bintang_madinah,kuota,sisa_kuota,deskripsi,
             fasilitas,`include`,`exclude`,banner,status,featured,urutan,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            'sssddisisisiisssssiii',
            [
                $nama,
                $slug,
                $kategori,
                $harga,
                $harga_coret,
                $durasi,
                $maskapai,
                $hotel_m,
                $bintang_m,
                $hotel_mad,
                $bintang_mad,
                $kuota,
                $kuota,
                $deskripsi,
                $fasilitas,
                $include,
                $exclude,
                $banner,
                $status,
                $featured,
                $urutan,
                $_SESSION['admin_id']
            ]
        );
        redirect(BASE_URL . '/admin/paket.php', 'Paket berhasil ditambahkan.', 'sukses');
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = db()->fetchOne("SELECT * FROM paket_umrah WHERE id = ?", 'i', [(int) $_GET['edit']]);
}

$pakets = db()->fetchAll("SELECT * FROM paket_umrah ORDER BY urutan ASC, id DESC", '', []);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paket Umrah — Admin SAH Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <?php include __DIR__ . '/inc/admin-style.php'; ?>
    <style>
        .paket-img-thumb {
            width: 60px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            background: #f1f5f9;
        }

        .star-select {
            display: flex;
            gap: 4px;
        }

        .star-select input {
            display: none;
        }

        .star-select label {
            font-size: 1.3rem;
            color: #d1d5db;
            cursor: pointer;
            transition: color .15s;
        }

        .star-select input:checked~label,
        .star-select label:hover,
        .star-select label:hover~label {
            color: #f59e0b;
        }

        .badge-custom {
            display: inline-block;
            padding: .2rem .65rem;
            border-radius: 50px;
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/inc/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/inc/topbar.php'; ?>
        <div class="content">
            <?php /* FIX #4: getFlash() diganti renderFlash() yang return string HTML */ ?>
            <?= renderFlash() ?>

            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <h4 class="page-title">Paket Umrah</h4>
                <?php if (!isSuperadmin()): ?>
                <button class="btn-admin btn-hijau"
                    onclick="document.getElementById('formPanel').scrollIntoView({behavior:'smooth'})">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Paket
                </button>
                <?php endif; ?>
            </div>

            <?php if (isSuperadmin()):
                $totalAktif = 0; $totalKuota = 0; $totalSisa = 0; $totalFeatured = 0;
                foreach ($pakets as $pp) {
                    if ($pp['status'] === 'aktif') $totalAktif++;
                    $totalKuota += $pp['kuota'];
                    $totalSisa += $pp['sisa_kuota'];
                    if ($pp['featured']) $totalFeatured++;
                }
            ?>
            <div class="stat-summary-row mb-4">
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= count($pakets) ?></div>
                    <div class="stat-mini-label">Total Paket</div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= $totalAktif ?></div>
                    <div class="stat-mini-label">Paket Aktif</div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= $totalSisa ?>/<?= $totalKuota ?></div>
                    <div class="stat-mini-label">Sisa Kuota</div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= $totalFeatured ?></div>
                    <div class="stat-mini-label">Featured</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- TABEL PAKET -->
            <div class="section-card mb-4">
                <div class="sc-header">
                    <div class="sc-title"><i class="bi bi-briefcase-fill me-2" style="color:var(--emas)"></i><?= isSuperadmin() ? 'Laporan' : 'Daftar' ?>
                        Paket (<?= count($pakets) ?>)
                    </div>
                </div>
                <div style="overflow-x:auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Nama Paket</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Kuota</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <?php if (!isSuperadmin()): ?><th>Aksi</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pakets)): ?>
                                <?php foreach ($pakets as $p):
                                    $sisa_persen = $p['kuota'] > 0 ? round(($p['sisa_kuota'] / $p['kuota']) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if ($p['banner']): ?>
                                                <img src="<?= UPLOAD_URL . $p['banner'] ?>" class="paket-img-thumb" alt="">
                                            <?php else: ?>
                                                <div class="paket-img-thumb d-flex align-items-center justify-content-center"
                                                    style="background:linear-gradient(135deg,var(--hijau-tua),var(--hijau))">
                                                    <i class="bi bi-building text-white opacity-50"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-weight:600;font-size:.85rem">
                                                <?= htmlspecialchars($p['nama_paket']) ?>
                                            </div>
                                            <div style="font-size:.73rem;color:#94a3b8">
                                                <?= $p['durasi'] ?> hari ·
                                                <?= htmlspecialchars($p['maskapai']) ?>
                                            </div>
                                        </td>
                                        <td><span class="badge-custom" style="background:#e8f5ee;color:var(--hijau)">
                                                <?= ucfirst($p['kategori']) ?>
                                            </span></td>
                                        <td>
                                            <div style="font-weight:700;color:var(--hijau);font-size:.85rem">
                                                <?= rupiah($p['harga']) ?>
                                            </div>
                                            <?php if ($p['harga_coret']): ?>
                                                <div style="font-size:.72rem;color:#94a3b8;text-decoration:line-through">
                                                    <?= rupiah($p['harga_coret']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-size:.82rem">
                                                <?= $p['sisa_kuota'] ?>/<?= $p['kuota'] ?>
                                            </div>
                                            <div
                                                style="height:4px;background:#f1f5f9;border-radius:2px;margin-top:3px;width:70px">
                                                <div
                                                    style="height:100%;width:<?= $sisa_persen ?>%;background:var(--hijau);border-radius:2px">
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?= statusBadge($p['status']) ?>
                                        </td>
                                        <td>
                                            <?php if ($p['featured']): ?>
                                                <span style="color:var(--emas)"><i class="bi bi-star-fill"></i></span>
                                            <?php else: ?>
                                                <span style="color:#d1d5db"><i class="bi bi-star"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if (!isSuperadmin()): ?>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="paket.php?edit=<?= $p['id'] ?>" class="btn-admin-sm btn-biru">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="paket.php?hapus=<?= $p['id'] ?>" class="btn-admin-sm btn-merah"
                                                    onclick="return confirm('Hapus paket <?= htmlspecialchars(addslashes($p['nama_paket'])) ?>?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= isSuperadmin() ? 7 : 8 ?>" class="empty-cell">
                                        <i class="bi bi-briefcase d-block mb-2" style="font-size:2rem;opacity:.3"></i>Belum
                                        ada paket
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FORM TAMBAH/EDIT (disembunyikan untuk superadmin, read-only) -->
            <?php if (!isSuperadmin()): ?>
            <div class="section-card" id="formPanel">
                <div class="sc-header">
                    <div class="sc-title">
                        <i class="bi bi-<?= $edit ? 'pencil-square' : 'plus-circle-fill' ?> me-2"
                            style="color:var(--emas)"></i>
                        <?= $edit ? 'Edit Paket: ' . htmlspecialchars($edit['nama_paket']) : 'Tambah Paket Baru' ?>
                    </div>
                    <?php if ($edit): ?>
                        <a href="paket.php" class="btn-admin-sm btn-abu"><i class="bi bi-x"></i> Batal Edit</a>
                    <?php endif; ?>
                </div>
                <div style="padding:24px">
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
                        <input type="hidden" name="banner_lama" value="<?= htmlspecialchars($edit['banner'] ?? '') ?>">

                        <div class="row g-3">
                            <!-- Nama Paket -->
                            <div class="col-md-8">
                                <label class="form-label">Nama Paket <span class="text-danger">*</span></label>
                                <input type="text" name="nama_paket" class="form-control"
                                    value="<?= htmlspecialchars($edit['nama_paket'] ?? '') ?>"
                                    placeholder="Contoh: Paket Reguler 9 Hari" required>
                            </div>
                            <!-- Kategori -->
                            <div class="col-md-4">
                                <label class="form-label">Kategori</label>
                                <select name="kategori" class="form-select">
                                    <?php foreach (['reguler', 'plus', 'vip', 'furoda', 'promo'] as $k): ?>
                                        <option value="<?= $k ?>" <?= ($edit['kategori'] ?? '') === $k ? 'selected' : '' ?>>
                                            <?= ucfirst($k) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Harga -->
                            <div class="col-md-4">
                                <label class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="text" name="harga" class="form-control"
                                    value="<?= $edit ? number_format($edit['harga'], 0, ',', '.') : '' ?>"
                                    placeholder="25.000.000" required>
                            </div>
                            <!-- Harga Coret -->
                            <div class="col-md-4">
                                <label class="form-label">Harga Coret (Rp)</label>
                                <input type="text" name="harga_coret" class="form-control"
                                    value="<?= $edit && $edit['harga_coret'] ? number_format($edit['harga_coret'], 0, ',', '.') : '' ?>"
                                    placeholder="Kosongkan jika tidak ada">
                            </div>
                            <!-- Durasi -->
                            <div class="col-md-2">
                                <label class="form-label">Durasi (Hari)</label>
                                <input type="number" name="durasi" class="form-control" min="1"
                                    value="<?= $edit['durasi'] ?? 9 ?>">
                            </div>
                            <!-- Kuota -->
                            <div class="col-md-2">
                                <label class="form-label">Kuota</label>
                                <input type="number" name="kuota" class="form-control" min="1"
                                    value="<?= $edit['kuota'] ?? 45 ?>">
                            </div>
                            <!-- Maskapai -->
                            <div class="col-md-6">
                                <label class="form-label">Maskapai</label>
                                <input type="text" name="maskapai" class="form-control"
                                    value="<?= htmlspecialchars($edit['maskapai'] ?? '') ?>"
                                    placeholder="Garuda Indonesia">
                            </div>
                            <!-- Hotel Mekkah -->
                            <div class="col-md-5">
                                <label class="form-label">Hotel Mekkah</label>
                                <input type="text" name="hotel_mekkah" class="form-control"
                                    value="<?= htmlspecialchars($edit['hotel_mekkah'] ?? '') ?>"
                                    placeholder="Grand Zam Zam Tower">
                            </div>
                            <!-- Bintang Mekkah -->
                            <div class="col-md-1">
                                <label class="form-label">Bintang</label>
                                <select name="bintang_mekkah" class="form-select">
                                    <?php for ($i = 3; $i <= 5; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($edit['bintang_mekkah'] ?? 4) == $i ? 'selected' : '' ?>>
                                            <?= $i ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <!-- Hotel Madinah -->
                            <div class="col-md-5">
                                <label class="form-label">Hotel Madinah</label>
                                <input type="text" name="hotel_madinah" class="form-control"
                                    value="<?= htmlspecialchars($edit['hotel_madinah'] ?? '') ?>"
                                    placeholder="Dallah Taibah">
                            </div>
                            <!-- Bintang Madinah -->
                            <div class="col-md-1">
                                <label class="form-label">Bintang</label>
                                <select name="bintang_madinah" class="form-select">
                                    <?php for ($i = 3; $i <= 5; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($edit['bintang_madinah'] ?? 4) == $i ? 'selected' : '' ?>>
                                            <?= $i ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <!-- Deskripsi -->
                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="3"
                                    placeholder="Deskripsi singkat paket..."><?= htmlspecialchars($edit['deskripsi'] ?? '') ?></textarea>
                            </div>
                            <!-- Fasilitas -->
                            <div class="col-md-4">
                                <label class="form-label">Fasilitas <span class="form-text">(satu per
                                        baris)</span></label>
                                <textarea name="fasilitas" class="form-control" rows="5"
                                    placeholder="Muthawwif berpengalaman&#10;Visa umrah&#10;Asuransi perjalanan"><?= htmlspecialchars(implode("\n", json_decode($edit['fasilitas'] ?? '[]', true) ?: [])) ?></textarea>
                            </div>
                            <!-- Include -->
                            <div class="col-md-4">
                                <label class="form-label">Sudah Termasuk <span class="form-text">(satu per
                                        baris)</span></label>
                                <textarea name="include" class="form-control" rows="5"
                                    placeholder="Tiket pesawat PP&#10;Hotel Mekkah 5 malam&#10;Konsumsi 3x sehari"><?= htmlspecialchars($edit['include'] ?? '') ?></textarea>
                            </div>
                            <!-- Exclude -->
                            <div class="col-md-4">
                                <label class="form-label">Tidak Termasuk <span class="form-text">(satu per
                                        baris)</span></label>
                                <textarea name="exclude" class="form-control" rows="5"
                                    placeholder="Biaya paspor&#10;Keperluan pribadi&#10;Tips guide"><?= htmlspecialchars($edit['exclude'] ?? '') ?></textarea>
                            </div>
                            <!-- Banner -->
                            <div class="col-md-6">
                                <label class="form-label">Gambar Banner</label>
                                <?php if (!empty($edit['banner'])): ?>
                                    <div class="mb-2">
                                        <img src="<?= UPLOAD_URL . $edit['banner'] ?>"
                                            style="height:80px;border-radius:8px;object-fit:cover">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="banner" class="form-control" accept="image/*">
                                <div class="form-text">JPG/PNG/WEBP, maks 5MB</div>
                            </div>
                            <!-- Status & opsi -->
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach (['aktif', 'habis', 'nonaktif', 'coming_soon'] as $s): ?>
                                        <option value="<?= $s ?>" <?= ($edit['status'] ?? 'aktif') === $s ? 'selected' : '' ?>>
                                            <?= ucfirst(str_replace('_', ' ', $s)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Urutan</label>
                                <input type="number" name="urutan" class="form-control" min="0"
                                    value="<?= $edit['urutan'] ?? 0 ?>">
                            </div>
                            <div class="col-md-1 d-flex align-items-end pb-1">
                                <div class="form-check">
                                    <input type="checkbox" name="featured" id="featured" class="form-check-input"
                                        <?= ($edit['featured'] ?? 0) ? 'checked' : '' ?>>
                                    <label for="featured" class="form-check-label"
                                        style="font-size:.8rem">Featured</label>
                                </div>
                            </div>

                            <!-- SUBMIT -->
                            <div class="col-12 pt-2">
                                <button type="submit" class="btn-admin btn-hijau">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    <?= $edit ? 'Simpan Perubahan' : 'Tambah Paket' ?>
                                </button>
                                <?php if ($edit): ?>
                                    <a href="paket.php" class="btn-admin btn-abu ms-2">Batal</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($edit): ?>
        <script>document.getElementById('formPanel').scrollIntoView();</script>
    <?php endif; ?>
</body>

</html>