<?php
// admin/booking.php
require_once __DIR__ . '/../config/config.php';
cekAdmin();
$pageTitle = 'Data Booking';

$status_filter = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

$statusList = ['pending', 'confirmed', 'dp_paid', 'lunas', 'berangkat', 'selesai', 'cancelled'];

$where = ['1=1'];
$params = [];
$types = '';

if ($status_filter && in_array($status_filter, $statusList)) {
  $where[] = 'b.status = ?';
  $params[] = $status_filter;
  $types .= 's';
}
if ($search) {
  $where[] = '(b.kode_booking LIKE ? OR b.nama_pemesan LIKE ? OR b.telepon LIKE ?)';
  $s = "%$search%";
  $params[] = $s;
  $params[] = $s;
  $params[] = $s;
  $types .= 'sss';
}

$whereStr = implode(' AND ', $where);

$total = (int) db()->fetchOne(
  "SELECT COUNT(*) as c FROM booking b WHERE $whereStr",
  $types,
  $params
)['c'];
$offset = ($page - 1) * $perPage;

$bookings = db()->fetchAll(
  "SELECT b.*, pu.nama_paket
     FROM booking b
     LEFT JOIN paket_umrah pu ON b.paket_id = pu.id
     WHERE $whereStr
     ORDER BY b.created_at DESC
     LIMIT ? OFFSET ?",
  $types . 'ii',
  array_merge($params, [$perPage, $offset])
);

// Ringkasan cepat per status (untuk kartu di atas tabel)
$ringkasan = db()->fetchOne(
  "SELECT
      COUNT(*) AS total,
      SUM(status = 'pending') AS pending,
      SUM(status IN ('confirmed','dp_paid')) AS diproses,
      SUM(status = 'lunas') AS lunas,
      SUM(status = 'cancelled') AS batal
   FROM booking",
  '',
  []
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Booking — Admin SAH Travel</title>
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
          <h4 class="page-title">Data Booking</h4>
          <p class="text-muted mb-0" style="font-size:.85rem">Total <?= $total ?> booking ditemukan</p>
        </div>
      </div>

      <!-- RINGKASAN -->
      <div class="stats-row mb-3" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
        <div class="section-card" style="padding:16px 18px">
          <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase">Pending</div>
          <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;color:#d97706">
            <?= (int) ($ringkasan['pending'] ?? 0) ?>
          </div>
        </div>
        <div class="section-card" style="padding:16px 18px">
          <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase">Diproses</div>
          <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;color:#2563eb">
            <?= (int) ($ringkasan['diproses'] ?? 0) ?>
          </div>
        </div>
        <div class="section-card" style="padding:16px 18px">
          <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase">Lunas</div>
          <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;color:var(--hijau)">
            <?= (int) ($ringkasan['lunas'] ?? 0) ?>
          </div>
        </div>
        <div class="section-card" style="padding:16px 18px">
          <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase">Dibatalkan</div>
          <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;color:#dc2626">
            <?= (int) ($ringkasan['batal'] ?? 0) ?>
          </div>
        </div>
      </div>

      <!-- FILTER -->
      <div class="filter-tabs mb-3">
        <?php foreach (['' => 'Semua', 'pending' => 'Pending', 'confirmed' => 'Dikonfirmasi', 'dp_paid' => 'DP Terbayar', 'lunas' => 'Lunas', 'berangkat' => 'Berangkat', 'selesai' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $s => $l): ?>
          <a href="booking.php?status=<?= $s ?>" class="filter-tab <?= $status_filter === $s ? 'active' : '' ?>">
            <?= $l ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- SEARCH -->
      <div class="section-card mb-3">
        <div style="padding:14px 20px">
          <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
            <input type="text" name="q" class="form-control" placeholder="Cari kode booking, nama, atau telepon..."
              value="<?= htmlspecialchars($search) ?>" style="max-width:340px;border-radius:10px;font-size:.88rem">
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
                <th>Paket</th>
                <th>Jamaah</th>
                <th>Total Harga</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($bookings)): ?>
                <?php foreach ($bookings as $b): ?>
                  <tr>
                    <td>
                      <a href="booking-detail.php?id=<?= $b['id'] ?>" class="kode-booking" style="text-decoration:none">
                        <?= htmlspecialchars($b['kode_booking']) ?>
                      </a>
                    </td>
                    <td>
                      <div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($b['nama_pemesan']) ?></div>
                      <div style="font-size:.73rem;color:#888"><?= htmlspecialchars($b['telepon']) ?></div>
                    </td>
                    <td style="font-size:.85rem"><?= htmlspecialchars($b['nama_paket'] ?? '-') ?></td>
                    <td><?= (int) $b['jumlah_jamaah'] ?> orang</td>
                    <td style="font-weight:700;color:var(--hijau)"><?= rupiah($b['total_harga']) ?></td>
                    <td><?= statusBadge($b['status']) ?></td>
                    <td style="font-size:.78rem;color:#888"><?= tglIndo($b['created_at']) ?></td>
                    <td>
                      <a href="booking-detail.php?id=<?= $b['id'] ?>" class="btn-admin-sm btn-hijau">
                        <i class="bi bi-eye"></i> Detail
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="empty-cell">
                    <i class="bi bi-calendar-x d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                    Tidak ada data booking
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($total > $perPage): ?>
          <div style="padding:16px 20px;border-top:1px solid #f5f5f5">
            <?= paginate($total, $page, $perPage, "booking.php?status=$status_filter&q=$search") ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>