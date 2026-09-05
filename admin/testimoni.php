<?php
// admin/testimoni.php
require_once __DIR__ . '/../config/config.php';
cekAdmin();
$pageTitle = 'Testimoni';

// AKSI
if (isset($_GET['approve'])) {
    blockIfSuperadmin(BASE_URL . '/admin/testimoni.php');
    db()->execute("UPDATE testimoni SET status='approved', featured=1 WHERE id=?", 'i', [(int) $_GET['approve']]);
    redirect(BASE_URL . '/admin/testimoni.php', 'Testimoni disetujui dan ditampilkan.', 'sukses');
}
if (isset($_GET['reject'])) {
    blockIfSuperadmin(BASE_URL . '/admin/testimoni.php');
    db()->execute("UPDATE testimoni SET status='rejected' WHERE id=?", 'i', [(int) $_GET['reject']]);
    redirect(BASE_URL . '/admin/testimoni.php', 'Testimoni ditolak.', 'sukses');
}
if (isset($_GET['hapus'])) {
    blockIfSuperadmin(BASE_URL . '/admin/testimoni.php');
    db()->execute("DELETE FROM testimoni WHERE id=?", 'i', [(int) $_GET['hapus']]);
    redirect(BASE_URL . '/admin/testimoni.php', 'Testimoni dihapus.', 'sukses');
}
if (isset($_GET['toggle_featured'])) {
    blockIfSuperadmin(BASE_URL . '/admin/testimoni.php');
    $t = db()->fetchOne("SELECT featured FROM testimoni WHERE id=?", 'i', [(int) $_GET['toggle_featured']]);
    if ($t) {
        db()->execute("UPDATE testimoni SET featured=? WHERE id=?", 'ii', [$t['featured'] ? 0 : 1, (int) $_GET['toggle_featured']]);
    }
    redirect(BASE_URL . '/admin/testimoni.php', 'Featured diperbarui.', 'sukses');
}

$status_filter = sanitize($_GET['status'] ?? '');
$where = $status_filter ? "WHERE status = '$status_filter'" : '';
$testimoni = db()->fetchAll("SELECT * FROM testimoni $where ORDER BY created_at DESC", '', []);

$counts = [];
foreach (db()->fetchAll("SELECT status, COUNT(*) as c FROM testimoni GROUP BY status", '', []) as $r)
    $counts[$r['status']] = $r['c'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimoni — Admin SAH Travel</title>
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

            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="page-title">Testimoni Jamaah</h4>
            </div>

            <div class="filter-tabs mb-4">
                <?php foreach (['' => 'Semua', 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $s => $l): ?>
                    <a href="testimoni.php?status=<?= $s ?>"
                        class="filter-tab <?= $status_filter === $s ? 'active' : '' ?>">
                        <?= $l ?>
                        <?php $cnt = $s ? ($counts[$s] ?? 0) : array_sum($counts);
                        if ($cnt): ?>
                            <span class="tab-count">
                                <?= $cnt ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($testimoni)): ?>
                <div class="row g-3">
                    <?php foreach ($testimoni as $t): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="section-card h-100">
                                <div style="padding:18px 20px">
                                    <!-- Header -->
                                    <div class="d-flex align-items-center gap-10 mb-3" style="gap:10px">
                                        <div
                                            style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--hijau),var(--emas));display:flex;align-items:center;justify-content:center;font-weight:700;color:white;flex-shrink:0">
                                            <?= strtoupper(substr($t['nama'], 0, 1)) ?>
                                        </div>
                                        <div style="flex:1;min-width:0">
                                            <div style="font-weight:700;font-size:.87rem;color:#1a1a1a">
                                                <?= htmlspecialchars($t['nama']) ?>
                                            </div>
                                            <div style="font-size:.72rem;color:#94a3b8">
                                                <?= htmlspecialchars($t['asal_kota'] ?? '') ?> ·
                                                <?= $t['tahun_umrah'] ?? '' ?>
                                            </div>
                                        </div>
                                        <!-- Featured toggle -->
                                        <?php if (!isSuperadmin()): ?>
                                        <a href="testimoni.php?toggle_featured=<?= $t['id'] ?>&status=<?= $status_filter ?>"
                                            title="<?= $t['featured'] ? 'Hapus dari featured' : 'Jadikan featured' ?>"
                                            style="color:<?= $t['featured'] ? 'var(--emas)' : '#d1d5db' ?>;font-size:1.1rem;text-decoration:none">
                                            <i class="bi bi-star-fill"></i>
                                        </a>
                                        <?php elseif ($t['featured']): ?>
                                        <span title="Featured" style="color:var(--emas);font-size:1.1rem">
                                            <i class="bi bi-star-fill"></i>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Rating -->
                                    <div style="color:#f59e0b;font-size:.85rem;margin-bottom:8px">
                                        <?= str_repeat('★', $t['rating']) ?>
                                        <?= str_repeat('☆', 5 - $t['rating']) ?>
                                    </div>
                                    <!-- Paket -->
                                    <?php if ($t['paket']): ?>
                                        <div style="font-size:.72rem;color:var(--hijau);font-weight:600;margin-bottom:8px">
                                            <i class="bi bi-briefcase me-1"></i>
                                            <?= htmlspecialchars($t['paket']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <!-- Isi -->
                                    <p
                                        style="font-size:.83rem;color:#475569;line-height:1.65;font-style:italic;margin-bottom:14px">
                                        "
                                        <?= htmlspecialchars(truncate($t['isi'], 150)) ?>"
                                    </p>
                                    <!-- Status badge -->
                                    <div class="d-flex align-items-center justify-content-between">
                                        <?= statusBadge($t['status']) ?>
                                        <span style="font-size:.72rem;color:#94a3b8">
                                            <?= tglIndo($t['created_at']) ?>
                                        </span>
                                    </div>
                                    <!-- Aksi -->
                                    <?php if (!isSuperadmin()): ?>
                                    <div class="d-flex gap-2 mt-3 flex-wrap">
                                        <?php if ($t['status'] === 'pending'): ?>
                                            <a href="testimoni.php?approve=<?= $t['id'] ?>&status=<?= $status_filter ?>"
                                                class="btn-admin-sm btn-hijau">
                                                <i class="bi bi-check-circle"></i> Setujui
                                            </a>
                                            <a href="testimoni.php?reject=<?= $t['id'] ?>&status=<?= $status_filter ?>"
                                                class="btn-admin-sm btn-merah">
                                                <i class="bi bi-x-circle"></i> Tolak
                                            </a>
                                        <?php elseif ($t['status'] === 'rejected'): ?>
                                            <a href="testimoni.php?approve=<?= $t['id'] ?>&status=<?= $status_filter ?>"
                                                class="btn-admin-sm btn-hijau">
                                                <i class="bi bi-arrow-counterclockwise"></i> Setujui
                                            </a>
                                        <?php else: ?>
                                            <a href="testimoni.php?reject=<?= $t['id'] ?>&status=<?= $status_filter ?>"
                                                class="btn-admin-sm btn-abu">
                                                <i class="bi bi-eye-slash"></i> Sembunyikan
                                            </a>
                                        <?php endif; ?>
                                        <a href="testimoni.php?hapus=<?= $t['id'] ?>&status=<?= $status_filter ?>"
                                            class="btn-admin-sm btn-merah" onclick="return confirm('Hapus testimoni ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="section-card">
                    <div class="empty-cell">
                        <i class="bi bi-chat-quote d-block mb-2" style="font-size:2.5rem;opacity:.25"></i>
                        Tidak ada testimoni
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>