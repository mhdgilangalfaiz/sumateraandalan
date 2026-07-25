<?php
// admin/pembayaran.php
require_once __DIR__ . '/../config/config.php';
cekAdmin();
$pageTitle = 'Pembayaran';

$status_filter = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

$where = ['1=1'];
$params = [];
$types = '';

if ($status_filter) {
  $where[] = 'p.status = ?';
  $params[] = $status_filter;
  $types .= 's';
}
if ($search) {
  $where[] = '(b.kode_booking LIKE ? OR b.nama_pemesan LIKE ?)';
  $s = "%$search%";
  $params[] = $s;
  $params[] = $s;
  $types .= 'ss';
}

$whereStr = implode(' AND ', $where);
$total = (int) db()->fetchOne(
  "SELECT COUNT(*) as c FROM pembayaran p JOIN booking b ON p.booking_id = b.id WHERE $whereStr",
  $types,
  $params
)['c'];
$offset = ($page - 1) * $perPage;

$pembayaran = db()->fetchAll(
  "SELECT p.*, b.kode_booking, b.nama_pemesan, b.telepon, b.total_harga
     FROM pembayaran p
     JOIN booking b ON p.booking_id = b.id
     WHERE $whereStr
     ORDER BY p.created_at DESC
     LIMIT ? OFFSET ?",
  $types . 'ii',
  array_merge($params, [$perPage, $offset])
);

// Badge pembayaran
function badgePembayaran(string $s): string
{
  $m = ['pending' => ['#92400e', '#fef3c7', 'Menunggu'], 'verified' => ['#14532d', '#bbf7d0', 'Terverifikasi'], 'rejected' => ['#991b1b', '#fee2e2', 'Ditolak']];
  [$c, $bg, $l] = $m[$s] ?? ['#555', '#eee', ucfirst($s)];
  return "<span style='background:$bg;color:$c;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700'>$l</span>";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pembayaran — Admin SAH Travel</title>
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
        <div>
          <h4 class="page-title">Pembayaran</h4>
          <p class="text-muted mb-0" style="font-size:.85rem">Total <?= $total ?> transaksi</p>
        </div>
      </div>

      <!-- FILTER -->
      <div class="filter-tabs mb-3">
        <?php foreach (['' => 'Semua', 'pending' => 'Menunggu', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'] as $s => $l): ?>
          <a href="pembayaran.php?status=<?= $s ?>" class="filter-tab <?= $status_filter === $s ? 'active' : '' ?>">
            <?= $l ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- SEARCH -->
      <div class="section-card mb-3">
        <div style="padding:14px 20px">
          <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
            <input type="text" name="q" class="form-control" placeholder="Cari kode booking atau nama..."
              value="<?= htmlspecialchars($search) ?>" style="max-width:320px;border-radius:10px;font-size:.88rem">
            <button type="submit" class="btn-admin-sm btn-hijau"><i class="bi bi-search"></i> Cari</button>
          </form>
        </div>
      </div>

      <!-- TABLE -->
      <div class="section-card">
        <div style="overflow-x:auto">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Kode Booking</th>
                <th>Nama Pemesan</th>
                <th>Jenis</th>
                <th>Jumlah</th>
                <th>Metode</th>
                <th>Bukti</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($pembayaran)): ?>
                <?php foreach ($pembayaran as $py): ?>
                  <tr>
                    <td>
                      <a href="booking-detail.php?id=<?= $py['booking_id'] ?>" class="kode-booking"
                        style="text-decoration:none">
                        <?= $py['kode_booking'] ?>
                      </a>
                    </td>
                    <td>
                      <div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($py['nama_pemesan']) ?></div>
                      <div style="font-size:.73rem;color:#888"><?= htmlspecialchars($py['telepon']) ?></div>
                    </td>
                    <td><?= ucfirst($py['jenis']) ?></td>
                    <td style="font-weight:700;color:var(--hijau)"><?= rupiah($py['jumlah']) ?></td>
                    <td style="font-size:.82rem">
                      <?= htmlspecialchars($py['bank'] ? $py['bank'] . ' · ' . $py['metode'] : ($py['metode'] ?? '-')) ?></td>
                    <td>
                      <?php if ($py['bukti_bayar']): ?>
                        <a href="<?= UPLOAD_URL . $py['bukti_bayar'] ?>" target="_blank" class="btn-admin-sm btn-abu">
                          <i class="bi bi-image"></i> Lihat
                        </a>
                      <?php else: ?><span style="color:#ccc;font-size:.8rem">–</span><?php endif; ?>
                    </td>
                    <td><?= badgePembayaran($py['status']) ?></td>
                    <td style="font-size:.78rem;color:#888"><?= tglIndo($py['created_at']) ?></td>
                    <td>
                      <?php if ($py['status'] === 'pending'): ?>
                        <a href="booking-detail.php?id=<?= $py['booking_id'] ?>" class="btn-admin-sm btn-hijau">
                          <i class="bi bi-check-circle"></i> Verifikasi
                        </a>
                      <?php else: ?>
                        <a href="booking-detail.php?id=<?= $py['booking_id'] ?>" class="btn-admin-sm btn-abu">
                          <i class="bi bi-eye"></i> Detail
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="9" class="empty-cell">
                    <i class="bi bi-credit-card d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                    Tidak ada data pembayaran
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($total > $perPage): ?>
          <div style="padding:16px 20px;border-top:1px solid #f5f5f5">
            <?= paginate($total, $page, $perPage, "pembayaran.php?status=$status_filter&q=$search") ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>