<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$settings = [];
foreach (db()->fetchAll("SELECT nama_key, nilai FROM pengaturan") as $p)
    $settings[$p['nama_key']] = $p['nilai'];

$alamat = $settings['alamat'] ?? 'Medan, Sumatera Utara';
$telepon = $settings['telepon'] ?? WA_NUMBER;
$email = $settings['email'] ?? 'info@sahtravel.com';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak – <?= htmlspecialchars($settings['nama_perusahaan'] ?? 'SAH Travel') ?></title>
    <meta name="description" content="Hubungi SAH Umrah untuk konsultasi paket umrah, jadwal keberangkatan, dan informasi pendaftaran.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
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
            --krem-tua: #EFE8D8;
            --putih: #FDFAF5;
            --teks-gelap: #1A1A1A;
            --teks-abu: #6B6B6B;
            --font-arab: 'Amiri', serif;
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
            background: rgba(27, 77, 46, 0.97);
            backdrop-filter: blur(10px);
            padding: .8rem 0;
            box-shadow: 0 4px 30px rgba(0, 0, 0, .2)
        }

        .navbar-brand {
            font-family: var(--font-display);
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff !important;
            display: flex;
            align-items: center;
        }

        .navbar-brand span {
            color: var(--emas)
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

        .nav-link:hover,
        .nav-link.active {
            color: var(--emas) !important
        }

        .page-hero {
            background: linear-gradient(135deg, #0D2B1A, #1B4D2E 50%, #1B6B3A);
            padding: 120px 0 60px;
            position: relative;
            overflow: hidden
        }

        @media (max-width: 767px) {
            .page-hero {
                padding: 70px 0 30px
            }
        }

        .page-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: .05;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23C9A84C' fill-opacity='1'%3E%3Cpath d='M30 0L39 20.5H60L42.5 33.2L49.5 53.5L30 40.5L10.5 53.5L17.5 33.2L0 20.5H21L30 0Z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")
        }

        .page-hero-title {
            font-family: var(--font-display);
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            font-weight: 700;
            color: #fff
        }

        .page-hero-title span {
            color: var(--emas)
        }

        .page-hero-sub {
            color: rgba(255, 255, 255, .7);
            font-size: 1rem
        }

        .breadcrumb-item a {
            color: var(--emas-muda);
            text-decoration: none
        }

        .breadcrumb-item.active {
            color: rgba(255, 255, 255, .6)
        }

        .breadcrumb-item+.breadcrumb-item::before {
            color: rgba(255, 255, 255, .4)
        }

        .kontak-section {
            padding: 70px 0 80px;
            background: var(--krem)
        }

        .info-card {
            background: #fff;
            border-radius: 18px;
            padding: 1.6rem;
            border: 1px solid rgba(27, 107, 58, .08);
            box-shadow: 0 4px 20px rgba(27, 77, 46, .06);
            display: flex;
            gap: 16px;
            align-items: flex-start;
            height: 100%;
            transition: all .3s
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 45px rgba(27, 77, 46, .12)
        }

        .info-icon {
            flex-shrink: 0;
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--hijau), var(--hijau-muda));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem
        }

        .info-title {
            font-weight: 700;
            color: var(--hijau-tua);
            font-size: .95rem;
            margin-bottom: .3rem
        }

        .info-text {
            color: var(--teks-abu);
            font-size: .87rem;
            line-height: 1.6;
            margin-bottom: 0
        }

        .info-text a {
            color: var(--teks-abu);
            text-decoration: none
        }

        .info-text a:hover {
            color: var(--hijau)
        }

        .jam-item {
            display: flex;
            justify-content: space-between;
            font-size: .87rem;
            padding: .5rem 0;
            border-bottom: 1px dashed rgba(27, 107, 58, .15)
        }

        .jam-item:last-child {
            border-bottom: none
        }

        /* ===== QUICK ACTIONS ===== */
        .quick-actions {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px
        }

        .quick-btn {
            flex: 1 1 220px;
            display: flex;
            align-items: center;
            gap: 14px;
            background: #fff;
            border: 1px solid rgba(27, 107, 58, .1);
            border-radius: 16px;
            padding: 1.1rem 1.3rem;
            text-decoration: none;
            box-shadow: 0 4px 20px rgba(27, 77, 46, .06);
            transition: all .3s
        }

        .quick-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(27, 77, 46, .12)
        }

        .quick-btn-icon {
            flex-shrink: 0;
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #fff
        }

        .quick-btn.wa .quick-btn-icon {
            background: #25D366
        }

        .quick-btn.call .quick-btn-icon {
            background: var(--hijau)
        }

        .quick-btn.rute .quick-btn-icon {
            background: var(--emas);
            color: var(--hijau-tua)
        }

        .quick-btn-title {
            font-weight: 700;
            color: var(--hijau-tua);
            font-size: .92rem;
            margin-bottom: 0
        }

        .quick-btn-sub {
            color: var(--teks-abu);
            font-size: .78rem
        }

        @media (max-width: 575px) {
            .quick-actions {
                flex-direction: column
            }
        }

        /* ===== MAP ===== */
        .map-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem
        }

        .map-title i {
            color: var(--hijau);
            font-size: 1.3rem
        }

        .map-wrap {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(27, 77, 46, .1);
            width: 100%;
            height: 480px
        }

        .map-wrap iframe {
            width: 100%;
            height: 100%;
            border: 0
        }

        @media (max-width: 991px) {
            .map-wrap {
                height: 400px
            }
        }

        @media (max-width: 575px) {
            .map-wrap {
                height: 300px;
                border-radius: 16px
            }
        }

        .wa-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #25D366;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 8px 25px rgba(37, 211, 102, .45);
            text-decoration: none
        }

        footer {
            background: var(--hijau-tua);
            padding: 50px 0 25px;
            color: rgba(255, 255, 255, .7)
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
            color: rgba(255, 255, 255, .65);
            text-decoration: none;
            font-size: .85rem;
            display: block;
            margin-bottom: .5rem;
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
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><img src="<?= BASE_URL ?>/assets/img/logo-sah.png"
                    alt="Logo SAH Umrah" class="navbar-logo me-2">SAH
                <span>Umrah</span></a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <i class="bi bi-list text-white fs-4"></i>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                    <li class="nav-item"><a class="nav-link" href="../index.php#beranda">Beranda</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="layananDropdown" role="button"
                            data-bs-toggle="dropdown">Layanan Kami</a>
                        <ul class="dropdown-menu" aria-labelledby="layananDropdown">
                            <li><a class="dropdown-item" href="paket.php"><i
                                        class="bi bi-suitcase-lg me-2"></i>Paket Umrah</a></li>
                            <li><a class="dropdown-item" href="visa.php"><i
                                        class="bi bi-file-earmark-text me-2"></i>Visa Umrah</a></li>
                            <li><a class="dropdown-item" href="tiket.php"><i
                                        class="bi bi-airplane me-2"></i>Tiket Pesawat</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="../index.php#testimoni">Testimoni</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php#faq">FAQ</a></li>
                    <li class="nav-item"><a class="nav-link active" href="../index.php#kontak">Kontak</a></li>
                    <li class="nav-item ms-2">
                        <a class="nav-link btn-navbar" href="cek-booking.php">
                            <i class="bi bi-search me-1"></i>Cek Booking
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
                    <li class="breadcrumb-item active">Kontak</li>
                </ol>
            </nav>
            <h1 class="page-hero-title">Hubungi <span>Kami</span></h1>
            <p class="page-hero-sub">Tim kami siap membantu konsultasi paket umrah Anda</p>
        </div>
    </section>

    <section class="kontak-section">
        <div class="container">

            <!-- INFO CARDS -->
            <div class="row g-4 mb-5">
                <div class="col-md-6 col-lg-3" data-aos="fade-up">
                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-whatsapp"></i></div>
                        <div>
                            <div class="info-title">WhatsApp</div>
                            <p class="info-text"><a href="<?= waLink() ?>" target="_blank"><?= htmlspecialchars($telepon) ?></a></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-envelope"></i></div>
                        <div>
                            <div class="info-title">Email</div>
                            <p class="info-text"><a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <div class="info-title">Kantor Kami</div>
                            <p class="info-text"><?= htmlspecialchars($alamat) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-clock"></i></div>
                        <div style="width:100%">
                            <div class="info-title">Jam Operasional</div>
                            <div class="jam-item"><span>Senin – Jumat</span><span>08.00 – 17.00</span></div>
                            <div class="jam-item"><span>Sabtu</span><span>08.00 – 14.00</span></div>
                            <div class="jam-item"><span>Minggu</span><span>Libur</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="quick-actions" data-aos="fade-up">
                <a href="<?= waLink() ?>" target="_blank" class="quick-btn wa">
                    <div class="quick-btn-icon"><i class="bi bi-whatsapp"></i></div>
                    <div>
                        <div class="quick-btn-title">Chat WhatsApp</div>
                        <div class="quick-btn-sub">Balasan cepat oleh tim kami</div>
                    </div>
                </a>
                <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $telepon)) ?>" class="quick-btn call">
                    <div class="quick-btn-icon"><i class="bi bi-telephone-fill"></i></div>
                    <div>
                        <div class="quick-btn-title">Telepon Sekarang</div>
                        <div class="quick-btn-sub"><?= htmlspecialchars($telepon) ?></div>
                    </div>
                </a>
                <a href="https://www.google.com/maps?q=<?= urlencode($alamat) ?>" target="_blank" class="quick-btn rute">
                    <div class="quick-btn-icon"><i class="bi bi-signpost-2-fill"></i></div>
                    <div>
                        <div class="quick-btn-title">Buka Rute di Maps</div>
                        <div class="quick-btn-sub">Menuju kantor kami</div>
                    </div>
                </a>
            </div>

            <!-- MAP FULL WIDTH -->
            <div data-aos="fade-up">
                <div class="map-title">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span style="font-weight:700;color:var(--hijau-tua)">Lokasi Kantor Kami</span>
                </div>
                <div class="map-wrap">
                    <iframe
                        src="https://www.google.com/maps?q=<?= urlencode($alamat) ?>&output=embed"
                        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
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
                        Paket</div>
                    <a href="paket.php?kategori=reguler" class="footer-link">Paket Reguler</a>
                    <a href="paket.php?kategori=plus" class="footer-link">Paket Plus</a>
                    <a href="paket.php?kategori=vip" class="footer-link">Paket VIP</a>
                    <a href="paket.php?kategori=furoda" class="footer-link">Paket Furoda</a>
                </div>
                <div class="col-6 col-lg-2">
                    <div
                        style="color:var(--emas);font-weight:600;font-size:.85rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:1rem">
                        Menu</div>
                    <a href="../index.php" class="footer-link">Home</a>
                    <a href="tentang.php" class="footer-link">Tentang Kami</a>
                    <a href="kontak.php" class="footer-link">Kontak</a>
                </div>
                <div class="col-lg-4">
                    <div
                        style="color:var(--emas);font-weight:600;font-size:.85rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:1rem">
                        Kontak Cepat</div>
                    <a href="<?= waLink() ?>" target="_blank" class="footer-link"><i
                            class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($telepon) ?></a>
                    <a href="mailto:<?= htmlspecialchars($email) ?>" class="footer-link"><i
                            class="bi bi-envelope me-1"></i><?= htmlspecialchars($email) ?></a>
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
    <script>
        AOS.init({ once: true, offset: 60, duration: 300 });
    </script>
</body>

</html>