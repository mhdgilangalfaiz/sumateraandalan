<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
requireUserLogin();

$user = currentUser();

// ── KLAIM BOOKING LAMA (sebelum punya akun) ──────────────────────
// Booking guest lama TIDAK otomatis muncul di dashboard hanya berdasarkan
// kecocokan email — karena email pendaftaran akun tidak diverifikasi,
// itu bisa disalahgunakan orang lain untuk melihat booking orang lain.
// Sebagai gantinya, user harus membuktikan kepemilikan booking dengan
// memasukkan kode booking + email/telepon yang dipakai saat booking,
// sama seperti alur di pages/cek-booking.php.
$claimError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['klaim_kode'])) {
    checkCsrf();
    $kode = sanitize($_POST['klaim_kode'] ?? '');
    $kontak = sanitize($_POST['klaim_kontak'] ?? '');

    if (!$kode || !$kontak) {
        $claimError = 'Kode booking dan email/telepon wajib diisi.';
    } else {
        $booking = db()->fetchOne(
            "SELECT id, user_id FROM booking WHERE kode_booking = ? AND (email = ? OR telepon = ?)",
            'sss',
            [$kode, $kontak, $kontak]
        );
        if (!$booking) {
            $claimError = 'Kode booking tidak ditemukan, atau email/telepon tidak cocok dengan data booking tersebut.';
        } elseif ($booking['user_id']) {
            $claimError = $booking['user_id'] == $user['id']
                ? 'Booking ini sudah terhubung ke akun Anda.'
                : 'Booking ini sudah diklaim oleh akun lain.';
        } else {
            db()->execute("UPDATE booking SET user_id = ? WHERE id = ?", 'ii', [$user['id'], $booking['id']]);
            redirect(BASE_URL . '/user/dashboard.php', 'Booking berhasil dihubungkan ke akun Anda.', 'sukses');
        }
    }
}

