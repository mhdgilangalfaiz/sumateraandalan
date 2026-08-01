<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$kode = trim($_GET['kode'] ?? '');
if (!$kode) {
    redirect(BASE_URL . '/pages/paket.php');
}

$booking = db()->fetchOne(
    "SELECT b.*, p.nama_paket, p.durasi, p.maskapai, j.tanggal_berangkat, j.tanggal_pulang
     FROM booking b
     JOIN paket_umrah p ON b.paket_id = p.id
     LEFT JOIN jadwal j ON b.jadwal_id = j.id
     WHERE b.kode_booking = ?",
    's',
    [$kode]
);

if (!$booking) {
    redirect(BASE_URL . '/pages/paket.php');
}

function tglIndo(string $tgl): string
{
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    [$y, $m, $d] = explode('-', $tgl);
    return (int) $d . ' ' . $bulan[(int) $m] . ' ' . $y;
}

$settings = [];
foreach (db()->fetchAll("SELECT nama_key, nilai FROM pengaturan") as $p)
    $settings[$p['nama_key']] = $p['nilai'];

$waNo = $settings['wa_admin'] ?? '6281362216482';
$waMsg = 'Assalamu\'alaikum, saya telah mendaftar paket umrah dengan kode booking: ' . $kode . '. Mohon konfirmasinya. Terima kasih.';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil – SAH Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --hijau-tua: #1B4D2E;
            --hijau: #1B6B3A;
            --emas: #C9A84C;
            --krem: #F9F5EE
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--krem);
            min-height: 100vh;
            display: flex;
            flex-direction: column
        }

        .navbar {
            background: rgba(27, 77, 46, .97);
            padding: .8rem 0
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff !important
        }

        .navbar-brand span {
            color: var(--emas)
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

        .sukses-wrap {
            max-width: 680px;
            margin: 60px auto;
            padding: 0 16px
        }

        .sukses-card {
            background: #fff;
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 8px 40px rgba(27, 77, 46, .12);
            text-align: center
        }

        .sukses-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--hijau-tua), var(--hijau));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            color: #fff
        }

        .sukses-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--hijau-tua);
            margin-bottom: .5rem
        }

        .sukses-sub {
            color: #666;
            font-size: .9rem;
            margin-bottom: 2rem
        }

        .kode-box {
            background: linear-gradient(135deg, var(--hijau-tua), var(--hijau));
            color: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem
        }

        .kode-label {
            font-size: .75rem;
            color: rgba(255, 255, 255, .7);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: .3rem
        }

        .kode-val {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--emas);
            letter-spacing: 2px
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: .88rem
        }

        .detail-table td {
            padding: .6rem .5rem;
            border-bottom: 1px solid #f0f0f0
        }

        .detail-table td:first-child {
            color: #888;
            width: 45%
        }

        .detail-table td:last-child {
            font-weight: 600;
            color: #333
        }

        .btn-cek {
            background: linear-gradient(135deg, var(--hijau-tua), var(--hijau));
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: .8rem 2rem;
            font-size: .95rem;
            font-weight: 700;
            width: 100%;
            transition: all .3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            margin-bottom: .8rem
        }

        .btn-cek:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(27, 77, 46, .3)
        }

        .btn-wa {
            background: #25D366;
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: .8rem 2rem;
            font-size: .95rem;
            font-weight: 700;
            width: 100%;
            transition: all .3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem
        }

        .btn-wa:hover {
            background: #1da851;
            color: #fff;
            transform: translateY(-2px)
        }

        .btn-back {
            background: transparent;
            color: var(--hijau);
            border: 2px solid var(--hijau);
            border-radius: 50px;
            padding: .7rem 2rem;
            font-size: .88rem;
            font-weight: 600;
            width: 100%;
            transition: all .3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            margin-top: .8rem
        }

        .btn-back:hover {
            background: var(--hijau);
            color: #fff
        }

        .info-box {
            background: #FFF8E8;
            border: 1px solid #F0D080;
            border-radius: 12px;
            padding: 1rem 1.2rem;
            font-size: .83rem;
            color: #7D5A00;
            margin-bottom: 1.5rem;
            text-align: left
        }

        footer {
            background: var(--hijau-tua);
            padding: 20px 0;
            color: rgba(255, 255, 255, .6);
            text-align: center;
            font-size: .82rem;
            margin-top: auto
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="<?= BASE_URL ?>/assets/img/logo-sah.png" alt="Logo SAH Umrah" class="navbar-logo me-2">SAH <span>Umrah</span>
            </a>
        </div>
    </nav>

    <div class="sukses-wrap">
        <div class="sukses-card">
            <div class="sukses-icon"><i class="bi bi-check-lg"></i></div>
            <div class="sukses-title">Pendaftaran Berhasil!</div>
            <div class="sukses-sub">Terima kasih, <strong>
                    <?= htmlspecialchars($booking['nama_pemesan']) ?>
                </strong>. Pendaftaran Anda telah kami terima.</div>

            <div class="kode-box">
                <div class="kode-label">Kode Booking Anda</div>
                <div class="kode-val">
                    <?= htmlspecialchars($booking['kode_booking']) ?>
                </div>
                <div style="font-size:.75rem;color:rgba(255,255,255,.6);margin-top:.5rem">Simpan kode ini untuk
                    keperluan konfirmasi</div>
            </div>

            <table class="detail-table mb-4">
                <tr>
                    <td>Paket</td>
                    <td>
                        <?= htmlspecialchars($booking['nama_paket']) ?>
                    </td>
                </tr>
                <tr>
                    <td>Durasi</td>
                    <td>
                        <?= $booking['durasi'] ?> Hari
                    </td>
                </tr>
                <?php if ($booking['tanggal_berangkat']): ?>
                    <tr>
                        <td>Keberangkatan</td>
                        <td>
                            <?= tglIndo($booking['tanggal_berangkat']) ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Kepulangan</td>
                        <td>
                            <?= tglIndo($booking['tanggal_pulang']) ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td>Jumlah Jamaah</td>
                    <td>
                        <?= $booking['jumlah_jamaah'] ?> Orang
                    </td>
                </tr>
                <tr>
                    <td>Total Pembayaran</td>
                    <td style="color:var(--hijau-tua)">Rp
                        <?= number_format($booking['total_harga'], 0, ',', '.') ?>
                    </td>
                </tr>
                <tr>
                    <td>DP Minimum</td>
                    <td style="color:var(--emas);font-weight:700">Rp
                        <?= number_format($booking['dp_amount'], 0, ',', '.') ?>
                    </td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td><span
                            style="background:#fef3c7;color:#92400e;padding:3px 12px;border-radius:20px;font-size:.78rem;font-weight:700">Menunggu
                            Konfirmasi</span></td>
                </tr>
            </table>

            <div class="info-box">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Langkah selanjutnya:</strong> Hubungi admin kami via WhatsApp untuk konfirmasi pendaftaran dan
                informasi pembayaran DP.
            </div>

            <a href="cek-booking.php?kode=<?= urlencode($booking['kode_booking']) ?>" class="btn-cek">
                <i class="bi bi-search"></i> Cek Status Booking Saya
            </a>
            <a href="https://wa.me/<?= $waNo ?>?text=<?= urlencode($waMsg) ?>" target="_blank" class="btn-wa">
                <i class="bi bi-whatsapp"></i> Konfirmasi via WhatsApp
            </a>
            <a href="paket.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Kembali ke Paket
            </a>
        </div>
    </div>

    <footer>©
        <?= date('Y') ?>
        <?= htmlspecialchars($settings['nama_perusahaan'] ?? 'PT. Sumatera Andalan Haramain') ?>. All rights reserved.
    </footer>

</body>

</html>