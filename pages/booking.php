<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$paketId = (int) ($_GET['paket'] ?? 0);
$jadwalId = (int) ($_GET['jadwal'] ?? 0);
$qty = max(1, (int) ($_GET['qty'] ?? 1));

if (!$paketId) {
  setFlash('error', 'Pilih paket terlebih dahulu.');
  redirect(BASE_URL . '/pages/paket.php');
}

$paket = db()->fetchOne("SELECT * FROM paket_umrah WHERE id = ? AND status = 'aktif'", 'i', [$paketId]);
if (!$paket) {
  setFlash('error', 'Paket tidak ditemukan.');
  redirect(BASE_URL . '/pages/paket.php');
}
if ($paket['sisa_kuota'] < $qty) {
  setFlash('error', 'Kuota tidak mencukupi.');
  redirect(BASE_URL . '/pages/paket-detail.php?slug=' . $paket['slug']);
}

$jadwal = null;
if ($jadwalId)
  $jadwal = db()->fetchOne("SELECT * FROM jadwal WHERE id = ? AND paket_id = ? AND status = 'aktif'", 'ii', [$jadwalId, $paketId]);

$jadwals = db()->fetchAll("SELECT * FROM jadwal WHERE paket_id = ? AND status = 'aktif' AND tanggal_berangkat >= CURDATE() ORDER BY tanggal_berangkat ASC", 'i', [$paketId]);
$settings = [];
foreach (db()->fetchAll("SELECT nama_key, nilai FROM pengaturan") as $p)
  $settings[$p['nama_key']] = $p['nilai'];

$harga = $jadwal && $jadwal['harga_khusus'] ? $jadwal['harga_khusus'] : $paket['harga'];
$total = $harga * $qty;
$dp = $total * (($settings['dp_minimum_persen'] ?? 30) / 100);

$errors = [];
$old = [];

