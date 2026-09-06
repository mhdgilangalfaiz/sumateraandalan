<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$settings = [];
foreach (db()->fetchAll("SELECT nama_key, nilai FROM pengaturan") as $p)
    $settings[$p['nama_key']] = $p['nilai'];

$pesanMsg = "Assalamu'alaikum, saya ingin bertanya tentang layanan pengurusan visa umrah.";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visa Umrah – <?= htmlspecialchars($settings['nama_perusahaan'] ?? 'SAH Travel') ?></title>
    <meta name="description"
        content="Layanan pengurusan visa umrah, tersedia untuk jamaah maupun travel/agen lain.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --hijau-tua: #1B4D2E;
            --hijau: #1B6B3A;
            --hijau-muda: #2E8B57;
            --emas: #C9A84C;
            --emas-muda: #E8C97A;
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
            background: var(--putih);
            color: var(--teks-gelap);
            overflow-x: hidden
        }

        .navbar {
            background: rgba(27, 77, 46, .97);
            backdrop-filter: blur(10px);
            padding: .8rem 0;
            box-shadow: 0 4px 30px rgba(0, 0, 0, .2)
        }

        .navbar-brand {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.4rem;
            color: #fff !important;
            display: flex;
            align-items: center
        }

        .navbar-brand span {
            color: var(--emas)
        }

        .navbar-logo {
            height: 34px
        }

        .nav-link {
            color: rgba(255, 255, 255, .85) !important;
            font-size: .92rem;
            font-weight: 500;
            padding: .5rem .9rem !important
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--emas) !important
        }

        .btn-navbar {
            background: var(--emas);
            color: var(--hijau-tua) !important;
            border-radius: 30px;
            font-weight: 600 !important;
            padding: .5rem 1.2rem !important
        }

        .dropdown-menu {
            border: none;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .12);
            padding: .5rem
        }

        .dropdown-item {
            border-radius: 8px;
            font-size: .9rem;
            padding: .55rem .8rem
        }

        .dropdown-item:hover {
            background: var(--krem)
        }

        .page-hero {
            background: linear-gradient(135deg, var(--hijau-tua), var(--hijau));
            padding: 130px 0 60px;
            color: #fff
        }

        .page-hero-title {
            font-family: var(--font-display);
            font-size: 2.3rem;
            font-weight: 700
        }

        .page-hero-title span {
            color: var(--emas)
        }

        .page-hero-sub {
            opacity: .85;
            max-width: 600px
        }

        .breadcrumb a {
            color: rgba(255, 255, 255, .7);
            text-decoration: none
        }

        .breadcrumb-item.active {
            color: var(--emas)
        }

        .info-banner {
            background: var(--krem);
            border-radius: 16px;
            padding: 1.6rem;
            border-left: 4px solid var(--emas)
        }

        .section-label {
            color: var(--emas);
            font-weight: 700;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: .5rem
        }

        .section-title {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--hijau-tua)
        }

        .syarat-item,
        .step-item {
            background: #fff;
            border-radius: 14px;
            padding: 1.4rem;
            border: 1px solid #f0ead9;
            height: 100%
        }

        .syarat-icon,
        .step-num {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: var(--krem);
            color: var(--hijau);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem
        }

        .btn-cta {
            background: var(--hijau);
            color: #fff;
            border-radius: 30px;
            font-weight: 600;
            font-size: .92rem;
            padding: .75rem 1.8rem;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px
        }

        .btn-cta:hover {
            background: var(--hijau-tua);
            color: #fff
        }

        footer {
            background: var(--hijau-tua);
            color: rgba(255, 255, 255, .75);
            padding: 3.5rem 0 1.5rem
        }

        .footer-brand {
            font-family: var(--font-display);
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff
        }

        .footer-brand span {
            color: var(--emas)
        }

        .footer-link {
            display: block;
            color: rgba(255, 255, 255, .65);
            font-size: .85rem;
            margin-bottom: .5rem;
            text-decoration: none;
            transition: color .3s
        }

        .footer-link:hover {
            color: var(--emas)
        }

        .footer-bottom {
            margin-top: 2.5rem;
            padding-top: 1.2rem;
            border-top: 1px solid rgba(255, 255, 255, .1);
            font-size: .8rem;
            color: rgba(255, 255, 255, .4)
        }

        .wa-float {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            background: #25D366;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.6rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .25);
            z-index: 999
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><img src="<?= BASE_URL ?>/assets/img/logo-sah.png"
                    alt="Logo SAH Umrah" class="navbar-logo me-2">SAH <span>Umrah</span></a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <i class="bi bi-list text-white fs-4"></i>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                    <li class="nav-item"><a class="nav-link" href="../index.php#beranda">Beranda</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="layananDropdown" role="button"
                            data-bs-toggle="dropdown">Layanan Kami</a>
                        <ul class="dropdown-menu" aria-labelledby="layananDropdown">
                            <li><a class="dropdown-item" href="paket.php"><i
                                        class="bi bi-suitcase-lg me-2"></i>Paket Umrah</a></li>
                            <li><a class="dropdown-item active" href="visa.php"><i
                                        class="bi bi-file-earmark-text me-2"></i>Visa Umrah</a></li>
                            <li><a class="dropdown-item" href="tiket.php"><i
                                        class="bi bi-airplane me-2"></i>Tiket Pesawat</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="../index.php#testimoni">Testimoni</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php#faq">FAQ</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php#kontak">Kontak</a></li>
                    <li class="nav-item ms-2">
                        <a class="nav-link btn-navbar" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="page-hero">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0" style="font-size:.83rem">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item active">Visa Umrah</li>
                </ol>
            </nav>
            <h1 class="page-hero-title">Layanan <span>Visa</span> Umrah</h1>
            <p class="page-hero-sub">Pengurusan visa umrah yang cepat dan terpercaya — untuk jamaah perorangan
                maupun travel/agen lain yang butuh pengurusan visa tanpa harus ambil paket dari kami.</p>
        </div>
    </section>

    <section style="padding:50px 0 30px; background:var(--krem)">
        <div class="container">
            <div class="info-banner" data-aos="fade-up">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi bi-info-circle-fill" style="color:var(--emas);font-size:1.3rem"></i>
                    <div style="font-size:.88rem;line-height:1.6">
                        Kalau Anda mengambil <a href="paket.php">Paket Umrah</a> kami, visa sudah termasuk di dalam
                        harga paket. Layanan di halaman ini khusus untuk pengurusan visa <strong>terpisah</strong>
                        (standalone) — misalnya untuk travel/agen lain, atau jamaah yang keberangkatannya sudah
                        diatur sendiri dan hanya butuh bantuan visa.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section style="padding:20px 0 70px; background:var(--krem)">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <div class="section-label">Persyaratan</div>
                <h2 class="section-title">Dokumen yang Perlu Disiapkan</h2>
            </div>
            <div class="row g-4 mb-5">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="syarat-item">
                        <div class="syarat-icon"><i class="bi bi-passport"></i></div>
                        <div style="font-weight:600;margin-bottom:6px">Paspor</div>
                        <div style="font-size:.85rem;color:var(--teks-abu)">Masa berlaku minimal 6 bulan sejak
                            tanggal keberangkatan.</div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="80">
                    <div class="syarat-item">
                        <div class="syarat-icon"><i class="bi bi-camera"></i></div>
                        <div style="font-weight:600;margin-bottom:6px">Pas Foto</div>
                        <div style="font-size:.85rem;color:var(--teks-abu)">Foto terbaru sesuai standar visa umrah
                            (latar putih).</div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="160">
                    <div class="syarat-item">
                        <div class="syarat-icon"><i class="bi bi-clipboard2-pulse"></i></div>
                        <div style="font-weight:600;margin-bottom:6px">Sertifikat Vaksin Meningitis</div>
                        <div style="font-size:.85rem;color:var(--teks-abu)">Wajib dari fasilitas kesehatan yang
                            terdaftar.</div>
                    </div>
                </div>
            </div>

            <div class="text-center mb-5" data-aos="fade-up">
                <div class="section-label">Proses</div>
                <h2 class="section-title">Bagaimana Alurnya</h2>
            </div>
            <div class="row g-4 mb-5">
                <?php
                $steps = [
                    ['Kirim Dokumen', 'Serahkan paspor, foto, dan sertifikat vaksin ke tim kami.'],
                    ['Verifikasi', 'Tim kami cek kelengkapan sebelum dikirim ke provider visa.'],
                    ['Proses ke Provider', 'Pengajuan dikirim setelah pembayaran diterima penuh.'],
                    ['Visa Terbit', 'Kami informasikan begitu visa selesai diproses.'],
                ];
                foreach ($steps as $i => $s): ?>
                    <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
                        <div class="step-item">
                            <div class="step-num"><?= $i + 1 ?></div>
                            <div style="font-weight:600;margin-bottom:6px"><?= $s[0] ?></div>
                            <div style="font-size:.83rem;color:var(--teks-abu)"><?= $s[1] ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="info-banner" data-aos="fade-up" style="border-left-color:var(--hijau)">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi bi-clock-history" style="color:var(--hijau);font-size:1.3rem"></i>
                    <div style="font-size:.88rem;line-height:1.6">
                        <strong>Catatan pembayaran:</strong> pengajuan visa standalone baru kami proses ke provider
                        setelah pembayaran diterima <strong>penuh (lunas)</strong> — tidak ada skema cicilan untuk
                        layanan visa terpisah ini.
                    </div>
                </div>
            </div>

            <div class="text-center mt-5" data-aos="fade-up">
                <a href="<?= waLink($pesanMsg) ?>" class="btn-cta" target="_blank">
                    <i class="bi bi-whatsapp"></i>Konsultasi via WhatsApp
                </a>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand mb-2"><i class="bi bi-moon-stars-fill me-2"
                            style="color:var(--emas)"></i>SAH <span>Travel</span></div>
                    <p style="font-size:.85rem;line-height:1.7">
                        <?= htmlspecialchars($settings['tagline'] ?? 'Perjalanan Suci, Pelayanan Terpercaya') ?>
                    </p>
                </div>
                <div class="col-6 col-lg-2">
                    <div
                        style="color:var(--emas);font-weight:600;font-size:.85rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:1rem">
                        Layanan</div>
                    <a href="paket.php" class="footer-link">Paket Umrah</a>
                    <a href="visa.php" class="footer-link">Visa Umrah</a>
                    <a href="tiket.php" class="footer-link">Tiket Pesawat</a>
                </div>
                <div class="col-6 col-lg-2">
                    <div
                        style="color:var(--emas);font-weight:600;font-size:.85rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:1rem">
                        Menu</div>
                    <a href="../index.php" class="footer-link">Home</a>
                    <a href="kontak.php" class="footer-link">Kontak</a>
                    <a href="cek-booking.php" class="footer-link">Cek Booking</a>
                </div>
                <div class="col-lg-4">
                    <div
                        style="color:var(--emas);font-weight:600;font-size:.85rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:1rem">
                        Butuh Bantuan?</div>
                    <p style="font-size:.83rem;line-height:1.6">Konsultasikan kebutuhan visa umrah Anda langsung
                        dengan tim kami.</p>
                    <a href="<?= waLink() ?>" class="btn btn-outline-warning rounded-pill px-3"
                        style="font-size:.83rem" target="_blank">
                        <i class="bi bi-whatsapp me-2"></i>Chat WhatsApp
                    </a>
                </div>
            </div>
            <div class="footer-bottom text-center">© <?= date('Y') ?>
                <?= htmlspecialchars($settings['nama_perusahaan'] ?? 'PT. Sumatera Andalan Haramain') ?>. All rights
                reserved.
            </div>
        </div>
    </footer>

    <a href="<?= waLink() ?>" class="wa-float" target="_blank"><i class="bi bi-whatsapp"></i></a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>AOS.init({ once: true, offset: 60, duration: 300 });</script>
</body>

</html>