// Riwayat booking: HANYA yang sudah terhubung ke akun ini (user_id).
// Booking lama yang belum diklaim tidak ikut tampil (lihat form klaim di atas).
$bookings = db()->fetchAll(
    "SELECT b.*,
        pk.nama_paket,
        tp.maskapai, tp.kelas, tp.tipe_perjalanan,
        seg.kota_asal, seg.kota_tujuan, seg.tanggal AS tanggal_berangkat,
        (SELECT status FROM visa_applications WHERE booking_id = b.id LIMIT 1) AS visa_status,
        (SELECT COALESCE(SUM(jumlah),0) FROM pembayaran WHERE booking_id = b.id AND status = 'verified') AS total_verified,
        (SELECT COUNT(*) FROM pembayaran WHERE booking_id = b.id AND status = 'pending') AS bukti_pending
     FROM booking b
     LEFT JOIN paket_umrah pk ON b.paket_id = pk.id
     LEFT JOIN tiket_pesawat tp ON b.tiket_id = tp.id
     LEFT JOIN tiket_segmen seg ON seg.tiket_id = tp.id AND seg.arah = 'berangkat' AND seg.urutan = 1
     WHERE b.user_id = ?
     ORDER BY (b.status IN ('selesai','cancelled')) ASC, b.created_at DESC",
    'i',
    [$user['id']]
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan – SAH Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <?php include __DIR__ . '/../includes/navbar-style.php'; ?>
    <style>
        :root {
            --hijau-tua: #1B4D2E;
            --hijau: #1B6B3A;
            --emas: #C9A84C;
            --krem: #F9F5EE;
            --font-display: 'Playfair Display', serif;
            --font-body: 'DM Sans', sans-serif
        }

        body {
            font-family: var(--font-body);
            background: var(--krem);
            min-height: 100vh
        }

        /* Halaman ini tidak punya hero image gelap di bawah navbar, jadi
           navbar dipaksa solid dari awal (bukan transparan-lalu-scroll)
           supaya teksnya tetap kebaca di atas background krem. */
        .navbar {
            background: rgba(27, 77, 46, .97) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, .15);
        }

        .container.py-5 {
            padding-top: 6.5rem !important
        }

        @media (max-width: 991px) {
            .container.py-5 {
                padding-top: 5.5rem !important
            }
        }

        .page-title {
            font-family: var(--font-display);
            font-weight: 700;
            color: var(--hijau-tua)
        }

        .welcome-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 1.6rem
        }

        .welcome-sub {
            font-size: .85rem;
            color: #6B6B6B;
            margin-top: 2px
        }

        .btn-cari-layanan {
            background: var(--hijau);
            color: #fff;
            border-radius: 50px;
            font-size: .82rem;
            font-weight: 600;
            padding: 9px 20px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap
        }

        .btn-cari-layanan:hover {
            background: var(--hijau-tua);
            color: #fff
        }

        .empty-state {
            background: #fff;
            border: 1px dashed #e5ddc8;
            border-radius: 18px;
            padding: 3.2rem 1.5rem;
            text-align: center
        }

        .order-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.4rem 1.6rem;
            border: 1px solid #f0ead9;
            margin-bottom: 1rem
        }

        .badge-item {
            font-size: .72rem;
            color: var(--hijau);
            border: 1px solid #d8ecdf;
            background: #eef8f1;
            padding: 3px 10px;
            border-radius: 20px;
            margin-right: 6px
        }

        .badge-status {
            font-size: .72rem;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600
        }

        .st-pending {
            background: #FEF3C7;
            color: #92400E
        }

        .st-confirmed,
        .st-dp_paid {
            background: #DBEAFE;
            color: #1E40AF
        }

        .st-lunas,
        .st-selesai {
            background: #DCFCE7;
            color: #166534
        }

        .st-cancelled {
            background: #FEE2E2;
            color: #991B1B
        }

        .action-note {
            background: #FEF3C7;
            border-left: 3px solid #D97706;
            border-radius: 8px;
            padding: .6rem .9rem;
            font-size: .8rem;
            margin-top: .8rem
        }

        .btn-aksi {
            background: var(--hijau);
            color: #fff;
            border-radius: 20px;
            font-size: .8rem;
            padding: 6px 16px;
            text-decoration: none;
            display: inline-block;
            margin-top: .6rem
        }

        .btn-aksi:hover {
            background: var(--hijau-tua);
            color: #fff
        }

        .claim-card {
            background: #fff;
            border: 1px dashed var(--emas);
            border-radius: 16px;
            padding: 1.2rem 1.4rem;
            margin-bottom: 1.6rem
        }

        .claim-card summary {
            cursor: pointer;
            font-weight: 600;
            color: var(--hijau-tua);
            font-size: .9rem
        }

        .claim-card summary::-webkit-details-marker {
            display: none
        }
    </style>
</head>

