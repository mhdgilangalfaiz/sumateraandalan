<?php
// admin/jadwal.php
require_once __DIR__ . '/../config/config.php';
cekAdmin();
$pageTitle = 'Jadwal Keberangkatan';

// HAPUS
if (isset($_GET['hapus'])) {
    blockIfSuperadmin(BASE_URL . '/admin/jadwal.php');
    db()->execute("DELETE FROM jadwal WHERE id=?", 'i', [(int) $_GET['hapus']]);
    redirect(BASE_URL . '/admin/jadwal.php', 'Jadwal berhasil dihapus.', 'sukses');
}

// SIMPAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    blockIfSuperadmin(BASE_URL . '/admin/jadwal.php');
    checkCsrf();
    $id = (int) ($_POST['id'] ?? 0);
    $paket_id = (int) ($_POST['paket_id'] ?? 0);
    $tgl_brkt = sanitize($_POST['tanggal_berangkat'] ?? '');
    $tgl_plg = sanitize($_POST['tanggal_pulang'] ?? '');
    $kuota = (int) ($_POST['kuota'] ?? 45);
    $harga_khs = $_POST['harga_khusus'] ? (float) str_replace(['.', ','], ['', '.'], $_POST['harga_khusus']) : null;
    $keterangan = sanitize($_POST['keterangan'] ?? '');
    $status = sanitize($_POST['status'] ?? 'aktif');

    if ($id) {
        db()->execute(
            "UPDATE jadwal SET paket_id=?,tanggal_berangkat=?,tanggal_pulang=?,kuota=?,harga_khusus=?,keterangan=?,status=? WHERE id=?",
            'issdsssi',
            [$paket_id, $tgl_brkt, $tgl_plg, $kuota, $harga_khs, $keterangan, $status, $id]
        );
        redirect(BASE_URL . '/admin/jadwal.php', 'Jadwal berhasil diperbarui.', 'sukses');
    } else {
        db()->insert(
            "INSERT INTO jadwal (paket_id,tanggal_berangkat,tanggal_pulang,kuota,terisi,harga_khusus,keterangan,status) VALUES (?,?,?,?,0,?,?,?)",
            'issidss',
            [$paket_id, $tgl_brkt, $tgl_plg, $kuota, $harga_khs, $keterangan, $status]
        );
        redirect(BASE_URL . '/admin/jadwal.php', 'Jadwal berhasil ditambahkan.', 'sukses');
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = db()->fetchOne("SELECT * FROM jadwal WHERE id=?", 'i', [(int) $_GET['edit']]);
}

