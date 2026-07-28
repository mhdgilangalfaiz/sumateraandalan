<?php
// user/dashboard.php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$user = currentUser();
if (!$user) {
    logout();
    redirect(APP_URL . '/pages/login.php');
}

// CATATAN: tabel `booking` belum punya kolom user_id (booking dibuat sebagai
// guest checkout). Untuk sementara, booking dicocokkan lewat email akun.
// Idealnya tabel booking ditambahkan kolom user_id agar relasinya pasti.
$bookings = db()->fetchAll(
    "SELECT b.*, pu.nama_paket, pu.durasi, pu.maskapai, j.tanggal_berangkat
     FROM booking b
     LEFT JOIN paket_umrah pu ON b.paket_id = pu.id
     LEFT JOIN jadwal j ON b.jadwal_id = j.id
     WHERE b.email = ?
     ORDER BY b.created_at DESC",
    's',
    [$user['email']]
);

$totalBooking = count($bookings);
$totalLunas = count(array_filter($bookings, fn($b) => $b['status'] === 'lunas'));
$totalProses = count(array_filter($bookings, fn($b) => in_array($b['status'], ['pending', 'confirmed', 'dp_paid'])));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Jamaah – SAH Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --hijau-tua: #1B4D2E;
            --hijau: #1B6B3A;
            --emas: #C9A84C;
            --emas-muda: #E8C97A;
            --krem: #F9F5EE;
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
        }

        .navbar {
            background: rgba(27, 77, 46, .97);
            padding: .8rem 0;
        }

        .navbar-brand {
            font-family: var(--font-display);
            font-size: 1.3rem;
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
            font-size: .88rem;
        }

        .page-hero {
            background: linear-gradient(135deg, #0D2B1A, #1B6B3A);
            padding: 60px 0 80px;
            color: #fff;
        }

        .page-hero h1 {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.8rem;
        }

        .content-wrap {
            margin-top: -50px;
        }

        .card-box {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 30px rgba(27, 77, 46, .08);
            margin-bottom: 1.5rem;
        }

        .stat-num {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--hijau-tua);
        }

        .stat-label {
            font-size: .78rem;
            color: var(--teks-abu);
        }

        .booking-item {
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: .8rem;
        }

        .booking-item .kode {
            font-weight: 700;
            color: var(--hijau-tua);
            font-size: .9rem;
        }

        .empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--teks-abu);
        }

        .btn-hijau {
            background: var(--hijau);
            color: #fff;
            border-radius: 50px;
            font-weight: 600;
            font-size: .85rem;
            padding: .5rem 1.3rem;
            border: none;
            text-decoration: none;
            display: inline-block;
        }

        .btn-hijau:hover {
            background: var(--hijau-tua);
            color: #fff;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>/index.php"><img
        src="<?= BASE_URL ?>/assets/img/logo-sah.png" alt="Logo SAH Umrah" class="navbar-logo me-2">SAH <span>Umrah</span></a>
            <div class="d-flex gap-3 align-items-center">
                <a href="<?= BASE_URL ?>/pages/paket.php" class="nav-link">Paket Umrah</a>
                <a href="<?= BASE_URL ?>/pages/cek-booking.php" class="nav-link">Cek Booking</a>
                <a href="<?= BASE_URL ?>/pages/logout.php" class="nav-link"><i class="bi bi-box-arrow-right me-1"></i>Keluar</a>
            </div>
        </div>
    </nav>

    <section class="page-hero">
        <div class="container">
            <h1>Assalamu'alaikum, <?= htmlspecialchars($user['nama_lengkap']) ?> 👋</h1>
            <p class="mb-0" style="opacity:.85;font-size:.9rem">Ini ringkasan akun dan pendaftaran umrah Anda.</p>
        </div>
    </section>

    <div class="container content-wrap">
        <?= renderFlash() ?>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card-box text-center">
                    <div class="stat-num"><?= $totalBooking ?></div>
                    <div class="stat-label">Total Pendaftaran</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-box text-center">
                    <div class="stat-num"><?= $totalProses ?></div>
                    <div class="stat-label">Sedang Diproses</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-box text-center">
                    <div class="stat-num"><?= $totalLunas ?></div>
                    <div class="stat-label">Lunas</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card-box">
                    <h6 class="fw-bold mb-3" style="color:var(--hijau-tua)">
                        <i class="bi bi-calendar-check-fill me-2" style="color:var(--emas)"></i>Riwayat Pendaftaran
                    </h6>

                    <?php if (empty($bookings)): ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-x" style="font-size:2rem;opacity:.3"></i>
                            <p class="mt-2 mb-3">Anda belum memiliki pendaftaran umrah.</p>
                            <a href="<?= BASE_URL ?>/pages/paket.php" class="btn-hijau">Lihat Paket Umrah</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <div class="booking-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <div class="kode"><?= htmlspecialchars($b['kode_booking']) ?></div>
                                    <div style="font-size:.83rem;color:var(--teks-abu)">
                                        <?= htmlspecialchars($b['nama_paket'] ?? 'Paket tidak ditemukan') ?>
                                        <?php if (!empty($b['tanggal_berangkat'])): ?>
                                            &middot; Berangkat <?= tglIndo($b['tanggal_berangkat']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <?= statusBadge($b['status']) ?>
                                    <div style="font-size:.8rem;font-weight:600;margin-top:4px">
                                        <?= rupiah($b['total_harga']) ?>
                                    </div>
                                </div>
                                <a href="<?= BASE_URL ?>/pages/cek-booking.php?kode=<?= urlencode($b['kode_booking']) ?>"
                                    class="btn-hijau" style="padding:.4rem 1rem;font-size:.8rem">Detail</a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-box">
                    <h6 class="fw-bold mb-3" style="color:var(--hijau-tua)">
                        <i class="bi bi-person-fill me-2" style="color:var(--emas)"></i>Profil Saya
                    </h6>
                    <div style="font-size:.85rem" class="mb-2">
                        <div style="color:var(--teks-abu)">Nama Lengkap</div>
                        <div class="fw-semibold"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                    </div>
                    <div style="font-size:.85rem" class="mb-2">
                        <div style="color:var(--teks-abu)">Email</div>
                        <div class="fw-semibold"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <div style="font-size:.85rem">
                        <div style="color:var(--teks-abu)">No. WhatsApp / HP</div>
                        <div class="fw-semibold"><?= htmlspecialchars($user['telepon'] ?? '-') ?></div>
                    </div>
                </div>

                <div class="card-box">
                    <h6 class="fw-bold mb-3" style="color:var(--hijau-tua)">
                        <i class="bi bi-lightning-charge-fill me-2" style="color:var(--emas)"></i>Aksi Cepat
                    </h6>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/pages/paket.php" class="btn-hijau"><i
                                class="bi bi-briefcase-fill me-2"></i>Daftar Paket Baru</a>
                        <a href="<?= waLink() ?>" target="_blank" class="btn-hijau"
                            style="background:#25D366"><i class="bi bi-whatsapp me-2"></i>Hubungi Kami</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-4 mt-4" style="font-size:.82rem;color:var(--teks-abu)">
        © <?= date('Y') ?> PT. Sumatera Andalan Haramain. All rights reserved.
    </footer>
</body>

</html>