// PROCESS BOOKING
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  checkCsrf();
  $old = $_POST;

  // Ambil data pemesan
  $namaPemesan = trim($_POST['nama_pemesan'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telepon = trim($_POST['telepon'] ?? '');
  $alamat = trim($_POST['alamat'] ?? '');
  $jId = (int) ($_POST['jadwal_id'] ?? 0);
  $jQty = max(1, (int) ($_POST['jumlah_jamaah'] ?? 1));
  $catatan = trim($_POST['catatan'] ?? '');

  // Validasi
  if (!$namaPemesan)
    $errors[] = 'Nama pemesan wajib diisi.';
  if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = 'Email tidak valid.';
  if (!$telepon)
    $errors[] = 'Nomor telepon wajib diisi.';
  if (!$alamat)
    $errors[] = 'Alamat wajib diisi.';

  $selJadwal = $jId ? db()->fetchOne("SELECT * FROM jadwal WHERE id = ? AND paket_id = ? AND status = 'aktif'", 'ii', [$jId, $paketId]) : null;
  $finalHarga = $selJadwal && $selJadwal['harga_khusus'] ? $selJadwal['harga_khusus'] : $paket['harga'];
  $finalTotal = $finalHarga * $jQty;
  $finalDp = $finalTotal * (($settings['dp_minimum_persen'] ?? 30) / 100);

  if ($paket['sisa_kuota'] < $jQty)
    $errors[] = 'Kuota tidak mencukupi untuk ' . $jQty . ' jamaah.';

  if (empty($errors)) {
    // Kumpulkan data jamaah
    $dataJamaah = [];
    // Jamaah pertama = pemesan sendiri
    $dataJamaah[] = [
      'nama' => htmlspecialchars($namaPemesan),
      'ktp' => htmlspecialchars(trim($_POST['ktp_pemesan'] ?? '')),
      'paspor' => htmlspecialchars(trim($_POST['paspor_pemesan'] ?? '')),
    ];
    // Jamaah tambahan
    if (isset($_POST['jamaah_nama'])) {
      foreach ($_POST['jamaah_nama'] as $idx => $jNama) {
        if (trim($jNama)) {
          $dataJamaah[] = [
            'nama' => htmlspecialchars(trim($jNama)),
            'ktp' => htmlspecialchars(trim($_POST['jamaah_ktp'][$idx] ?? '')),
            'paspor' => htmlspecialchars(trim($_POST['jamaah_paspor'][$idx] ?? '')),
          ];
        }
      }
    }

    // Generate kode booking
    $tahun = date('Y');
    $total_exists = (int) db()->fetchOne("SELECT COUNT(*) as total FROM booking WHERE YEAR(tanggal_booking) = ?", 'i', [$tahun])['total'];
    $kodeBooking = 'SAH-' . $tahun . '-' . str_pad($total_exists + 1, 4, '0', STR_PAD_LEFT);

    $bookingId = db()->insert(
      "INSERT INTO booking (kode_booking, nama_pemesan, email, telepon, alamat, paket_id, jadwal_id, jumlah_jamaah, harga_per_orang, total_harga, dp_amount, catatan, data_jamaah, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
      'sssssiiidddss',
      [$kodeBooking, $namaPemesan, $email, $telepon, $alamat, $paketId, $jId ?: null, $jQty, $finalHarga, $finalTotal, $finalDp, $catatan, json_encode($dataJamaah)]
    );

    if ($bookingId) {
      // Kurangi sisa kuota
      db()->execute("UPDATE paket_umrah SET sisa_kuota = sisa_kuota - ? WHERE id = ?", 'ii', [$jQty, $paketId]);
      if ($jId)
        db()->execute("UPDATE jadwal SET terisi = terisi + ? WHERE id = ?", 'ii', [$jQty, $jId]);

      // Notifikasi admin
      db()->insert(
        "INSERT INTO notifikasi (judul, pesan, tipe, link) VALUES (?, ?, 'booking', ?)",
        'sss',
        [
          'Booking Baru: ' . $kodeBooking,
          $namaPemesan . ' mendaftar paket ' . $paket['nama_paket'] . ' (' . $jQty . ' jamaah)',
          BASE_URL . '/admin/pages/booking-detail.php?id=' . $bookingId
        ]
      );

      // Redirect ke halaman sukses
      redirect(BASE_URL . '/pages/booking_sukses.php?kode=' . $kodeBooking);
    } else {
      $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
    }
  }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Form Pendaftaran – <?= htmlspecialchars($paket['nama_paket']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <style>
    :root {
      --hijau-tua: #1B4D2E;
      --hijau: #1B6B3A;
      --emas: #C9A84C;
      --krem: #F9F5EE;
      --font-display: 'Playfair Display', serif;
      --font-body: 'DM Sans', sans-serif
    }

    * {
      box-sizing: border-box
    }

    body {
      font-family: var(--font-body);
      background: var(--krem)
    }

    .navbar {
      background: rgba(27, 77, 46, .97);
      padding: .8rem 0
    }

    .navbar-brand {
      font-family: var(--font-display);
      font-size: 1.3rem;
      font-weight: 700;
      color: #fff !important
    }

    .navbar-brand span {
      color: var(--emas)
    }

    .nav-link {
      color: rgba(255, 255, 255, .9) !important;
      font-size: .88rem
    }

    .nav-link:hover {
      color: var(--emas) !important
    }

    .page-hero {
      background: linear-gradient(135deg, #0D2B1A, #1B6B3A);
      padding: 90px 0 45px
    }

    .page-hero h1 {
      font-family: var(--font-display);
      font-size: clamp(1.6rem, 3vw, 2.2rem);
      font-weight: 700;
      color: #fff
    }

    .page-hero h1 span {
      color: var(--emas)
    }

    .breadcrumb-item a {
      color: var(--emas);
      text-decoration: none
    }

    .breadcrumb-item.active {
      color: rgba(255, 255, 255, .6)
    }

    .breadcrumb-item+.breadcrumb-item::before {
      color: rgba(255, 255, 255, .4)
    }

    .card-form {
      background: #fff;
      border-radius: 18px;
      padding: 2rem;
      box-shadow: 0 4px 20px rgba(27, 77, 46, .08);
      border: 1px solid rgba(27, 107, 58, .08);
      margin-bottom: 1.5rem
    }

    .section-head {
      font-family: var(--font-display);
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--hijau-tua);
      margin-bottom: 1.2rem;
      padding-bottom: .7rem;
      border-bottom: 2px solid var(--krem)
    }

    .section-head i {
      color: var(--emas);
      margin-right: .5rem
    }

    .form-label {
      font-size: .83rem;
      font-weight: 600;
      color: #444;
      margin-bottom: .3rem
    }

    .form-control,
    .form-select {
      border: 1.5px solid #e5e7eb;
      border-radius: 10px;
      padding: .55rem .9rem;
      font-size: .88rem;
      transition: border .3s
    }

    .form-control:focus,
    .form-select:focus {
      border-color: var(--hijau);
      box-shadow: 0 0 0 3px rgba(27, 107, 58, .1);
      outline: none
    }

    .form-control.is-invalid {
      border-color: #dc3545
    }

    .paket-summary {
      background: linear-gradient(135deg, var(--hijau-tua), var(--hijau));
      color: #fff;
      border-radius: 18px;
      padding: 1.8rem;
      position: sticky;
      top: 90px
    }

    .ps-name {
      font-family: var(--font-display);
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: .8rem
    }

    .ps-row {
      display: flex;
      justify-content: space-between;
      font-size: .85rem;
      padding: .4rem 0;
      border-bottom: 1px solid rgba(255, 255, 255, .1)
    }

    .ps-row:last-child {
      border-bottom: none
    }

    .ps-label {
      color: rgba(255, 255, 255, .7)
    }

    .ps-val {
      font-weight: 600
    }

    .total-box {
      background: rgba(255, 255, 255, .12);
      border-radius: 12px;
      padding: 1rem;
      margin: 1rem 0;
      text-align: center
    }

    .total-num {
      font-family: var(--font-display);
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--emas)
    }

    .dp-box {
      background: rgba(201, 168, 76, .2);
      border-radius: 10px;
      padding: .8rem 1rem;
      margin-bottom: 1rem;
      font-size: .83rem
    }

    .btn-submit {
      background: linear-gradient(135deg, var(--hijau-tua), var(--hijau));
      color: #fff;
      border: none;
      border-radius: 50px;
      padding: .85rem 2rem;
      font-size: .95rem;
      font-weight: 700;
      width: 100%;
      transition: all .3s
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(27, 107, 58, .35)
    }

    .jadwal-opt {
      border: 1.5px solid #e5e7eb;
      border-radius: 10px;
      padding: .8rem 1rem;
      cursor: pointer;
      transition: all .3s;
      margin-bottom: .5rem
    }

    .jadwal-opt:hover {
      border-color: var(--hijau)
    }

    .jadwal-opt.selected {
      border-color: var(--hijau);
      background: rgba(27, 107, 58, .05)
    }

    .jamaah-card {
      background: var(--krem);
      border-radius: 12px;
      padding: 1.2rem;
      margin-bottom: .8rem;
      border: 1px solid #e5e7eb
    }

    .jamaah-card-head {
      font-weight: 700;
      font-size: .88rem;
      color: var(--hijau-tua);
      margin-bottom: .8rem;
      display: flex;
      align-items: center;
      gap: .5rem
    }

    .jamaah-card-head i {
      color: var(--emas)
    }

    .alert-error-box {
      background: #FEF2F2;
      border: 1px solid #FECACA;
      color: #DC2626;
      border-radius: 12px;
      padding: 1rem 1.2rem;
      font-size: .85rem;
      margin-bottom: 1.5rem
    }

    .alert-error-box ul {
      margin: .5rem 0 0;
      padding-left: 1.2rem
    }

    .wa-float {
      position: fixed;
      bottom: 30px;
      right: 30px;
      z-index: 999;
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: #25D366;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      box-shadow: 0 8px 25px rgba(37, 211, 102, .45);
      text-decoration: none
    }

    footer {
      background: var(--hijau-tua);
      padding: 30px 0 20px;
      color: rgba(255, 255, 255, .6);
      text-align: center;
      font-size: .82rem
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
      <a class="navbar-brand" href="../index.php"><i class="bi bi-moon-stars-fill me-2"
          style="color:var(--emas)"></i>SAH <span>Travel</span></a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <a href="paket.php" class="nav-link"><i class="bi bi-grid me-1"></i>Paket Umrah</a>
        <a href="../index.php#kontak" class="nav-link"><i class="bi bi-telephone me-1"></i>Kontak</a>
      </div>
    </div>
  </nav>

  <section class="page-hero">
    <div class="container">
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0" style="font-size:.82rem">
          <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
          <li class="breadcrumb-item"><a href="paket.php">Paket</a></li>
          <li class="breadcrumb-item"><a
              href="paket-detail.php?slug=<?= $paket['slug'] ?>"><?= htmlspecialchars($paket['nama_paket']) ?></a></li>
          <li class="breadcrumb-item active">Pendaftaran</li>
        </ol>
      </nav>
      <h1>Form <span>Pendaftaran Umrah</span></h1>
      <p style="color:rgba(255,255,255,.7);font-size:.9rem;margin-top:.5rem">Isi formulir dengan data yang benar dan
        lengkap</p>
    </div>
  </section>

  <section style="padding:50px 0 80px">
    <div class="container">

      <?php if (!empty($errors)): ?>
        <div class="alert-error-box">
          <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Mohon perbaiki kesalahan berikut:</strong>
          <ul>
            <?php foreach ($errors as $err): ?>
              <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="" id="formBooking">
        <?= csrfField() ?>
        <input type="hidden" name="paket_id" value="<?= $paket['id'] ?>">

        <div class="row g-4">
          <!-- KIRI -->
          <div class="col-lg-8">

            <!-- DATA PEMESAN -->
            <div class="card-form">
              <div class="section-head"><i class="bi bi-person-fill"></i>Data Pemesan (Jamaah ke-1)</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                  <input type="text" name="nama_pemesan"
                    class="form-control <?= isset($errors) && !trim($_POST['nama_pemesan'] ?? '') ? 'is-invalid' : '' ?>"
                    placeholder="Nama sesuai KTP/Paspor" value="<?= htmlspecialchars($old['nama_pemesan'] ?? '') ?>"
                    required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email <span class="text-danger">*</span></label>
                  <input type="email" name="email" class="form-control" placeholder="email@example.com"
                    value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">No. Telepon / WhatsApp <span class="text-danger">*</span></label>
                  <input type="text" name="telepon" class="form-control" placeholder="08xxxxxxxxxx"
                    value="<?= htmlspecialchars($old['telepon'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">No. KTP</label>
                  <input type="text" name="ktp_pemesan" class="form-control" placeholder="Nomor KTP (16 digit)"
                    value="<?= htmlspecialchars($old['ktp_pemesan'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">No. Paspor</label>
                  <input type="text" name="paspor_pemesan" class="form-control" placeholder="Nomor paspor (jika ada)"
                    value="<?= htmlspecialchars($old['paspor_pemesan'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                  <textarea name="alamat" class="form-control" rows="2"
                    placeholder="Alamat lengkap sesuai KTP"><?= htmlspecialchars($old['alamat'] ?? '') ?></textarea>
                </div>
              </div>
            </div>

            <!-- PILIH JADWAL -->
            <div class="card-form">
              <div class="section-head"><i class="bi bi-calendar-event-fill"></i>Pilih Jadwal Keberangkatan</div>
              <?php if (!empty($jadwals)): ?>
                <?php foreach ($jadwals as $jd):
                  $sisa = $jd['kuota'] - $jd['terisi'];
                  $selected = ($jadwal && $jadwal['id'] == $jd['id']) || (!$jadwal && $jd === reset($jadwals));
                  $hargaJd = $jd['harga_khusus'] ?? $paket['harga'];
                  ?>
                  <label class="jadwal-opt <?= $selected ? 'selected' : '' ?> <?= $sisa == 0 ? 'opacity-50' : '' ?>"
                    onclick="<?= $sisa > 0 ? "pilihJadwal(this,{$jd['id']},{$hargaJd})" : 'return false' ?>">
                    <input type="radio" name="jadwal_id" value="<?= $jd['id'] ?>" style="display:none" <?= $selected ? 'checked' : '' ?>     <?= $sisa == 0 ? 'disabled' : '' ?>>
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <div style="font-weight:700;color:var(--hijau-tua)">
                          <i class="bi bi-calendar-event me-2" style="color:var(--emas)"></i>
                          <?= tglIndo($jd['tanggal_berangkat']) ?>
                        </div>
                        <div style="font-size:.8rem;color:#888;margin-top:.2rem">
                          Kembali: <?= tglIndo($jd['tanggal_pulang']) ?> | <?= $paket['durasi'] ?> Hari
                        </div>
                        <?php if ($jd['harga_khusus']): ?>
                          <div style="font-size:.78rem;color:var(--emas);font-weight:600;margin-top:.2rem">
                            Harga khusus: Rp <?= number_format($jd['harga_khusus'], 0, ',', '.') ?>/orang
                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="text-end">
                        <div
                          style="font-size:.8rem;font-weight:600;color:<?= $sisa < 5 ? '#dc3545' : ($sisa < 15 ? '#f59e0b' : 'var(--hijau)') ?>">
                          <?= $sisa ?> kursi tersisa
                        </div>
                        <?php if ($sisa === 0): ?>
                          <span class="badge bg-danger">Penuh</span>
                        <?php else: ?>
                          <i class="bi bi-check-circle-fill" style="color:var(--hijau);font-size:1.2rem"></i>
                        <?php endif; ?>
                      </div>
                    </div>
                  </label>
                <?php endforeach; ?>
                <input type="hidden" name="jadwal_id" id="hiddenJadwalId"
                  value="<?= $jadwal['id'] ?? ($jadwals[0]['id'] ?? '') ?>">
              <?php else: ?>
                <div class="text-center py-3">
                  <i class="bi bi-calendar-x d-block" style="font-size:2rem;color:#ccc;margin-bottom:.5rem"></i>
                  <p style="color:#888;font-size:.88rem">Jadwal belum tersedia. Hubungi kami via WhatsApp.</p>
                </div>
                <input type="hidden" name="jadwal_id" value="">
              <?php endif; ?>
            </div>

            <!-- JUMLAH JAMAAH -->
            <div class="card-form">
              <div class="section-head"><i class="bi bi-people-fill"></i>Jumlah Jamaah</div>
              <div class="row g-3 align-items-center">
                <div class="col-md-4">
                  <label class="form-label">Jumlah Orang <span class="text-danger">*</span></label>
                  <div class="d-flex align-items-center gap-2">
                    <button type="button" onclick="changeQty(-1)" class="btn btn-outline-secondary rounded-circle"
                      style="width:36px;height:36px;padding:0">−</button>
                    <input type="number" name="jumlah_jamaah" id="qty" class="form-control text-center"
                      value="<?= (int) ($old['jumlah_jamaah'] ?? $qty) ?>" min="1" max="<?= $paket['sisa_kuota'] ?>"
                      style="width:65px" onchange="updateTotal()">
                    <button type="button" onclick="changeQty(1)" class="btn btn-outline-secondary rounded-circle"
                      style="width:36px;height:36px;padding:0">+</button>
                  </div>
                </div>
                <div class="col-md-8">
                  <div style="background:var(--krem);border-radius:10px;padding:.8rem 1rem;font-size:.83rem;color:#555">
                    <i class="bi bi-info-circle me-2" style="color:var(--hijau)"></i>
                    Data jamaah ke-2 dan seterusnya dapat diisi di bawah
                  </div>
                </div>
              </div>

              <!-- Jamaah Tambahan -->
              <div id="jamaah-extra-wrap" style="margin-top:1.2rem;display:none">
                <div style="font-weight:700;font-size:.9rem;color:var(--hijau-tua);margin-bottom:.8rem">
                  <i class="bi bi-people me-2" style="color:var(--emas)"></i>Data Jamaah Tambahan
                </div>
                <div id="jamaah-list"></div>
              </div>
            </div>

            <!-- CATATAN -->
            <div class="card-form">
              <div class="section-head"><i class="bi bi-chat-text-fill"></i>Catatan Tambahan</div>
              <textarea name="catatan" class="form-control" rows="3"
                placeholder="Tuliskan kebutuhan khusus, pertanyaan, atau catatan untuk tim kami..."
                style="resize:none"><?= htmlspecialchars($old['catatan'] ?? '') ?></textarea>
            </div>

            <!-- SYARAT -->
            <div class="card-form">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="syarat" required>
                <label class="form-check-label" for="syarat" style="font-size:.85rem">
                  Saya menyatakan bahwa data yang saya isi adalah benar dan saya menyetujui
                  <a href="syarat-ketentuan.php" target="_blank" style="color:var(--hijau)">Syarat & Ketentuan</a>
                  pemesanan SAH Travel
                </label>
              </div>
            </div>

          </div>

          <!-- KANAN: SUMMARY -->
          <div class="col-lg-4">
            <div class="paket-summary">
              <div
                style="font-size:.75rem;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:1px;margin-bottom:.3rem">
                Ringkasan Paket</div>
              <div class="ps-name"><?= htmlspecialchars($paket['nama_paket']) ?></div>
              <div class="ps-row"><span class="ps-label">Durasi</span><span class="ps-val"><?= $paket['durasi'] ?>
                  Hari</span></div>
              <div class="ps-row"><span class="ps-label">Maskapai</span><span
                  class="ps-val"><?= htmlspecialchars($paket['maskapai']) ?></span></div>
              <div class="ps-row"><span class="ps-label">Hotel Mekkah</span><span class="ps-val"
                  style="font-size:.8rem"><?= htmlspecialchars($paket['hotel_mekkah']) ?></span></div>
              <div class="ps-row"><span class="ps-label">Hotel Madinah</span><span class="ps-val"
                  style="font-size:.8rem"><?= htmlspecialchars($paket['hotel_madinah']) ?></span></div>
              <div class="ps-row"><span class="ps-label">Harga/Orang</span><span class="ps-val" id="harga-per">Rp
                  <?= number_format($harga, 0, ',', '.') ?></span></div>
              <div class="ps-row"><span class="ps-label">Jumlah Jamaah</span><span class="ps-val"
                  id="qty-display"><?= $qty ?> Orang</span></div>
              <div class="total-box">
                <div style="font-size:.75rem;color:rgba(255,255,255,.7);margin-bottom:.3rem">Total Pembayaran</div>
                <div class="total-num" id="total-display">Rp <?= number_format($total, 0, ',', '.') ?></div>
              </div>
              <div class="dp-box">
                <div style="color:rgba(255,255,255,.8);margin-bottom:.2rem">
                  <i class="bi bi-info-circle me-1"></i>DP Minimum (<?= $settings['dp_minimum_persen'] ?? 30 ?>%)
                </div>
                <div style="font-size:1rem;font-weight:700;color:var(--emas)" id="dp-display">
                  Rp <?= number_format($dp, 0, ',', '.') ?>
                </div>
              </div>
              <button type="submit" class="btn-submit">
                <i class="bi bi-calendar-check-fill me-2"></i>Daftar Sekarang
              </button>
              <a href="<?= waLink('Halo, saya ingin konsultasi sebelum mendaftar paket: ' . $paket['nama_paket']) ?>"
                target="_blank"
                style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:.8rem;color:rgba(255,255,255,.75);text-decoration:none;font-size:.83rem">
                <i class="bi bi-whatsapp"></i>Ada pertanyaan? Hubungi kami
              </a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </section>

  <footer>© <?= date('Y') ?> <?= htmlspecialchars($settings['nama_perusahaan'] ?? 'PT. Sumatera Andalan Haramain') ?>.
    All rights reserved.</footer>
  <a href="<?= waLink() ?>" class="wa-float" target="_blank"><i class="bi bi-whatsapp"></i></a>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
  <script>
    let currentHarga = <?= $harga ?>;
    const dpPersen = <?= $settings['dp_minimum_persen'] ?? 30 ?>;
    const maxQty = <?= $paket['sisa_kuota'] ?>;

    function fmt(n) {
      return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function changeQty(d) {
      const i = document.getElementById('qty');
      let v = parseInt(i.value) + d;
      if (v < 1) v = 1;
      if (v > maxQty) v = maxQty;
      i.value = v;
      updateTotal();
    }

    function updateTotal() {
      const qty = parseInt(document.getElementById('qty').value) || 1;
      const total = currentHarga * qty;
      document.getElementById('qty-display').textContent = qty + ' Orang';
      document.getElementById('total-display').textContent = 'Rp ' + fmt(total);
      document.getElementById('dp-display').textContent = 'Rp ' + fmt(total * dpPersen / 100);
      renderJamaahTambahan(qty);
    }

    function renderJamaahTambahan(qty) {
      const wrap = document.getElementById('jamaah-extra-wrap');
      const list = document.getElementById('jamaah-list');
      if (qty > 1) {
        wrap.style.display = 'block';
        list.innerHTML = '';
        for (let i = 1; i < qty; i++) {
          list.innerHTML += `
                <div class="jamaah-card">
                    <div class="jamaah-card-head">
                        <i class="bi bi-person-circle"></i> Jamaah ke-${i + 1}
                    </div>
                    <div class="row g-2">
                        <div class="col-12">
                            <input type="text" name="jamaah_nama[]" class="form-control"
                                placeholder="Nama lengkap jamaah ${i + 1}" style="font-size:.83rem">
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="jamaah_ktp[]" class="form-control"
                                placeholder="No. KTP (opsional)" style="font-size:.83rem">
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="jamaah_paspor[]" class="form-control"
                                placeholder="No. Paspor (opsional)" style="font-size:.83rem">
                        </div>
                    </div>
                </div>`;
        }
      } else {
        wrap.style.display = 'none';
      }
    }

    function pilihJadwal(el, id, harga) {
      document.querySelectorAll('.jadwal-opt').forEach(e => e.classList.remove('selected'));
      el.classList.add('selected');
      el.querySelector('input[type=radio]').checked = true;
      document.getElementById('hiddenJadwalId').value = id;
      currentHarga = harga;
      document.getElementById('harga-per').textContent = 'Rp ' + fmt(harga);
      updateTotal();
    }

    document.getElementById('formBooking').addEventListener('submit', function (e) {
      e.preventDefault();
      const qty = document.getElementById('qty').value;
      const total = document.getElementById('total-display').textContent;
      Swal.fire({
        title: 'Konfirmasi Pendaftaran',
        html: `Anda akan mendaftar <b><?= htmlspecialchars($paket['nama_paket']) ?></b><br>
                   Jumlah: <b>${qty} Jamaah</b><br>
                   Total: <b>${total}</b>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1B4D2E',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Ya, Daftar!',
        cancelButtonText: 'Batal'
      }).then(r => { if (r.isConfirmed) this.submit(); });
    });

    // Init
    updateTotal();
  </script>
</body>

</html>