<body>
    <?php $navActive = ''; include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="welcome-bar">
            <div>
                <h4 class="page-title mb-0">Riwayat Pesanan Saya</h4>
                <div class="welcome-sub">Halo, <?= htmlspecialchars(explode(' ', $user['nama_lengkap'])[0]) ?> — semua
                    pesanan Anda ada di sini.</div>
            </div>
            <a href="<?= BASE_URL ?>/pages/paket.php" class="btn-cari-layanan">
                <i class="bi bi-search"></i> Cari Layanan Baru
            </a>
        </div>

        <details class="claim-card">
            <summary><i class="bi bi-link-45deg me-1"></i>Punya booking lama sebelum punya akun? Klaim di sini</summary>
            <p style="font-size:.82rem;color:#6B6B6B;margin-top:.7rem">
                Masukkan kode booking dan email/telepon yang Anda pakai saat memesan, supaya booking itu terhubung ke
                akun Anda dan muncul di riwayat pesanan.
            </p>
            <?php if ($claimError): ?>
                <div class="alert alert-danger py-2" style="font-size:.85rem"><?= htmlspecialchars($claimError) ?></div>
            <?php endif; ?>
            <form method="POST" class="row g-2">
                <?= csrfField() ?>
                <div class="col-sm-5">
                    <input type="text" name="klaim_kode" class="form-control form-control-sm" placeholder="Kode booking" required>
                </div>
                <div class="col-sm-5">
                    <input type="text" name="klaim_kontak" class="form-control form-control-sm" placeholder="Email atau no. telepon" required>
                </div>
                <div class="col-sm-2">
                    <button type="submit" class="btn btn-sm w-100" style="background:var(--hijau);color:#fff">Klaim</button>
                </div>
            </form>
        </details>

        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox" style="font-size:2.8rem;color:#d1c7a3"></i>
                <p class="mt-3 mb-1" style="color:#444;font-weight:600">Belum ada pesanan</p>
                <p style="color:#94a3b8;font-size:.85rem;margin-bottom:1.4rem">Yuk mulai rencanakan perjalanan ibadah
                    Anda bersama kami.</p>
                <a href="<?= BASE_URL ?>/pages/paket.php" class="btn-aksi" style="margin-top:0">
                    <i class="bi bi-suitcase-lg me-1"></i>Lihat Paket Umrah
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $b):
                $butuhAksi = $b['status'] === 'pending' && !$b['bukti_pending'];
                $sisaJam = $b['batas_deposit'] ? round((strtotime($b['batas_deposit']) - time()) / 3600, 1) : null;
                ?>
                <div class="order-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div style="font-weight:700;font-size:.95rem">
                                <?= htmlspecialchars($b['kode_booking']) ?>
                            </div>
                            <div style="font-size:.78rem;color:#94a3b8">
                                <?= tglIndo(date('Y-m-d', strtotime($b['created_at']))) ?>
                            </div>
                        </div>
                        <span class="badge-status st-<?= $b['status'] ?>"><?= ucfirst(str_replace('_', ' ', $b['status'])) ?></span>
                    </div>

                    <div class="mt-2">
                        <?php if ($b['nama_paket']): ?>
                            <span class="badge-item"><i class="bi bi-suitcase-lg me-1"></i>
                                <?= htmlspecialchars($b['nama_paket']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($b['maskapai']): ?>
                            <span class="badge-item"><i class="bi bi-airplane me-1"></i>
                                <?= htmlspecialchars(($b['kota_asal'] ?? '') . ' – ' . ($b['kota_tujuan'] ?? '') . ' (' . $b['maskapai'] . ')') ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($b['visa_status']): ?>
                            <span class="badge-item"><i class="bi bi-file-earmark-text me-1"></i>Visa:
                                <?= ucwords(str_replace('_', ' ', $b['visa_status'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div style="font-size:.85rem;color:#444;margin-top:.6rem">
                        Total: <strong>Rp <?= number_format($b['total_harga'], 0, ',', '.') ?></strong>
                        · Terverifikasi: Rp <?= number_format($b['total_verified'], 0, ',', '.') ?>
                    </div>

                    <?php if ($b['bukti_pending'] > 0): ?>
                        <div class="action-note"><i class="bi bi-hourglass-split me-1"></i>Bukti transfer sedang
                            menunggu verifikasi admin.</div>
                    <?php elseif ($butuhAksi && $b['tiket_id']): ?>
                        <div class="action-note">
                            <i class="bi bi-clock-history me-1"></i>
                            <?= $sisaJam !== null && $sisaJam > 0 ? "Segera transfer DP, sisa waktu {$sisaJam} jam." : 'Batas waktu deposit sudah lewat.' ?>
                        </div>
                        <a href="../pages/upload-bukti.php?kode=<?= urlencode($b['kode_booking']) ?>" class="btn-aksi">
                            <i class="bi bi-upload me-1"></i>Upload Bukti Transfer
                        </a>
                    <?php elseif ($butuhAksi): ?>
                        <a href="../pages/upload-bukti.php?kode=<?= urlencode($b['kode_booking']) ?>" class="btn-aksi">
                            <i class="bi bi-upload me-1"></i>Upload Bukti Transfer
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>