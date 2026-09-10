<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$tiketId = (int) ($_GET['id'] ?? 0);
$tiket = db()->fetchOne(
    "SELECT tp.*, seg.kota_asal, seg.kota_tujuan, seg.tanggal AS tanggal_berangkat, seg.jam, seg.kode_penerbangan
     FROM tiket_pesawat tp
     LEFT JOIN tiket_segmen seg ON seg.tiket_id = tp.id AND seg.arah = 'berangkat' AND seg.urutan = 1
     WHERE tp.id = ? AND tp.status = 'aktif'",
    'i',
    [$tiketId]
);

if (!$tiket) {
    redirect(BASE_URL . '/pages/tiket.php');
}

$sisaSeat = max(0, $tiket['kuota'] - $tiket['terisi']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $nama = sanitize($_POST['nama_pemesan'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $telepon = sanitize($_POST['telepon'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');
    $jumlah = max(1, (int) ($_POST['jumlah_seat'] ?? 1));
    $catatan = sanitize($_POST['catatan'] ?? '');

    if (!$nama || !$email || !$telepon) {
        $errors[] = 'Nama, email, dan telepon wajib diisi.';
    }
    if ($jumlah > $sisaSeat) {
        $errors[] = 'Jumlah seat melebihi sisa yang tersedia saat ini.';
    }

    if (empty($errors)) {
        db()->beginTransaction();
        try {
            // Kunci baris tiket & cek ulang kuota DI DALAM transaction —
            // pola yang sama seperti perbaikan bug kuota paket sebelumnya,
            // supaya tidak race condition kalau ada 2 orang pesan bersamaan.
            $tiketLocked = db()->fetchOne("SELECT kuota, terisi FROM tiket_pesawat WHERE id = ? FOR UPDATE", 'i', [$tiketId]);
            $sisaSekarang = $tiketLocked ? ($tiketLocked['kuota'] - $tiketLocked['terisi']) : 0;
            if (!$tiketLocked || $sisaSekarang < $jumlah) {
                throw new Exception('Mohon maaf, seat baru saja diambil pemesan lain. Silakan pilih tiket lain.');
            }

            $tahun = date('Y');
            $totalTahunIni = (int) db()->fetchOne("SELECT COUNT(*) as c FROM booking WHERE YEAR(tanggal_booking) = ?", 'i', [$tahun])['c'];
            $kodeBooking = 'TKT-' . $tahun . '-' . str_pad($totalTahunIni + 1, 4, '0', STR_PAD_LEFT);
            $totalHarga = $tiket['harga'] * $jumlah;
            $batasDeposit = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $bookingId = db()->insert(
                "INSERT INTO booking (kode_booking, nama_pemesan, email, telepon, alamat, paket_id, tiket_id, jumlah_jamaah, harga_per_orang, total_harga, catatan, status, batas_deposit)
                 VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, 'pending', ?)",
                'sssssiiddss',
                [$kodeBooking, $nama, $email, $telepon, $alamat, $tiketId, $jumlah, $tiket['harga'], $totalHarga, $catatan, $batasDeposit]
            );

            if (!$bookingId) {
                throw new Exception('Terjadi kesalahan sistem. Silakan coba lagi.');
            }

            $terisiBaru = $tiketLocked['terisi'] + $jumlah;
            $statusBaru = $terisiBaru >= $tiketLocked['kuota'] ? 'penuh' : 'aktif';
            db()->execute("UPDATE tiket_pesawat SET terisi = ?, status = ? WHERE id = ?", 'isi', [$terisiBaru, $statusBaru, $tiketId]);

            db()->commit();

            db()->insert(
                "INSERT INTO notifikasi (judul, pesan, tipe, link) VALUES (?, ?, 'booking', ?)",
                'sss',
                [
                    'Booking Tiket Baru: ' . $kodeBooking,
                    $nama . ' memesan ' . $jumlah . ' seat tiket ' . $tiket['maskapai'],
                    BASE_URL . '/admin/booking-detail.php?id=' . $bookingId
                ]
            );

            redirect(BASE_URL . '/pages/tiket-sukses.php?kode=' . $kodeBooking);
        } catch (Exception $e) {
            db()->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

$settings = [];
foreach (db()->fetchAll("SELECT nama_key, nilai FROM pengaturan") as $p)
    $settings[$p['nama_key']] = $p['nilai'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Tiket Pesawat – <?= htmlspecialchars($settings['nama_perusahaan'] ?? 'SAH Travel') ?></title>
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
            --putih: #FDFAF5;
            --teks-gelap: #1A1A1A;
            --teks-abu: #6B6B6B;
            --font-display: 'Playfair Display', serif;
            --font-body: 'DM Sans', sans-serif
        }

        * {
            box-sizing: border-box
        }

        body {
            font-family: var(--font-body);
            background: var(--krem);
            color: var(--teks-gelap)
        }

        /* Navbar sekarang di includes/navbar-style.php */

        .page-content-offset {
            padding-top: 130px
        }

        @media (max-width: 767px) {
            .page-content-offset {
                padding-top: 100px
            }
        }

        @media (max-width: 480px) {
            .page-content-offset {
                padding-top: 90px
            }
        }

        .ringkasan-card {
            background: var(--hijau-tua);
            color: #fff;
            border-radius: 16px;
            padding: 1.6rem;
            position: sticky;
            top: 20px
        }

        .ringkasan-card .rute {
            font-family: var(--font-display);
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 4px
        }

        .ringkasan-row {
            display: flex;
            justify-content: space-between;
            font-size: .85rem;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, .12)
        }

        .ringkasan-total {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--emas);
            margin-top: 12px
        }

        .form-card {
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid #f0ead9
        }

        .form-label {
            font-size: .85rem;
            font-weight: 600;
            color: var(--teks-gelap)
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #e5ddc9;
            padding: .65rem .9rem;
            font-size: .9rem
        }

        .form-control:focus {
            border-color: var(--hijau);
            box-shadow: none
        }

        .btn-submit {
            background: var(--hijau);
            color: #fff;
            border: none;
            border-radius: 30px;
            padding: .85rem 2rem;
            font-weight: 600;
            font-size: .95rem
        }

        .btn-submit:hover {
            background: var(--hijau-tua);
            color: #fff
        }

        .deposit-note {
            background: #FEF3C7;
            border-left: 4px solid #D97706;
            border-radius: 10px;
            padding: 1rem 1.2rem;
            font-size: .85rem;
            margin-bottom: 1.5rem
        }
    </style>
</head>

<body>
    <?php $navActive = 'tiket'; include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container py-5 page-content-offset">
        <a href="tiket.php" class="btn btn-outline-secondary btn-sm rounded-pill mb-3"><i
                class="bi bi-arrow-left me-1"></i>Kembali</a>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4 order-lg-2">
                <div class="ringkasan-card">
                    <div class="rute">
                        <?= htmlspecialchars(($tiket['kota_asal'] ?? '-') . ' – ' . ($tiket['kota_tujuan'] ?? '-')) ?>
                    </div>
                    <div style="font-size:.85rem;opacity:.85">
                        <?= htmlspecialchars($tiket['maskapai']) ?> ·
                        <?= $tiket['tanggal_berangkat'] ? tglIndo($tiket['tanggal_berangkat']) : 'Tanggal menyusul' ?>
                    </div>
                    <div class="ringkasan-row">
                        <span>Kelas</span><span><?= ucfirst($tiket['kelas']) ?></span>
                    </div>
                    <div class="ringkasan-row">
                        <span>Tipe</span><span><?= $tiket['tipe_perjalanan'] === 'pp' ? 'Pulang-Pergi' : 'Sekali Jalan' ?></span>
                    </div>
                    <div class="ringkasan-row">
                        <span>Harga / seat</span><span>Rp <?= number_format($tiket['harga'], 0, ',', '.') ?></span>
                    </div>
                    <div class="ringkasan-row" style="border-bottom:none">
                        <span>Sisa seat</span><span><?= $sisaSeat ?> seat</span>
                    </div>
                    <div class="ringkasan-total" id="totalHarga">Rp <?= number_format($tiket['harga'], 0, ',', '.') ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 order-lg-1">
                <div class="form-card">
                    <h4 class="mb-4" style="font-family:var(--font-display);color:var(--hijau-tua)">Data Pemesan</h4>

                    <div class="deposit-note">
                        <i class="bi bi-clock-history me-2"></i>
                        Setelah booking dibuat, Anda punya waktu <strong>1x24 jam</strong> untuk transfer DP. Kalau
                        lewat dari itu, seat otomatis dilepas kembali.
                    </div>

                    <form method="POST">
                        <?= csrfField() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama_pemesan" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. Telepon/WhatsApp <span class="text-danger">*</span></label>
                                <input type="text" name="telepon" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Seat <span class="text-danger">*</span></label>
                                <input type="number" id="jumlahSeat" name="jumlah_seat" class="form-control" min="1"
                                    max="<?= $sisaSeat ?>" value="1" required>
                                <div style="font-size:.75rem;color:var(--teks-abu);margin-top:4px">Maksimal
                                    <?= $sisaSeat ?> seat tersedia</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="alamat" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan (opsional)</label>
                                <textarea name="catatan" class="form-control" rows="2"
                                    placeholder="Contoh: nama travel/agen kalau memesan untuk pihak lain"></textarea>
                            </div>
                            <div class="col-12 pt-2">
                                <button type="submit" class="btn-submit">
                                    <i class="bi bi-check-circle-fill me-2"></i>Buat Pemesanan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const hargaSatuan = <?= (float) $tiket['harga'] ?>;
        document.getElementById('jumlahSeat').addEventListener('input', function () {
            const jml = parseInt(this.value) || 0;
            document.getElementById('totalHarga').textContent =
                'Rp ' + Math.round(hargaSatuan * jml).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    </script>
</body>

</html>