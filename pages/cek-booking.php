<?php
// pages/cek-booking.php
require_once __DIR__ . '/../config/config.php';

$settings = [];
foreach (db()->fetchAll("SELECT nama_key, nilai FROM pengaturan") as $p)
  $settings[$p['nama_key']] = $p['nilai'];

$booking = null;
$pembayaran = [];
$error = '';
$kode_input = strtoupper(sanitize($_GET['kode'] ?? $_POST['kode'] ?? ''));
$kontak_input = trim(sanitize($_GET['kontak'] ?? $_POST['kontak'] ?? ''));
$sudahCari = $kode_input !== '' || $kontak_input !== '';

if ($sudahCari) {
  if ($kode_input === '' || $kontak_input === '') {
    $error = 'Mohon isi kode booking dan email/no. HP yang digunakan saat mendaftar.';
  } else {
    // Verifikasi ganda: kode booking HARUS cocok dengan email ATAU telepon
    // pemilik booking. Ini mencegah orang menebak-nebak kode booking secara
    // berurutan untuk melihat data booking milik orang lain.
    $booking = db()->fetchOne(
      "SELECT b.*, pu.nama_paket, pu.durasi, pu.maskapai,
                  pu.hotel_mekkah, pu.bintang_mekkah,
                  pu.hotel_madinah, pu.bintang_madinah,
                  j.tanggal_berangkat, j.tanggal_pulang
           FROM booking b
           LEFT JOIN paket_umrah pu ON b.paket_id = pu.id
           LEFT JOIN jadwal j ON b.jadwal_id = j.id
           WHERE b.kode_booking = ? AND (b.email = ? OR b.telepon = ?)",
      'sss',
      [$kode_input, $kontak_input, $kontak_input]
    );

    if (!$booking) {
      // Pesan sengaja umum (tidak bilang "kode salah" atau "kontak salah"
      // secara spesifik) supaya tidak membantu orang menebak kombinasi yang benar.
      $error = 'Data tidak ditemukan. Pastikan kode booking dan email/no. HP sesuai dengan saat pendaftaran.';
    } else {
      $pembayaran = db()->fetchAll(
        "SELECT * FROM pembayaran WHERE booking_id = ? ORDER BY created_at DESC",
        'i',
        [$booking['id']]
      );
    }
  }
}

// Hitung total terbayar
$total_terbayar = 0;
foreach ($pembayaran as $py) {
  if ($py['status'] === 'verified')
    $total_terbayar += $py['jumlah'];
}
$sisa_bayar = ($booking['total_harga'] ?? 0) - $total_terbayar;

