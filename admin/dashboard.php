<?php
// admin/dashboard.php — FIXED
require_once __DIR__ . '/../config/config.php';
cekAdmin();

// ── STATISTIK ────────────────────────────────────────────────
$stats = db()->fetchOne("
    SELECT
        (SELECT COUNT(*) FROM booking)                                             AS total_booking,
        (SELECT COUNT(*) FROM booking WHERE status = 'pending')                   AS pending,
        (SELECT COUNT(*) FROM booking WHERE status IN ('confirmed','dp_paid'))    AS diproses,
        (SELECT COUNT(*) FROM booking WHERE status = 'lunas')                     AS lunas,
        (SELECT COUNT(*) FROM booking WHERE DATE(created_at) = CURDATE())         AS hari_ini,
        (SELECT COALESCE(SUM(jumlah),0) FROM pembayaran WHERE status='verified')  AS total_pemasukan,
        (SELECT COUNT(*) FROM pembayaran WHERE status = 'pending')                AS bayar_pending,
        (SELECT COUNT(*) FROM paket_umrah WHERE status = 'aktif')                 AS paket_aktif
", '', []);

// ── BOOKING TERBARU ──────────────────────────────────────────
$bookings = db()->fetchAll("
    SELECT b.*, pu.nama_paket
    FROM booking b
    LEFT JOIN paket_umrah pu ON b.paket_id = pu.id
    ORDER BY b.created_at DESC
    LIMIT 8
", '', []);

// ── PEMBAYARAN PENDING ───────────────────────────────────────
$bayar_pending = db()->fetchAll("
    SELECT p.*, b.kode_booking, b.data_jamaah
    FROM pembayaran p
    JOIN booking b ON p.booking_id = b.id
    WHERE p.status = 'pending'
    ORDER BY p.created_at DESC
    LIMIT 5
", '', []);

// ── VARIABEL ADMIN ───────────────────────────────────────────
$adminNama = $_SESSION['admin_nama'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';
$pageTitle = 'Dashboard';

// ── DATA KHUSUS SUPERADMIN (rekap read-only per layanan) ─────
$rekapVisa = null;
$rekapStaff = null;
if (isSuperadmin()) {
    $rekapVisa = db()->fetchOne(
        "SELECT
            COUNT(*) AS total,
            SUM(status IN ('dokumen_belum_lengkap','dokumen_lengkap','menunggu_pembayaran','submitted','in_process','perlu_revisi')) AS proses,
            SUM(status = 'approved') AS approved,
            SUM(status = 'rejected') AS rejected
         FROM visa_applications",
        '',
        []
    );

    $rekapStaff = db()->fetchOne(
        "SELECT
            COUNT(*) AS total,
            SUM(status = 1) AS aktif,
            SUM(role = 'admin' AND status = 1) AS admin_aktif,
            SUM(role = 'superadmin' AND status = 1) AS superadmin_aktif
         FROM admins",
        '',
        []
    );

    $daftarStaff = db()->fetchAll(
        "SELECT id, nama, role, status, last_login, last_activity FROM admins ORDER BY status DESC, last_activity DESC LIMIT 6",
        '',
        []
    );
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Admin SAH Travel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
    rel="stylesheet">
  <?php include __DIR__ . '/inc/admin-style.php'; ?>
  <style>
    :root {
      --hijau-tua: #1B4D2E;
      --hijau: #1B6B3A;
      --hijau-muda: #2E8B57;
      --emas: #C9A84C;
      --emas-muda: #E8C97A;
      --sidebar-w: 260px;
      --font-display: 'Playfair Display', serif;
      --font-body: 'DM Sans', sans-serif
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    body {
      font-family: var(--font-body);
      background: #F0F4F8;
      min-height: 100vh
    }

    /* Sidebar, topbar, main, dan content sudah disediakan oleh inc/admin-style.php */

    /* GREETING */
    .greeting {
      background: linear-gradient(135deg, #0D2B1A, #1B4D2E 55%, #1B6B3A);
      border-radius: 20px;
      padding: 28px 32px;
      margin-bottom: 24px;
      position: relative;
      overflow: hidden
    }

    .greeting::before {
      content: '';
      position: absolute;
      inset: 0;
      opacity: .06;
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23C9A84C'%3E%3Cpath d='M30 0L39 20.5H60L42.5 33.2L49.5 53.5L30 40.5L10.5 53.5L17.5 33.2L0 20.5H21L30 0Z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")
    }

    .greeting-content {
      position: relative;
      z-index: 2
    }

    .greeting h2 {
      font-family: var(--font-display);
      font-size: 1.4rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 4px
    }

    .greeting p {
      color: rgba(255, 255, 255, .6);
      font-size: .875rem;
      margin: 0
    }

    .greeting-stats {
      display: flex;
      gap: 20px;
      margin-top: 18px;
      flex-wrap: wrap
    }

    .g-stat-num {
      font-family: var(--font-display);
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--emas);
      line-height: 1
    }

    .g-stat-label {
      font-size: .7rem;
      color: rgba(255, 255, 255, .5);
      margin-top: 2px;
      text-transform: uppercase;
      letter-spacing: .5px
    }

    /* STAT CARDS */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 24px
    }

    .stat-card {
      background: #fff;
      border-radius: 16px;
      padding: 20px;
      border: 1px solid #E8EDF2;
      transition: all .25s;
      position: relative;
      overflow: hidden
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      border-radius: 16px 16px 0 0
    }

    .stat-card.green::before {
      background: linear-gradient(90deg, var(--hijau), var(--hijau-muda))
    }

    .stat-card.gold::before {
      background: linear-gradient(90deg, var(--emas), var(--emas-muda))
    }

    .stat-card.blue::before {
      background: linear-gradient(90deg, #3b82f6, #60a5fa)
    }

    .stat-card.purple::before {
      background: linear-gradient(90deg, #8b5cf6, #a78bfa)
    }

    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, .08)
    }

    .stat-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 14px
    }

    .stat-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem
    }

    .stat-icon.green {
      background: #E8F5EE;
      color: var(--hijau)
    }

    .stat-icon.gold {
      background: #FEF3C7;
      color: #d97706
    }

    .stat-icon.blue {
      background: #DBEAFE;
      color: #2563eb
    }

    .stat-icon.purple {
      background: #EDE9FE;
      color: #7c3aed
    }

    .stat-label {
      font-size: .73rem;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: .5px;
      font-weight: 600
    }

    .stat-num {
      font-family: var(--font-display);
      font-size: 2rem;
      font-weight: 700;
      line-height: 1;
      margin-top: 2px
    }

    .stat-num.green {
      color: var(--hijau)
    }

    .stat-num.gold {
      color: #d97706
    }

    .stat-num.blue {
      color: #2563eb
    }

    .stat-num.purple {
      color: #7c3aed
    }

    .stat-sub {
      font-size: .73rem;
      color: #94a3b8;
      margin-top: 6px
    }

    .badge-up {
      font-size: .68rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 20px;
      background: #dcfce7;
      color: #16a34a;
      display: inline-flex;
      align-items: center;
      gap: 3px
    }

    /* GRID */
    .content-grid {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 20px
    }

    /* CARD BOX */
    .card-box {
      background: #fff;
      border-radius: 16px;
      border: 1px solid #E8EDF2;
      overflow: hidden;
      margin-bottom: 0
    }

    .card-head {
      padding: 18px 22px;
      border-bottom: 1px solid #F1F5F9;
      display: flex;
      align-items: center;
      justify-content: space-between
    }

    .card-head-title {
      font-size: .9rem;
      font-weight: 700;
      color: #1a1a1a;
      display: flex;
      align-items: center;
      gap: 8px
    }

    .card-head-icon {
      width: 30px;
      height: 30px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .85rem
    }

    .card-head-icon.green {
      background: #E8F5EE;
      color: var(--hijau)
    }

    .card-head-icon.gold {
      background: #FEF3C7;
      color: #d97706
    }

    .card-link {
      font-size: .78rem;
      color: var(--hijau);
      text-decoration: none;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px
    }

    .card-link:hover {
      text-decoration: underline
    }

    /* TABLE */
    .tbl {
      width: 100%;
      border-collapse: collapse
    }

    .tbl thead th {
      padding: 10px 18px;
      text-align: left;
      font-size: .7rem;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: .5px;
      background: #FAFBFC;
      white-space: nowrap
    }

    .tbl tbody td {
      padding: 13px 18px;
      font-size: .83rem;
      border-top: 1px solid #F8FAFC;
      color: #374151;
      vertical-align: middle
    }

    .tbl tbody tr:hover td {
      background: #FAFBFC
    }

    .kode {
      font-family: monospace;
      font-size: .78rem;
      background: #F1F5F9;
      padding: 3px 8px;
      border-radius: 6px;
      color: #475569;
      font-weight: 600
    }

    .nama-cell .nama {
      font-weight: 600;
      color: #1a1a1a;
      font-size: .85rem
    }

    .nama-cell .sub {
      font-size: .72rem;
      color: #94a3b8;
      margin-top: 1px
    }

    .price {
      font-weight: 700;
      color: var(--hijau)
    }

    .empty-state-sm {
      padding: 40px 20px;
      text-align: center;
      color: #94a3b8
    }

    .empty-state-sm i {
      font-size: 2.5rem;
      display: block;
      margin-bottom: 8px;
      opacity: .3
    }

    /* SIDE */
    .side-stack {
      display: flex;
      flex-direction: column;
      gap: 20px
    }

    .quick-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      padding: 16px
    }

    .quick-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      padding: 16px 8px;
      border-radius: 12px;
      text-decoration: none;
      border: 1.5px solid #E8EDF2;
      background: #fff;
      transition: all .2s;
      cursor: pointer
    }

    .quick-item:hover {
      border-color: var(--hijau);
      background: #F0FDF4;
      transform: translateY(-2px)
    }

    .quick-icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem
    }

    .quick-label {
      font-size: .72rem;
      font-weight: 700;
      color: #475569;
      text-align: center;
      line-height: 1.3
    }

    .pay-item {
      padding: 14px 20px;
      border-top: 1px solid #F8FAFC;
      display: flex;
      align-items: center;
      gap: 12px
    }

    .pay-item:first-child {
      border-top: none
    }

    .pay-ava {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--hijau), var(--emas));
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: .85rem;
      color: #fff;
      flex-shrink: 0
    }

    .pay-info {
      flex: 1;
      min-width: 0
    }

    .pay-nama {
      font-size: .83rem;
      font-weight: 600;
      color: #1a1a1a;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis
    }

    .pay-sub {
      font-size: .72rem;
      color: #94a3b8;
      margin-top: 1px
    }

    .pay-right {
      text-align: right;
      flex-shrink: 0
    }

    .pay-jml {
      font-size: .83rem;
      font-weight: 700;
      color: var(--hijau)
    }

    .btn-verif {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: var(--hijau);
      color: #fff;
      border: none;
      border-radius: 7px;
      padding: 4px 10px;
      font-size: .7rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: background .2s;
      margin-top: 4px
    }

    .btn-verif:hover {
      background: var(--hijau-tua);
      color: #fff
    }

    .empty-pay {
      padding: 24px;
      text-align: center;
      color: #94a3b8;
      font-size: .82rem
    }

    .empty-pay i {
      display: block;
      font-size: 1.8rem;
      margin-bottom: 6px;
      color: #bbf7d0
    }

    /* RESPONSIVE */
    @media(max-width:1200px) {
      .stats-row {
        grid-template-columns: repeat(2, 1fr)
      }

      .content-grid {
        grid-template-columns: 1fr
      }

      .side-stack {
        display: grid;
        grid-template-columns: 1fr 1fr
      }
    }

    @media(max-width:600px) {
      .stats-row {
        grid-template-columns: 1fr 1fr;
        gap: 12px
      }

      .side-stack {
        grid-template-columns: 1fr
      }
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/inc/sidebar.php'; ?>

  <div class="main">
    <?php include __DIR__ . '/inc/topbar.php'; ?>

    <div class="content">

      <!-- GREETING -->
      <div class="greeting">
        <div class="greeting-content">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
              <h2>Assalamu'alaikum, <?= htmlspecialchars(explode(' ', $adminNama)[0]) ?></h2>
              <p><?= isSuperadmin() ? 'Ringkasan bisnis SAH Travel — akun Anda khusus untuk melihat rekap' : 'Berikut ringkasan aktivitas SAH Travel hari ini' ?></p>
            </div>
            <div style="text-align:right">
              <div style="font-size:.72rem;color:rgba(255,255,255,.4);margin-bottom:2px">Paket Aktif</div>
              <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;color:var(--emas)">
                <?= $stats['paket_aktif'] ?? 0 ?>
              </div>
            </div>
          </div>
          <div class="greeting-stats">
            <div>
              <div class="g-stat-num"><?= $stats['total_booking'] ?? 0 ?></div>
              <div class="g-stat-label">Total Booking</div>
            </div>
            <div>
              <div class="g-stat-num"><?= $stats['lunas'] ?? 0 ?></div>
              <div class="g-stat-label">Lunas</div>
            </div>
            <div>
              <div class="g-stat-num"><?= $stats['diproses'] ?? 0 ?></div>
              <div class="g-stat-label">Diproses</div>
            </div>
            <div>
              <div class="g-stat-num">Rp <?= number_format(($stats['total_pemasukan'] ?? 0) / 1000000, 1) ?>Jt</div>
              <div class="g-stat-label">Pemasukan</div>
            </div>
          </div>
        </div>
      </div>

      <!-- STAT CARDS -->
      <div class="stats-row">
        <div class="stat-card green">
          <div class="stat-top">
            <div>
              <div class="stat-label">Total Booking</div>
              <div class="stat-num green"><?= $stats['total_booking'] ?? 0 ?></div>
            </div>
            <div class="stat-icon green"><i class="bi bi-people-fill"></i></div>
          </div>
          <div class="stat-sub"><span class="badge-up"><i
                class="bi bi-arrow-up"></i><?= $stats['hari_ini'] ?? 0 ?></span> hari ini</div>
        </div>
        <div class="stat-card gold">
          <div class="stat-top">
            <div>
              <div class="stat-label">Menunggu Konfirmasi</div>
              <div class="stat-num gold"><?= $stats['pending'] ?? 0 ?></div>
            </div>
            <div class="stat-icon gold"><i class="bi bi-hourglass-split"></i></div>
          </div>
          <div class="stat-sub">perlu tindakan segera</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-top">
            <div>
              <div class="stat-label">Pembayaran Pending</div>
              <div class="stat-num blue"><?= $stats['bayar_pending'] ?? 0 ?></div>
            </div>
            <div class="stat-icon blue"><i class="bi bi-credit-card-fill"></i></div>
          </div>
          <div class="stat-sub">menunggu verifikasi</div>
        </div>
        <div class="stat-card purple">
          <div class="stat-top">
            <div>
              <div class="stat-label">Total Pemasukan</div>
              <div class="stat-num purple" style="font-size:1.25rem;margin-top:6px">
                Rp <?= number_format($stats['total_pemasukan'] ?? 0, 0, ',', '.') ?>
              </div>
            </div>
            <div class="stat-icon purple"><i class="bi bi-cash-stack"></i></div>
          </div>
          <div class="stat-sub">pembayaran terverifikasi</div>
        </div>
      </div>

      <?php if (isSuperadmin()): ?>
        <!-- ============ TAMPILAN SUPERADMIN: REKAP READ-ONLY ============ -->
        <div class="content-grid" style="grid-template-columns:1fr">

          <div class="card-box">
            <div class="card-head">
              <div class="card-head-title">
                <div class="card-head-icon green"><i class="bi bi-bar-chart-fill"></i></div>
                Rekap per Layanan
              </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;padding:4px">

              <!-- REKAP PAKET -->
              <div class="stat-card green" style="margin:0">
                <div class="stat-top">
                  <div>
                    <div class="stat-label">Paket Umrah Aktif</div>
                    <div class="stat-num green"><?= $stats['paket_aktif'] ?? 0 ?></div>
                  </div>
                  <div class="stat-icon green"><i class="bi bi-briefcase-fill"></i></div>
                </div>
                <div class="stat-sub">Rp <?= number_format($stats['total_pemasukan'] ?? 0, 0, ',', '.') ?> total
                  pemasukan terverifikasi</div>
              </div>

              <!-- REKAP VISA -->
              <div class="stat-card gold" style="margin:0">
                <div class="stat-top">
                  <div>
                    <div class="stat-label">Visa Umrah</div>
                    <div class="stat-num gold"><?= (int) ($rekapVisa['total'] ?? 0) ?></div>
                  </div>
                  <div class="stat-icon gold"><i class="bi bi-file-earmark-text-fill"></i></div>
                </div>
                <div class="stat-sub">
                  <?= (int) ($rekapVisa['proses'] ?? 0) ?> diproses ·
                  <?= (int) ($rekapVisa['approved'] ?? 0) ?> disetujui ·
                  <?= (int) ($rekapVisa['rejected'] ?? 0) ?> ditolak
                </div>
                <a href="visa.php" class="card-link" style="margin-top:8px">Lihat detail <i
                    class="bi bi-arrow-right"></i></a>
              </div>

            </div>
          </div>

          <div class="card-box">
            <div class="card-head">
              <div class="card-head-title">
                <div class="card-head-icon gold"><i class="bi bi-people-fill"></i></div>
                Kelola Akun Staff
              </div>
              <a href="kelola-staff.php" class="card-link">Lihat semua <i class="bi bi-arrow-right"></i></a>
            </div>
            <div style="display:flex;align-items:baseline;gap:10px;padding:2px 2px 10px">
              <div style="font-family:var(--font-display);font-size:1.9rem;font-weight:700;color:var(--hijau)">
                <?= (int) ($rekapStaff['aktif'] ?? 0) ?>
              </div>
              <div style="font-size:.8rem;color:#64748b">
                akun staff aktif
                (<?= (int) ($rekapStaff['admin_aktif'] ?? 0) ?> Admin,
                <?= (int) ($rekapStaff['superadmin_aktif'] ?? 0) ?> Superadmin)
                <?php $nonaktifStaff = (int) ($rekapStaff['total'] ?? 0) - (int) ($rekapStaff['aktif'] ?? 0); ?>
                <?php if ($nonaktifStaff > 0): ?>
                  <div style="font-size:.72rem;color:#94a3b8">+<?= $nonaktifStaff ?> akun nonaktif</div>
                <?php endif; ?>
              </div>
            </div>

            <!-- DAFTAR STAFF -->
            <div style="display:flex;flex-direction:column;gap:2px;border-top:1px solid #f1f5f9;padding-top:8px">
              <?php foreach ($daftarStaff as $s):
                // Online = heartbeat terakhir diterima kurang dari 45 detik lalu
                // (heartbeat dikirim tiap 25 detik, jadi 45 detik kasih toleransi 1x gagal kirim)
                $online = $s['last_activity'] && (time() - strtotime($s['last_activity'])) < 45;
                $lastSeen = $s['last_activity'] ?: $s['last_login'];
                ?>
                <div class="pay-item">
                  <div class="pay-ava" style="position:relative">
                    <?= strtoupper(substr($s['nama'], 0, 1)) ?>
                    <?php if ($online): ?>
                      <span
                        style="position:absolute;bottom:-1px;right:-1px;width:9px;height:9px;background:#22c55e;border:2px solid #fff;border-radius:50%"></span>
                    <?php endif; ?>
                  </div>
                  <div class="pay-info">
                    <div class="pay-nama">
                      <?= htmlspecialchars($s['nama']) ?>
                      <span
                        style="font-size:.65rem;font-weight:600;color:<?= $s['role'] === 'superadmin' ? '#1B6B3A' : '#94a3b8' ?>">
                        · <?= ucfirst($s['role']) ?>
                      </span>
                    </div>
                    <div class="pay-sub">
                      <?php if ($s['status'] == 0): ?>
                        <span style="color:#dc2626">Nonaktif</span>
                      <?php elseif ($online): ?>
                        <span style="color:#22c55e;font-weight:600">Online sekarang</span>
                      <?php elseif ($lastSeen): ?>
                        Terakhir online <?= timeAgo($lastSeen) ?>
                      <?php else: ?>
                        Belum pernah login
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="pay-right">
                    <?php if ($s['role'] === 'superadmin'): ?>
                      <span style="font-size:.68rem;color:#c7d2d9;font-weight:600">
                        <?= $s['id'] == $_SESSION['admin_id'] ? 'Anda' : '–' ?>
                      </span>
                    <?php elseif ($s['status'] == 1): ?>
                      <a href="kelola-staff.php?nonaktifkan=<?= $s['id'] ?>" class="btn-verif" style="background:#fee2e2;color:#dc2626"
                        onclick="return confirm('Nonaktifkan akun <?= htmlspecialchars($s['nama']) ?>?')">
                        <i class="bi bi-slash-circle"></i>Nonaktifkan
                      </a>
                    <?php else: ?>
                      <a href="kelola-staff.php?aktifkan=<?= $s['id'] ?>" class="btn-verif">
                        <i class="bi bi-check-circle"></i>Aktifkan
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="quick-grid" style="margin-top:10px">
              <a href="kelola-staff.php#formTambah" class="quick-item">
                <div class="quick-icon" style="background:#E8F5EE;color:var(--hijau)"><i
                    class="bi bi-person-plus-fill"></i></div>
                <div class="quick-label">Tambah Akun Staff</div>
              </a>
              <a href="kelola-staff.php" class="quick-item">
                <div class="quick-icon" style="background:#EDE9FE;color:#7c3aed"><i class="bi bi-gear-fill"></i>
                </div>
                <div class="quick-label">Kelola Semua Akun</div>
              </a>
            </div>
          </div>

        </div>
      <?php else: ?>
        <!-- ============ TAMPILAN ADMIN: OPERASIONAL (CRUD) ============ -->
        <!-- CONTENT GRID -->
        <div class="content-grid">

          <!-- BOOKING TERBARU -->
          <div class="card-box">
            <div class="card-head">
              <div class="card-head-title">
                <div class="card-head-icon green"><i class="bi bi-calendar-check-fill"></i></div>
                Booking Terbaru
              </div>
              <a href="booking.php" class="card-link">Lihat semua <i class="bi bi-arrow-right"></i></a>
            </div>
            <?php if (!empty($bookings)): ?>
              <div style="overflow-x:auto">
                <table class="tbl">
                  <thead>
                    <tr>
                      <th>Kode</th>
                      <th>Nama Pemesan</th>
                      <th>Paket</th>
                      <th>Total</th>
                      <th>Status</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($bookings as $b):
                      // Ambil nama dari data_jamaah (karena tidak ada kolom nama_pemesan)
                      $namaP = 'Tamu';
                      if (!empty($b['data_jamaah'])) {
                        $dj = json_decode($b['data_jamaah'], true);
                        $namaP = $dj[0]['nama'] ?? 'Tamu';
                      }
                      $telpP = '';
                      if (!empty($b['data_jamaah'])) {
                        $dj = json_decode($b['data_jamaah'], true);
                        $telpP = $dj[0]['telepon'] ?? '';
                      }
                      ?>
                      <tr>
                        <td><span class="kode"><?= htmlspecialchars($b['kode_booking']) ?></span></td>
                        <td>
                          <div class="nama-cell">
                            <div class="nama"><?= htmlspecialchars($namaP) ?></div>
                            <div class="sub"><?= $b['jumlah_jamaah'] ?> jamaah &middot;
                              <?= tglIndo(date('Y-m-d', strtotime($b['created_at']))) ?>
                            </div>
                          </div>
                        </td>
                        <td style="font-size:.8rem;color:#64748b">
                          <?= htmlspecialchars(mb_strimwidth($b['nama_paket'] ?? '-', 0, 25, '…')) ?>
                        </td>
                        <td><span class="price">Rp <?= number_format($b['total_harga'], 0, ',', '.') ?></span></td>
                        <td><?= statusBadge($b['status']) ?></td>
                        <td><a href="booking-detail.php?id=<?= $b['id'] ?>"
                            style="color:var(--hijau);font-size:.78rem;text-decoration:none;font-weight:600;white-space:nowrap">Detail
                            →</a></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="empty-state-sm"><i class="bi bi-calendar-x"></i>Belum ada data booking</div>
            <?php endif; ?>
          </div>

          <!-- SIDE -->
          <div class="side-stack">

            <!-- QUICK ACTIONS -->
            <div class="card-box">
              <div class="card-head">
                <div class="card-head-title">
                  <div class="card-head-icon gold"><i class="bi bi-lightning-charge-fill"></i></div>Aksi Cepat
                </div>
              </div>
              <div class="quick-grid">
                <a href="paket.php" class="quick-item">
                  <div class="quick-icon" style="background:#E8F5EE;color:var(--hijau)"><i
                      class="bi bi-plus-circle-fill"></i></div>
                  <div class="quick-label">Tambah Paket</div>
                </a>
                <a href="booking.php?status=pending" class="quick-item">
                  <div class="quick-icon" style="background:#FEF3C7;color:#d97706"><i
                      class="bi bi-hourglass-split"></i>
                  </div>
                  <div class="quick-label">Booking Pending</div>
                </a>
                <a href="pembayaran.php?status=pending" class="quick-item">
                  <div class="quick-icon" style="background:#DBEAFE;color:#2563eb"><i
                      class="bi bi-credit-card-fill"></i>
                  </div>
                  <div class="quick-label">Verifikasi Bayar</div>
                </a>
                <a href="pengaturan.php" class="quick-item">
                  <div class="quick-icon" style="background:#EDE9FE;color:#7c3aed"><i class="bi bi-gear-fill"></i>
                  </div>
                  <div class="quick-label">Pengaturan</div>
                </a>
              </div>
            </div>

            <!-- PEMBAYARAN PENDING -->
            <div class="card-box">
              <div class="card-head">
                <div class="card-head-title">
                  <div class="card-head-icon gold"><i class="bi bi-credit-card-fill"></i></div>Pembayaran Masuk
                </div>
                <a href="pembayaran.php" class="card-link">Semua <i class="bi bi-arrow-right"></i></a>
              </div>
              <?php if (!empty($bayar_pending)): ?>
                <?php foreach ($bayar_pending as $py):
                  $namaP2 = 'Tamu';
                  if (!empty($py['data_jamaah'])) {
                    $dj2 = json_decode($py['data_jamaah'], true);
                    $namaP2 = $dj2[0]['nama'] ?? 'Tamu';
                  }
                  ?>
                  <div class="pay-item">
                    <div class="pay-ava"><?= strtoupper(substr($namaP2, 0, 1)) ?></div>
                    <div class="pay-info">
                      <div class="pay-nama"><?= htmlspecialchars($namaP2) ?></div>
                      <div class="pay-sub"><?= htmlspecialchars($py['kode_booking']) ?></div>
                    </div>
                    <div class="pay-right">
                      <div class="pay-jml">Rp <?= number_format($py['jumlah'], 0, ',', '.') ?></div>
                      <a href="booking-detail.php?id=<?= $py['booking_id'] ?>" class="btn-verif">
                        <i class="bi bi-check-circle"></i>Verifikasi
                      </a>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="empty-pay"><i class="bi bi-check-circle-fill"></i>Semua pembayaran sudah terverifikasi
                </div>
              <?php endif; ?>
            </div>

          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>