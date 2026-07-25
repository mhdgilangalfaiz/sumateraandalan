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

    /* SIDEBAR */
    .sidebar {
      position: fixed;
      inset: 0 auto 0 0;
      width: var(--sidebar-w);
      background: linear-gradient(180deg, #0D2B1A, #1B4D2E 100%);
      display: flex;
      flex-direction: column;
      z-index: 200;
      transition: transform .3s;
      box-shadow: 4px 0 24px rgba(0, 0, 0, .15);
      overflow-y: auto
    }

    .sidebar::-webkit-scrollbar {
      width: 4px
    }

    .sidebar::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, .15);
      border-radius: 2px
    }

    .sidebar-brand {
      padding: 24px 20px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      border-bottom: 1px solid rgba(255, 255, 255, .07);
      flex-shrink: 0
    }

    .brand-icon {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--emas), var(--emas-muda));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      color: var(--hijau-tua);
      flex-shrink: 0
    }

    .brand-name {
      font-family: var(--font-display);
      font-size: 1.05rem;
      font-weight: 700;
      color: #fff
    }

    .brand-name span {
      color: var(--emas)
    }

    .brand-sub {
      font-size: .68rem;
      color: rgba(255, 255, 255, .35);
      margin-top: 1px
    }

    .sidebar-nav {
      flex: 1;
      padding: 16px 12px
    }

    .nav-group-label {
      font-size: .62rem;
      font-weight: 700;
      color: rgba(255, 255, 255, .28);
      text-transform: uppercase;
      letter-spacing: 1.8px;
      padding: 12px 10px 5px
    }

    .nav-link-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 10px;
      color: rgba(255, 255, 255, .55);
      text-decoration: none;
      font-size: .855rem;
      font-weight: 500;
      margin-bottom: 2px;
      transition: all .2s
    }

    .nav-link-item:hover {
      background: rgba(255, 255, 255, .08);
      color: rgba(255, 255, 255, .9)
    }

    .nav-link-item.active {
      background: linear-gradient(135deg, rgba(201, 168, 76, .25), rgba(201, 168, 76, .1));
      color: var(--emas-muda);
      border: 1px solid rgba(201, 168, 76, .2)
    }

    .nav-link-item.active i,
    .nav-link-item:hover i {
      color: var(--emas)
    }

    .nav-link-item i {
      font-size: .95rem;
      width: 18px;
      text-align: center;
      flex-shrink: 0;
      color: rgba(255, 255, 255, .4)
    }

    .nav-badge {
      margin-left: auto;
      background: #ef4444;
      color: #fff;
      font-size: .62rem;
      font-weight: 700;
      min-width: 18px;
      height: 18px;
      border-radius: 9px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 5px
    }

    .sidebar-footer {
      padding: 12px;
      border-top: 1px solid rgba(255, 255, 255, .07);
      flex-shrink: 0
    }

    .admin-card {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 10px;
      background: rgba(255, 255, 255, .05);
      margin-bottom: 8px
    }

    .admin-ava {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--hijau-muda), var(--emas));
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: .9rem;
      color: #fff;
      flex-shrink: 0
    }

    .admin-name {
      font-size: .82rem;
      font-weight: 600;
      color: #fff
    }

    .admin-role {
      font-size: .68rem;
      color: rgba(255, 255, 255, .35)
    }

    .logout-link {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 9px 12px;
      border-radius: 10px;
      color: rgba(255, 255, 255, .45);
      text-decoration: none;
      font-size: .82rem;
      transition: all .2s
    }

    .logout-link:hover {
      background: rgba(239, 68, 68, .15);
      color: #fca5a5
    }

    /* OVERLAY */
    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .5);
      z-index: 199
    }

    .sidebar-overlay.show {
      display: block
    }

    /* MAIN */
    .main {
      margin-left: var(--sidebar-w);
      min-height: 100vh;
      display: flex;
      flex-direction: column
    }

    /* TOPBAR */
    .topbar {
      position: sticky;
      top: 0;
      z-index: 100;
      background: #fff;
      padding: 0 28px;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #E8EDF2;
      box-shadow: 0 1px 0 rgba(0, 0, 0, .04)
    }

    .hamburger {
      display: none;
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: #f5f7fa;
      border: none;
      cursor: pointer;
      align-items: center;
      justify-content: center;
      color: #555
    }

    .page-heading {
      font-size: 1rem;
      font-weight: 700;
      color: #1a1a1a
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 10px
    }

    .topbar-date {
      font-size: .78rem;
      color: #94a3b8;
      display: flex;
      align-items: center;
      gap: 5px
    }

    .icon-btn {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: #f5f7fa;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #64748b;
      text-decoration: none;
      transition: all .2s;
      position: relative
    }

    .icon-btn:hover {
      background: #e8edf2;
      color: #1a1a1a
    }

    .notif-dot {
      position: absolute;
      top: 7px;
      right: 7px;
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #ef4444;
      border: 1.5px solid #fff
    }

    /* CONTENT */
    .content {
      padding: 28px;
      flex: 1
    }

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

    @media(max-width:900px) {
      .sidebar {
        transform: translateX(-100%)
      }

      .sidebar.open {
        transform: translateX(0)
      }

      .main {
        margin-left: 0
      }

      .hamburger {
        display: flex
      }

      .topbar {
        padding: 0 16px
      }

      .content {
        padding: 16px
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

  <div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="bi bi-moon-stars-fill"></i></div>
      <div>
        <div class="brand-name">SAH <span>Travel</span></div>
        <div class="brand-sub">Panel Admin</div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-group-label">Utama</div>
      <a href="dashboard.php" class="nav-link-item active"><i class="bi bi-speedometer2"></i>Dashboard</a>
      <a href="booking.php" class="nav-link-item">
        <i class="bi bi-calendar-check"></i>Data Booking
        <?php if (($stats['pending'] ?? 0) > 0): ?><span
            class="nav-badge"><?= $stats['pending'] ?></span><?php endif; ?>
      </a>
      <a href="pembayaran.php" class="nav-link-item">
        <i class="bi bi-credit-card"></i>Pembayaran
        <?php if (($stats['bayar_pending'] ?? 0) > 0): ?><span
            class="nav-badge"><?= $stats['bayar_pending'] ?></span><?php endif; ?>
      </a>
      <div class="nav-group-label">Konten</div>
      <a href="paket.php" class="nav-link-item"><i class="bi bi-briefcase"></i>Paket Umrah</a>
      <a href="jadwal.php" class="nav-link-item"><i class="bi bi-calendar3"></i>Jadwal</a>
      <a href="testimoni.php" class="nav-link-item"><i class="bi bi-chat-quote"></i>Testimoni</a>
      <div class="nav-group-label">Sistem</div>
      <a href="pengaturan.php" class="nav-link-item"><i class="bi bi-gear"></i>Pengaturan</a>
    </nav>
    <div class="sidebar-footer">
      <div class="admin-card">
        <div class="admin-ava"><?= strtoupper(substr($adminNama, 0, 1)) ?></div>
        <div>
          <div class="admin-name"><?= htmlspecialchars($adminNama) ?></div>
          <div class="admin-role"><?= ucfirst($adminRole) ?></div>
        </div>
      </div>
      <a href="logout.php" class="logout-link"><i class="bi bi-box-arrow-left"></i>Keluar</a>
    </div>
  </aside>

  <div class="main">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:14px">
        <button class="hamburger" onclick="toggleSidebar()"><i class="bi bi-list" style="font-size:1.2rem"></i></button>
        <div class="page-heading">Dashboard</div>
      </div>
      <div class="topbar-right">
        <div class="topbar-date"><i class="bi bi-calendar3"></i><?= tglIndo(date('Y-m-d')) ?></div>
        <a href="<?= BASE_URL ?>/index.php" target="_blank" class="icon-btn" title="Lihat website"><i
            class="bi bi-box-arrow-up-right" style="font-size:.85rem"></i></a>
        <a href="booking.php?status=pending" class="icon-btn" title="Notifikasi">
          <i class="bi bi-bell" style="font-size:.9rem"></i>
          <?php if (($stats['pending'] + $stats['bayar_pending']) > 0): ?><span class="notif-dot"></span><?php endif; ?>
        </a>
      </div>
    </div>

    <div class="content">

      <!-- GREETING -->
      <div class="greeting">
        <div class="greeting-content">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
              <h2>Assalamu'alaikum, <?= htmlspecialchars(explode(' ', $adminNama)[0]) ?></h2>
              <p>Berikut ringkasan aktivitas SAH Travel hari ini</p>
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
                <div class="quick-icon" style="background:#FEF3C7;color:#d97706"><i class="bi bi-hourglass-split"></i>
                </div>
                <div class="quick-label">Booking Pending</div>
              </a>
              <a href="pembayaran.php?status=pending" class="quick-item">
                <div class="quick-icon" style="background:#DBEAFE;color:#2563eb"><i class="bi bi-credit-card-fill"></i>
                </div>
                <div class="quick-label">Verifikasi Bayar</div>
              </a>
              <a href="pengaturan.php" class="quick-item">
                <div class="quick-icon" style="background:#EDE9FE;color:#7c3aed"><i class="bi bi-gear-fill"></i></div>
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
              <div class="empty-pay"><i class="bi bi-check-circle-fill"></i>Semua pembayaran sudah terverifikasi</div>
            <?php endif; ?>
          </div>

        </div>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('open');
      document.getElementById('overlay').classList.toggle('show');
    }
    function closeSidebar() {
      document.getElementById('sidebar').classList.remove('open');
      document.getElementById('overlay').classList.remove('show');
    }
    window.addEventListener('resize', () => { if (window.innerWidth > 900) closeSidebar(); });
  </script>
</body>

</html>