$status_step = [
  'pending' => 1,
  'confirmed' => 2,
  'dp_paid' => 3,
  'lunas' => 4,
  'berangkat' => 5,
  'selesai' => 6,
  'cancelled' => 0,
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cek Status Booking — SAH Travel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <style>
    :root {
      --hijau-tua: #1B4D2E;
      --hijau: #1B6B3A;
      --emas: #C9A84C;
      --emas-muda: #E8C97A;
      --krem: #F9F5EE;
      --putih: #FDFAF5;
      --teks-gelap: #1A1A1A;
      --teks-abu: #6B6B6B;
      --font-display: 'Playfair Display', serif;
      --font-body: 'DM Sans', sans-serif;
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: var(--font-body);
      background: var(--krem);
      color: var(--teks-gelap);
    }

    ::-webkit-scrollbar {
      width: 6px;
    }

    ::-webkit-scrollbar-thumb {
      background: var(--hijau);
      border-radius: 3px;
    }

    /* NAVBAR */
    .navbar {
      background: rgba(27, 77, 46, .97);
      backdrop-filter: blur(10px);
      padding: .8rem 0;
      box-shadow: 0 4px 30px rgba(0, 0, 0, .2);
    }

    .navbar-brand {
      font-family: var(--font-display);
      font-size: 1.4rem;
      font-weight: 700;
      color: #fff !important;
    }

    .navbar-brand span {
      color: var(--emas);
    }

    .navbar-brand {
      display: flex;
      align-items: center;
    }

    .navbar-logo {
        height: 58px;
        width: auto;
        display: block;
    }

    @media (max-width: 991px) {
        .navbar-logo {
            height: 50px;
        }
    }

    @media (max-width: 480px) {
        .navbar-logo {
            height: 42px;
        }
    }

    .nav-link {
      color: rgba(255, 255, 255, .9) !important;
      font-weight: 500;
      font-size: .9rem;
      padding: .5rem 1rem !important;
      transition: color .3s
    }

    .nav-link:hover,
    .nav-link.active {
      color: var(--emas) !important
    }

    .btn-navbar {
      background: var(--emas);
      color: var(--hijau-tua) !important;
      border-radius: 50px;
      padding: .5rem 1.4rem !important;
      font-weight: 600;
      font-size: .85rem;
      transition: all .3s
    }

    .btn-navbar:hover {
      background: var(--emas-muda);
      transform: translateY(-1px)
    }

    /* PAGE HERO (standar — sama dengan halaman lain) */
    .page-hero {
      background: linear-gradient(135deg, #0D2B1A, #1B4D2E 50%, #1B6B3A);
      padding: 120px 0 60px;
      position: relative;
      overflow: hidden;
    }

    @media (max-width: 767px) {
      .page-hero {
        padding: 70px 0 30px;
      }
    }

    @media (max-width: 480px) {
      .page-hero {
        padding: 20px 0 20px;
      }
    }

    .page-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      opacity: .05;
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23C9A84C' fill-opacity='1'%3E%3Cpath d='M30 0L39 20.5H60L42.5 33.2L49.5 53.5L30 40.5L10.5 53.5L17.5 33.2L0 20.5H21L30 0Z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .page-hero-title {
      font-family: var(--font-display);
      font-size: clamp(1.8rem, 4vw, 2.8rem);
      font-weight: 700;
      color: #fff;
    }

    .page-hero-title span {
      color: var(--emas);
    }

    .page-hero-sub {
      color: rgba(255, 255, 255, .7);
      font-size: 1rem;
    }

    .breadcrumb-item a {
      color: var(--emas-muda);
      text-decoration: none;
    }

    .breadcrumb-item.active {
      color: rgba(255, 255, 255, .6);
    }

    .breadcrumb-item+.breadcrumb-item::before {
      color: rgba(255, 255, 255, .4);
    }

    /* SEARCH */
    .search-section {
      padding: 50px 0 30px;
    }

    .search-card {
      background: #fff;
      border-radius: 20px;
      padding: 36px;
      box-shadow: 0 8px 40px rgba(27, 77, 46, .1);
    }

    .search-title {
      font-family: var(--font-display);
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--hijau-tua);
      margin-bottom: 6px;
    }

    .search-desc {
      color: var(--teks-abu);
      font-size: .9rem;
      margin-bottom: 24px;
    }

    .search-group {
      display: flex;
      gap: 12px;
    }

    .search-input {
      flex: 1;
      border: 2px solid #e5e5e5;
      border-radius: 12px;
      padding: 12px 18px;
      font-size: .95rem;
      font-family: var(--font-body);
      transition: border-color .2s;
      text-transform: uppercase;
    }

    .search-input-kontak {
      text-transform: none;
    }

    .search-input:focus {
      outline: none;
      border-color: var(--hijau);
    }

    .btn-search {
      background: var(--hijau);
      color: #fff;
      border: none;
      border-radius: 12px;
      padding: 12px 28px;
      font-weight: 700;
      font-size: .9rem;
      font-family: var(--font-body);
      cursor: pointer;
      transition: all .3s;
      display: flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
    }

    .btn-search:hover {
      background: var(--hijau-tua);
    }

    .error-box {
      background: #fee2e2;
      color: #991b1b;
      border-radius: 12px;
      padding: 14px 18px;
      margin-top: 16px;
      font-size: .9rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* RESULT */
    .result-section {
      padding: 0 0 80px;
    }

    .result-header {
      background: linear-gradient(135deg, var(--hijau-tua), var(--hijau));
      border-radius: 20px 20px 0 0;
      padding: 24px 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
    }

    .kode-badge {
      font-family: monospace;
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--emas-muda);
      letter-spacing: 2px;
    }

    .result-body {
      background: #fff;
      border-radius: 0 0 20px 20px;
      padding: 32px;
      box-shadow: 0 8px 40px rgba(27, 77, 46, .1);
    }

    /* STEPPER */
    .stepper {
      display: flex;
      align-items: flex-start;
      padding: 20px 0;
      overflow-x: auto;
    }

    .st-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      flex: 1;
      min-width: 75px;
      position: relative;
    }

    .st-circle {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .78rem;
      font-weight: 700;
      border: 2px solid #e5e5e5;
      background: #fff;
      color: #ccc;
      z-index: 2;
      position: relative;
    }

    .st-item.done .st-circle {
      background: var(--hijau);
      border-color: var(--hijau);
      color: #fff;
    }

    .st-item.aktif .st-circle {
      background: var(--emas);
      border-color: var(--emas);
      color: var(--hijau-tua);
    }

    .st-label {
      font-size: .68rem;
      color: #aaa;
      margin-top: 5px;
      text-align: center;
      font-weight: 500;
      line-height: 1.3;
    }

    .st-item.done .st-label {
      color: var(--hijau);
    }

    .st-item.aktif .st-label {
      color: var(--emas);
      font-weight: 700;
    }

    .st-line {
      position: absolute;
      top: 16px;
      left: 50%;
      width: 100%;
      height: 2px;
      background: #e5e5e5;
      z-index: 1;
    }

    .st-item.done .st-line {
      background: var(--hijau);
    }

    /* INFO GRID */
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 14px;
      margin: 20px 0;
    }

    .info-box {
      background: var(--krem);
      border-radius: 12px;
      padding: 14px 16px;
    }

    .info-box-label {
      font-size: .72rem;
      color: var(--teks-abu);
      text-transform: uppercase;
      letter-spacing: .5px;
      margin-bottom: 4px;
    }

    .info-box-val {
      font-size: .92rem;
      font-weight: 600;
      color: var(--teks-gelap);
    }

    .info-box-val.harga {
      font-family: var(--font-display);
      color: var(--hijau);
    }

    /* PAKET BOX */
    .paket-box {
      background: var(--krem);
      border-radius: 14px;
      padding: 18px 22px;
      border-left: 4px solid var(--hijau);
      margin: 18px 0;
    }

    .paket-box-name {
      font-family: var(--font-display);
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--hijau-tua);
      margin-bottom: 10px;
    }

    .paket-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
    }

    .paket-tag {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: .83rem;
      color: var(--teks-abu);
    }

    .paket-tag i {
      color: var(--hijau);
    }

    /* PEMBAYARAN */
    .sub-title {
      font-size: .92rem;
      font-weight: 700;
      color: var(--hijau-tua);
      margin: 22px 0 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .tbl {
      width: 100%;
      border-collapse: collapse;
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid #f0f0f0;
    }

    .tbl thead th {
      background: #f8f8f8;
      padding: 10px 14px;
      text-align: left;
      font-size: .78rem;
      color: #666;
      font-weight: 600;
    }

    .tbl tbody td {
      padding: 11px 14px;
      font-size: .83rem;
      border-top: 1px solid #f5f5f5;
    }

    .badge-status {
      padding: 3px 10px;
      border-radius: 20px;
      font-size: .72rem;
      font-weight: 700;
    }

    .bs-pending {
      background: #fef3c7;
      color: #92400e;
    }

    .bs-verified {
      background: #dcfce7;
      color: #15803d;
    }

    .bs-rejected {
      background: #fee2e2;
      color: #dc2626;
    }

    /* CTA BAYAR */
    .pay-cta {
      background: linear-gradient(135deg, #fef3c7, #fff);
      border: 1px solid var(--emas);
      border-radius: 14px;
      padding: 18px 22px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 18px;
    }

    .btn-wa {
      background: #25D366;
      color: #fff;
      border: none;
      border-radius: 50px;
      padding: 9px 22px;
      font-weight: 700;
      font-size: .85rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      transition: all .3s;
    }

    .btn-wa:hover {
      background: #22c35e;
      color: #fff;
    }

    /* CATATAN */
    .catatan-box {
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      border-radius: 12px;
      padding: 14px 18px;
      margin-top: 14px;
      font-size: .85rem;
      color: #14532d;
    }

    footer {
      background: var(--hijau-tua);
      color: rgba(255, 255, 255, .5);
      text-align: center;
      padding: 22px;
      font-size: .82rem;
    }

    footer a {
      color: var(--emas);
      text-decoration: none;
    }
  </style>
</head>

<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
      <a class="navbar-brand" href="../index.php">
        <img src="<?= BASE_URL ?>/assets/img/logo-sah.png" alt="Logo SAH Umrah" class="navbar-logo me-2">SAH <span>Umrah</span>
      </a>
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <i class="bi bi-list text-white fs-4"></i>
      </button>
      <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
          <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="paket.php">Paket Umrah</a></li>
          <li class="nav-item"><a class="nav-link" href="tentang.php">Tentang Kami</a></li>
          <li class="nav-item"><a class="nav-link" href="kontak.php">Kontak</a></li>
          <li class="nav-item"><a class="nav-link active" href="cek-booking.php">Cek Booking</a></li>
          <li class="nav-item ms-2">
            <a class="nav-link btn-navbar" href="paket.php">
              <i class="bi bi-calendar-check me-1"></i>Daftar Sekarang
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- PAGE HERO -->
  <section class="page-hero">
    <div class="container">
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0" style="font-size:.83rem">
          <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
          <li class="breadcrumb-item active">Cek Status Booking</li>
        </ol>
      </nav>
      <h1 class="page-hero-title">Cek Status <span>Booking</span> Umrah</h1>
      <p class="page-hero-sub">Masukkan kode booking dan email/no. HP Anda untuk melihat status pendaftaran terkini</p>
    </div>
  </section>

  <!-- SEARCH -->
  <section class="search-section">
    <div class="container">
      <div class="search-card" data-aos="fade-up">
        <div class="search-title">
          <i class="bi bi-ticket-detailed-fill me-2" style="color:var(--emas)"></i>Masukkan Kode Booking
        </div>
        <div class="search-desc">
          Kode booking Anda didapat setelah pendaftaran selesai. Format: <strong>SAH-2026-0001</strong>.
          Untuk keamanan data Anda, mohon isi juga email atau no. HP yang digunakan saat mendaftar.
        </div>
        <form method="GET" action="">
          <div class="search-group" style="flex-direction:column;align-items:stretch;gap:12px">
            <input type="text" name="kode" class="search-input" placeholder="Kode Booking, contoh: SAH-2026-0001"
              value="<?= htmlspecialchars($kode_input) ?>" required>
            <input type="text" name="kontak" class="search-input search-input-kontak" placeholder="Email atau No. HP saat mendaftar"
              value="<?= htmlspecialchars($kontak_input) ?>" required>
            <button type="submit" class="btn-search">
              <i class="bi bi-search"></i> Cek Status
            </button>
          </div>
        </form>
        <?php if ($error): ?>
          <div class="error-box mt-3">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- RESULT -->
  <?php if ($booking): ?>
    <section class="result-section">
      <div class="container" data-aos="fade-up">

        <div class="result-header">
          <div>
            <div style="color:rgba(255,255,255,.6);font-size:.75rem;margin-bottom:4px">KODE BOOKING</div>
            <div class="kode-badge"><?= htmlspecialchars($booking['kode_booking']) ?></div>
          </div>
          <div style="text-align:right">
            <?= statusBadge($booking['status']) ?>
            <div style="color:rgba(255,255,255,.6);font-size:.75rem;margin-top:5px">
              Didaftarkan: <?= tglIndo($booking['tanggal_booking']) ?>
            </div>
          </div>
        </div>

        <div class="result-body">

          <!-- STEPPER -->
          <?php
          $steps = ['Menunggu', 'Dikonfirmasi', 'DP Dibayar', 'Lunas', 'Berangkat', 'Selesai'];
          $cur = $status_step[$booking['status']] ?? 1;
          $isBatal = $booking['status'] === 'cancelled';
          ?>
          <?php if (!$isBatal): ?>
            <div class="stepper">
              <?php foreach ($steps as $i => $label):
                $no = $i + 1;
                $cls = $no < $cur ? 'done' : ($no === $cur ? 'aktif' : '');
                ?>
                <div class="st-item <?= $cls ?>">
                  <?php if ($i < count($steps) - 1): ?>
                    <div class="st-line"></div><?php endif; ?>
                  <div class="st-circle">
                    <?= $no < $cur ? '<i class="bi bi-check-lg"></i>' : $no ?>
                  </div>
                  <div class="st-label"><?= $label ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div
              style="background:#fee2e2;color:#991b1b;border-radius:12px;padding:14px 18px;margin:16px 0;display:flex;align-items:center;gap:10px">
              <i class="bi bi-x-circle-fill fs-5"></i>
              <strong>Pendaftaran ini telah dibatalkan.</strong>
            </div>
          <?php endif; ?>

          <!-- INFO JAMAAH -->
          <div class="info-grid">
            <div class="info-box">
              <div class="info-box-label">Nama Pemesan</div>
              <div class="info-box-val"><?= htmlspecialchars($booking['nama_pemesan'] ?? '-') ?></div>
            </div>
            <div class="info-box">
              <div class="info-box-label">Telepon</div>
              <div class="info-box-val"><?= htmlspecialchars($booking['telepon'] ?? '-') ?></div>
            </div>
            <div class="info-box">
              <div class="info-box-label">Email</div>
              <div class="info-box-val" style="font-size:.82rem"><?= htmlspecialchars($booking['email'] ?? '-') ?></div>
            </div>
            <div class="info-box">
              <div class="info-box-label">Jumlah Jamaah</div>
              <div class="info-box-val"><?= $booking['jumlah_jamaah'] ?> Orang</div>
            </div>
            <div class="info-box">
              <div class="info-box-label">Total Tagihan</div>
              <div class="info-box-val harga"><?= rupiah($booking['total_harga'] ?? 0) ?></div>
            </div>
            <div class="info-box">
              <div class="info-box-label">Terbayar</div>
              <div class="info-box-val harga"><?= rupiah($total_terbayar) ?></div>
            </div>
            <div class="info-box">
              <div class="info-box-label">Sisa Tagihan</div>
              <div class="info-box-val"
                style="color:<?= $sisa_bayar > 0 ? '#dc2626' : '#15803d' ?>;font-family:var(--font-display)">
                <?= $sisa_bayar > 0 ? rupiah($sisa_bayar) : '✓ Lunas' ?>
              </div>
            </div>
            <div class="info-box">
              <div class="info-box-label">DP Minimum</div>
              <div class="info-box-val harga"><?= rupiah($booking['dp_amount'] ?? 0) ?></div>
            </div>
          </div>

          <!-- PAKET -->
          <?php if (!empty($booking['nama_paket'])): ?>
            <div class="paket-box">
              <div class="paket-box-name">
                <i class="bi bi-briefcase-fill me-2" style="color:var(--emas)"></i>
                <?= htmlspecialchars($booking['nama_paket']) ?>
              </div>
              <div class="paket-tags">
                <span class="paket-tag"><i class="bi bi-calendar3"></i><?= $booking['durasi'] ?> Hari</span>
                <span class="paket-tag"><i
                    class="bi bi-airplane"></i><?= htmlspecialchars($booking['maskapai'] ?? '-') ?></span>
                <span class="paket-tag"><i
                    class="bi bi-building"></i><?= htmlspecialchars($booking['hotel_mekkah'] ?? '-') ?></span>
                <span class="paket-tag"><i
                    class="bi bi-geo-alt"></i><?= htmlspecialchars($booking['hotel_madinah'] ?? '-') ?></span>
                <?php if (!empty($booking['tanggal_berangkat'])): ?>
                  <span class="paket-tag"><i class="bi bi-calendar-event"></i>Berangkat:
                    <?= tglIndo($booking['tanggal_berangkat']) ?></span>
                  <span class="paket-tag"><i class="bi bi-calendar-check"></i>Kembali:
                    <?= tglIndo($booking['tanggal_pulang']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- CATATAN ADMIN -->
          <?php if (!empty($booking['catatan_admin'])): ?>
            <div class="catatan-box">
              <strong><i class="bi bi-info-circle-fill me-2"></i>Pesan dari Tim SAH Travel:</strong><br>
              <?= nl2br(htmlspecialchars($booking['catatan_admin'])) ?>
            </div>
          <?php endif; ?>

          <!-- RIWAYAT PEMBAYARAN -->
          <div class="sub-title">
            <i class="bi bi-credit-card-fill" style="color:var(--emas)"></i> Riwayat Pembayaran
          </div>
          <?php if (!empty($pembayaran)): ?>
            <div style="overflow-x:auto">
              <table class="tbl">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Metode</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pembayaran as $py): ?>
                    <tr>
                      <td><?= tglIndo($py['created_at']) ?></td>
                      <td><?= ucfirst($py['jenis']) ?></td>
                      <td><strong><?= rupiah($py['jumlah']) ?></strong></td>
                      <td><?= htmlspecialchars($py['metode'] ?? '-') ?></td>
                      <td>
                        <span class="badge-status bs-<?= $py['status'] ?>">
                          <?= ['pending' => 'Menunggu', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'][$py['status']] ?? $py['status'] ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div
              style="background:var(--krem);border-radius:12px;padding:20px;text-align:center;color:var(--teks-abu);font-size:.85rem">
              <i class="bi bi-credit-card d-block mb-2" style="font-size:2rem;opacity:.3"></i>
              Belum ada riwayat pembayaran
            </div>
          <?php endif; ?>

          <!-- CTA -->
          <?php if ($sisa_bayar > 0 && !$isBatal): ?>
            <div class="pay-cta">
              <div>
                <div style="font-weight:700;color:var(--hijau-tua);margin-bottom:4px">
                  <i class="bi bi-exclamation-circle-fill me-2" style="color:var(--emas)"></i>
                  Sisa tagihan: <?= rupiah($sisa_bayar) ?>
                </div>
                <div style="font-size:.8rem;color:var(--teks-abu)">
                  Hubungi tim kami untuk info rekening pembayaran
                </div>
              </div>
              <a href="<?= waLink('Assalamu\'alaikum, saya ingin konfirmasi pembayaran. Kode booking: ' . $booking['kode_booking']) ?>"
                target="_blank" class="btn-wa">
                <i class="bi bi-whatsapp"></i> Konfirmasi via WA
              </a>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </section>
  <?php endif; ?>

  <footer>
    © <?= date('Y') ?>
    <a href="../index.php"><?= htmlspecialchars($settings['nama_perusahaan'] ?? 'PT. Sumatera Andalan Haramain') ?></a>.
    All rights reserved.
  </footer>

  <a href="<?= waLink() ?>" target="_blank"
    style="position:fixed;bottom:28px;right:28px;z-index:999;width:52px;height:52px;border-radius:50%;background:#25D366;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;text-decoration:none;box-shadow:0 6px 20px rgba(37,211,102,.5)">
    <i class="bi bi-whatsapp"></i>
  </a>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script>
    AOS.init({ once: true, offset: 60, duration: 300 });
    document.querySelector('.search-input')?.addEventListener('input', function () {
      this.value = this.value.toUpperCase();
    });
  </script>
</body>

</html>