$jadwals = db()->fetchAll(
    "SELECT j.*, p.nama_paket FROM jadwal j LEFT JOIN paket_umrah p ON j.paket_id = p.id ORDER BY j.tanggal_berangkat DESC",
    '',
    []
);
$pakets = db()->fetchAll("SELECT id, nama_paket FROM paket_umrah WHERE status='aktif' ORDER BY urutan", '', []);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal — Admin SAH Travel</title>
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
                <h4 class="page-title">Jadwal Keberangkatan</h4>
                <?php if (!isSuperadmin()): ?>
                <button class="btn-admin btn-hijau"
                    onclick="document.getElementById('formPanel').scrollIntoView({behavior:'smooth'})">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Jadwal
                </button>
                <?php endif; ?>
            </div>

            <?php if (isSuperadmin()):
                $totalKuotaJ = 0; $totalTerisiJ = 0; $totalAktifJ = 0;
                foreach ($jadwals as $jj) {
                    $totalKuotaJ += $jj['kuota'];
                    $totalTerisiJ += $jj['terisi'];
                    if ($jj['status'] === 'aktif') $totalAktifJ++;
                }
            ?>
            <div class="stat-summary-row mb-4">
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= count($jadwals) ?></div>
                    <div class="stat-mini-label">Total Jadwal</div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= $totalAktifJ ?></div>
                    <div class="stat-mini-label">Jadwal Aktif</div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= $totalTerisiJ ?>/<?= $totalKuotaJ ?></div>
                    <div class="stat-mini-label">Kuota Terisi</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- TABEL -->
            <div class="section-card mb-4">
                <div class="sc-header">
                    <div class="sc-title"><i class="bi bi-calendar3 me-2" style="color:var(--emas)"></i><?= isSuperadmin() ? 'Laporan Jadwal' : 'Daftar Jadwal' ?>
                    </div>
                </div>
                <div style="overflow-x:auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Paket</th>
                                <th>Berangkat</th>
                                <th>Kembali</th>
                                <th>Kuota</th>
                                <th>Terisi</th>
                                <th>Harga Khusus</th>
                                <th>Status</th>
                                <?php if (!isSuperadmin()): ?><th>Aksi</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($jadwals)): ?>
                                <?php foreach ($jadwals as $j):
                                    $sisa = $j['kuota'] - $j['terisi'];
                                    $persen = $j['kuota'] > 0 ? round(($j['terisi'] / $j['kuota']) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;font-size:.85rem">
                                                <?= htmlspecialchars($j['nama_paket'] ?? '-') ?>
                                            </div>
                                            <?php if ($j['keterangan']): ?>
                                                <div style="font-size:.72rem;color:#94a3b8">
                                                    <?= htmlspecialchars($j['keterangan']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-weight:600;font-size:.85rem">
                                            <?= tglIndo($j['tanggal_berangkat']) ?>
                                        </td>
                                        <td style="font-size:.83rem;color:#64748b">
                                            <?= tglIndo($j['tanggal_pulang']) ?>
                                        </td>
                                        <td>
                                            <?= $j['kuota'] ?>
                                        </td>
                                        <td>
                                            <div style="font-size:.83rem">
                                                <?= $j['terisi'] ?> <span style="color:#94a3b8">(sisa
                                                    <?= $sisa ?>)
                                                </span>
                                            </div>
                                            <div
                                                style="height:4px;background:#f1f5f9;border-radius:2px;margin-top:3px;width:60px">
                                                <div
                                                    style="height:100%;width:<?= $persen ?>%;background:<?= $persen >= 90 ? '#ef4444' : ($persen >= 70 ? '#f59e0b' : 'var(--hijau)') ?>;border-radius:2px">
                                                </div>
                                            </div>
                                        </td>
                                        <td style="font-size:.83rem">
                                            <?= $j['harga_khusus'] ? rupiah($j['harga_khusus']) : '<span style="color:#d1d5db">–</span>' ?>
                                        </td>
                                        <td>
                                            <?= statusBadge($j['status']) ?>
                                        </td>
                                        <?php if (!isSuperadmin()): ?>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="jadwal.php?edit=<?= $j['id'] ?>" class="btn-admin-sm btn-biru"><i
                                                        class="bi bi-pencil"></i> Edit</a>
                                                <a href="jadwal.php?hapus=<?= $j['id'] ?>" class="btn-admin-sm btn-merah"
                                                    onclick="return confirm('Hapus jadwal ini?')"><i
                                                        class="bi bi-trash"></i></a>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= isSuperadmin() ? 7 : 8 ?>" class="empty-cell">
                                        <i class="bi bi-calendar-x d-block mb-2" style="font-size:2rem;opacity:.3"></i>Belum
                                        ada jadwal
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FORM (disembunyikan untuk superadmin, read-only) -->
            <?php if (!isSuperadmin()): ?>
            <div class="section-card" id="formPanel">
                <div class="sc-header">
                    <div class="sc-title">
                        <i class="bi bi-<?= $edit ? 'pencil-square' : 'plus-circle-fill' ?> me-2"
                            style="color:var(--emas)"></i>
                        <?= $edit ? 'Edit Jadwal' : 'Tambah Jadwal Baru' ?>
                    </div>
                    <?php if ($edit): ?>
                        <a href="jadwal.php" class="btn-admin-sm btn-abu"><i class="bi bi-x"></i> Batal</a>
                    <?php endif; ?>
                </div>
                <div style="padding:22px">
                    <form method="POST">
                    <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Paket Umrah <span class="text-danger">*</span></label>
                                <select name="paket_id" class="form-select" required>
                                    <option value="">-- Pilih Paket --</option>
                                    <?php foreach ($pakets as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= ($edit['paket_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['nama_paket']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Berangkat <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_berangkat" class="form-control"
                                    value="<?= $edit['tanggal_berangkat'] ?? '' ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Kembali <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_pulang" class="form-control"
                                    value="<?= $edit['tanggal_pulang'] ?? '' ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Kuota</label>
                                <input type="number" name="kuota" class="form-control" min="1"
                                    value="<?= $edit['kuota'] ?? 45 ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach (['aktif', 'penuh', 'batal', 'selesai'] as $s): ?>
                                        <option value="<?= $s ?>" <?= ($edit['status'] ?? 'aktif') === $s ? 'selected' : '' ?>>
                                            <?= ucfirst($s) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Harga Khusus (opsional)</label>
                                <input type="text" name="harga_khusus" class="form-control"
                                    value="<?= $edit && $edit['harga_khusus'] ? number_format($edit['harga_khusus'], 0, ',', '.') : '' ?>"
                                    placeholder="Kosongkan jika sama dengan harga paket">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Keterangan</label>
                                <input type="text" name="keterangan" class="form-control"
                                    value="<?= htmlspecialchars($edit['keterangan'] ?? '') ?>"
                                    placeholder="Contoh: Special Ramadan, Early Bird, dll">
                            </div>
                            <div class="col-12 pt-1">
                                <button type="submit" class="btn-admin btn-hijau">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    <?= $edit ? 'Simpan Perubahan' : 'Tambah Jadwal' ?>
                                </button>
                                <?php if ($edit): ?><a href="jadwal.php" class="btn-admin btn-abu ms-2">Batal</a>
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