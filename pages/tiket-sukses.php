<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$kode = trim($_GET['kode'] ?? '');
if (!$kode) {
    redirect(BASE_URL . '/pages/tiket.php');
}

$booking = db()->fetchOne(
    "SELECT b.*, tp.maskapai, tp.kelas, tp.tipe_perjalanan, seg.kota_asal, seg.kota_tujuan, seg.tanggal AS tanggal_berangkat
     FROM booking b
     JOIN tiket_pesawat tp ON b.tiket_id = tp.id
     LEFT JOIN tiket_segmen seg ON seg.tiket_id = tp.id AND seg.arah = 'berangkat' AND seg.urutan = 1
     WHERE b.kode_booking = ?",
    's',
    [$kode]
);

if (!$booking) {
    redirect(BASE_URL . '/pages/tiket.php');
}

$settings = [];
foreach (db()->fetchAll("SELECT nama_key, nilai FROM pengaturan") as $p)
    $settings[$p['nama_key']] = $p['nilai'];

$waNo = $settings['wa_admin'] ?? '6281362216482';
$waMsg = "Assalamu'alaikum, saya telah memesan tiket pesawat dengan kode booking: {$kode}. Mohon konfirmasinya. Terima kasih.";
$sisaJam = max(0, round((strtotime($booking['batas_deposit']) - time()) / 3600, 1));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Berhasil – <?= htmlspecialchars($settings['nama_perusahaan'] ?? 'SAH Travel') ?></title>
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
            --krem: #F9F5EE;
            --font-display: 'Playfair Display', serif;
            --font-body: 'DM Sans', sans-serif
        }

        body {
            font-family: var(--font-body);
            background: var(--krem);
            display: flex;
            align-items: center;
            min-height: 100vh
        }

        .sukses-card {
            background: #fff;
            border-radius: 20px;
            padding: 3rem;
            max-width: 560px;
            margin: 0 auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .06)
        }

        .kode-box {
            background: var(--krem);
            border: 2px dashed var(--emas);
            border-radius: 14px;
            padding: 1.2rem;
            text-align: center;
            font-family: var(--font-display);
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--hijau-tua);
            letter-spacing: 2px;
            margin: 1.5rem 0
        }

        .deposit-warning {
            background: #FEF3C7;
            border-left: 4px solid #D97706;
            border-radius: 10px;
            padding: 1rem 1.2rem;
            font-size: .87rem;
            margin-bottom: 1.5rem
        }

        .btn-wa {
            background: #25D366;
            color: #fff;
            border: none;
            border-radius: 30px;
            padding: .85rem 1.8rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px
        }

        .btn-wa:hover {
            color: #fff;
            opacity: .9
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="sukses-card text-center">
            <i class="bi bi-check-circle-fill" style="font-size:3rem;color:var(--hijau)"></i>
            <h3 class="mt-3" style="font-family:var(--font-display);color:var(--hijau-tua)">Booking Berhasil Dibuat
            </h3>
            <p style="color:#6B6B6B">Simpan kode booking ini untuk konfirmasi pembayaran</p>

            <div class="kode-box"><?= htmlspecialchars($booking['kode_booking']) ?></div>

            <div class="text-start" style="font-size:.88rem;color:#444">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Rute</span>
                    <strong><?= htmlspecialchars(($booking['kota_asal'] ?? '-') . ' – ' . ($booking['kota_tujuan'] ?? '-')) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Maskapai</span><strong><?= htmlspecialchars($booking['maskapai']) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Jumlah Seat</span><strong><?= $booking['jumlah_jamaah'] ?> seat</strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span>Total Harga</span>
                    <strong>Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></strong>
                </div>
            </div>

            <div class="deposit-warning text-start mt-3">
                <i class="bi bi-clock-history me-2"></i>
                Lakukan transfer DP dalam <strong><?= $sisaJam ?> jam</strong> (batas: 1x24 jam dari sekarang),
                lalu kirim bukti transfer via WhatsApp beserta kode booking Anda. Kalau lewat dari waktu tersebut,
                booking otomatis dibatalkan dan seat dilepas kembali.
            </div>

            <a href="<?= waLink($waMsg, $waNo) ?>" class="btn-wa" target="_blank">
                <i class="bi bi-whatsapp"></i>Kirim Bukti Transfer via WhatsApp
            </a>

            <div class="mt-4">
                <a href="../pages/cek-booking.php" style="font-size:.85rem;color:var(--hijau)">Cek status booking
                    nanti di sini</a>
            </div>
        </div>
    </div>
</body>

</html>