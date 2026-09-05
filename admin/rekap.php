<?php
// admin/rekap.php
// Halaman rekap (read-only) untuk superadmin — melihat data visa
// tanpa bisa tambah/edit/hapus. CRUD hanya untuk role 'admin' lewat visa.php.
require_once __DIR__ . '/../config/config.php';
requireAdmin();
$pageTitle = 'Rekap Visa Umrah';

$statusAktif = ['dokumen_belum_lengkap', 'dokumen_lengkap', 'menunggu_pembayaran', 'submitted', 'in_process', 'perlu_revisi'];
$statusSelesai = ['approved', 'rejected'];
$semuaStatus = array_merge($statusAktif, $statusSelesai);

$filterStatus = sanitize($_GET['status'] ?? '');
$where = '';
$params = [];
$types = '';
if ($filterStatus && in_array($filterStatus, $semuaStatus)) {
  $where = 'WHERE va.status = ?';
  $types = 's';
  $params = [$filterStatus];
}

$visas = db()->fetchAll(
  "SELECT va.*, b.kode_booking, b.nama_pemesan, b.status AS booking_status
     FROM visa_applications va
     LEFT JOIN booking b ON va.booking_id = b.id
     $where
     ORDER BY
        (va.status IN ('approved','rejected')) ASC,
        va.deadline_keberangkatan IS NULL ASC,
        va.deadline_keberangkatan ASC",
  $types,
  $params
);

// Ringkasan jumlah per status
$ringkasan = db()->fetchOne(
  "SELECT
      COUNT(*) AS total,
      SUM(status IN ('dokumen_belum_lengkap','dokumen_lengkap','menunggu_pembayaran','submitted','in_process','perlu_revisi')) AS proses,
      SUM(status = 'approved') AS approved,
      SUM(status = 'rejected') AS rejected
   FROM visa_applications",
  '',
  []
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rekap Visa Umrah — Admin SAH Travel</title>
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
          <h4 class="page-title">Rekap Visa Umrah</h4>
          <p class="text-muted mb-0" style="font-size:.85rem">Tampilan khusus superadmin — hanya lihat, tidak bisa
            ubah data</p>
        </div>
      </div>

      <!-- RINGKASAN -->
      <div class="mb-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
        <div class="section-card" style="padding:16px 18px">
          <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase">Sedang Diproses</div>
          <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;color:#2563eb">
            <?= (int) ($ringkasan['proses'] ?? 0) ?>
          </div>
        </div>
        <div class="section-card" style="padding:16px 18px">
          <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase">Disetujui</div>
          <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;color:var(--hijau)">
            <?= (int) ($ringkasan['approved'] ?? 0) ?>
          </div>
        </div>
        <div class="section-card" style="padding:16px 18px">
          <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase">Ditolak</div>
          <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;color:#dc2626">
            <?= (int) ($ringkasan['rejected'] ?? 0) ?>
          </div>
        </div>
      </div>

      <!-- FILTER STATUS -->
      <div class="d-flex gap-2 flex-wrap mb-3">
        <a href="rekap.php" class="btn-admin-sm <?= $filterStatus === '' ? 'btn-hijau' : 'btn-abu' ?>">Semua</a>
        <?php foreach ($semuaStatus as $s): ?>
          <a href="rekap.php?status=<?= $s ?>"
            class="btn-admin-sm <?= $filterStatus === $s ? 'btn-hijau' : 'btn-abu' ?>"><?= ucwords(str_replace('_', ' ', $s)) ?></a>
        <?php endforeach; ?>
      </div>

      <!-- TABEL -->
      <div class="section-card">
        <div class="sc-header">
          <div class="sc-title"><i class="bi bi-file-earmark-text me-2" style="color:var(--emas)"></i>Daftar
            Pengajuan Visa</div>
        </div>
        <div style="overflow-x:auto">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Jamaah</th>
                <th>No. Booking</th>
                <th>Status Bayar</th>
                <th>Deadline Berangkat</th>
                <th>Status Visa</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($visas)): ?>
                <?php foreach ($visas as $v):
                  $mepet = false;
                  if ($v['deadline_keberangkatan'] && !in_array($v['status'], ['approved', 'rejected'])) {
                    $sisaHari = (strtotime($v['deadline_keberangkatan']) - time()) / 86400;
                    $mepet = $sisaHari <= 7;
                  }
                  ?>
                  <tr>
                    <td>
                      <div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($v['nama_jamaah']) ?></div>
                      <?php if ($v['no_paspor']): ?>
                        <div style="font-size:.72rem;color:#94a3b8">Paspor: <?= htmlspecialchars($v['no_paspor']) ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td style="font-size:.83rem">
                      <?= htmlspecialchars($v['kode_booking'] ?? '-') ?>
                      <div style="font-size:.72rem;color:#94a3b8"><?= htmlspecialchars($v['nama_pemesan'] ?? '') ?>
                      </div>
                    </td>
                    <td><?= statusBadge($v['booking_status'] ?? '-') ?></td>
                    <td style="font-size:.83rem;<?= $mepet ? 'color:#ef4444;font-weight:700' : '' ?>">
                      <?= $v['deadline_keberangkatan'] ? tglIndo($v['deadline_keberangkatan']) : '<span style="color:#d1d5db">–</span>' ?>
                      <?php if ($mepet): ?><div style="font-size:.7rem"><i
                            class="bi bi-exclamation-triangle-fill"></i> Mendekati deadline</div><?php endif; ?>
                    </td>
                    <td><?= statusBadge($v['status']) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="empty-cell">
                    <i class="bi bi-file-earmark-x d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                    Belum ada pengajuan visa
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>