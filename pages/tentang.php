<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$settings = [];
foreach (db()->fetchAll("SELECT nama_key, nilai FROM pengaturan") as $p)
    $settings[$p['nama_key']] = $p['nilai'];

$izin_ppiu = $settings['izin_ppiu'] ?? '91201032614170001';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami – <?= htmlspecialchars($settings['nama_perusahaan'] ?? 'SAH Travel') ?></title>
    <meta name="description" content="Kenali lebih dekat PT. Sumatera Andalan Haramain, biro perjalanan umrah terpercaya dengan izin resmi PPIU.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <?php include __DIR__ . '/../includes/navbar-style.php'; ?>
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

        /* Navbar sekarang di includes/navbar-style.php */

        .page-hero {
            background: linear-gradient(135deg, #0D2B1A, #1B4D2E 50%, #1B6B3A);
            padding: 120px 0 60px;
            position: relative;
            overflow: hidden
        }

        @media (max-width: 767px) {
            .page-hero {
                padding: 100px 0 30px
            }
        }

        @media (max-width: 480px) {
            .page-hero {
                padding: 90px 0 20px
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

        .section-label {
            display: inline-block;
            color: var(--emas);
            font-weight: 600;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: .5rem
        }

        .section-title {
            font-family: var(--font-display);
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            font-weight: 700;
            color: var(--hijau-tua)
        }

        .section-desc {
            color: var(--teks-abu);
            max-width: 620px
        }

        .profil-section {
            padding: 70px 0;
            background: #fff
        }

        .profil-img {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(27, 77, 46, .15);
            position: relative
        }

        .profil-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block
        }

        .profil-badge {
            position: absolute;
            bottom: -20px;
            right: -20px;
            background: #fff;
            border-radius: 16px;
            padding: 1.2rem 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, .15);
            display: flex;
            align-items: center;
            gap: 12px
        }

        @media (max-width: 767px) {
            .profil-badge {
                position: static;
                margin-top: 1rem
            }
        }

        .profil-badge i {
            font-size: 2rem;
            color: var(--emas)
        }

        .profil-badge-num {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--hijau-tua);
            line-height: 1
        }

        .profil-badge-txt {
            font-size: .78rem;
            color: var(--teks-abu)
        }

        .legalitas-card {
            background: var(--krem);
            border: 1px solid rgba(27, 107, 58, .1);
            border-radius: 14px;
            padding: 1rem 1.3rem;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 1.2rem
        }

        .legalitas-card i {
            font-size: 1.6rem;
            color: var(--hijau)
        }

        .legalitas-label {
            font-size: .75rem;
            color: var(--teks-abu);
            text-transform: uppercase;
            letter-spacing: .5px
        }

        .legalitas-value {
            font-weight: 700;
            color: var(--hijau-tua);
            font-size: .95rem
        }

        .stats-section {
            background: linear-gradient(135deg, #0D2B1A, #1B4D2E 50%, #1B6B3A);
            padding: 55px 0
        }

        .stat-item {
            text-align: center;
            color: #fff
        }

        .stat-num {
            font-family: var(--font-display);
            font-size: clamp(2rem, 4vw, 2.8rem);
            font-weight: 700;
            color: var(--emas)
        }

        .stat-label {
            font-size: .85rem;
            color: rgba(255, 255, 255, .75)
        }

        .vm-section {
            padding: 70px 0;
            background: var(--krem)
        }

        .vm-card {
            background: #fff;
            border-radius: 18px;
            padding: 2rem;
            height: 100%;
            border: 1px solid rgba(27, 107, 58, .08);
            transition: all .3s
        }

        .vm-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 50px rgba(27, 77, 46, .1)
        }

        .vm-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--hijau), var(--hijau-muda));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 1.2rem
        }

        .vm-title {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--hijau-tua);
            margin-bottom: .6rem
        }

        .vm-text {
            color: var(--teks-abu);
            font-size: .9rem;
            line-height: 1.7
        }

        .kenapa-section {
            padding: 70px 0;
            background: #fff
        }

        .kenapa-item {
            display: flex;
            gap: 16px;
            margin-bottom: 1.8rem
        }

        .kenapa-icon {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--krem);
            color: var(--hijau);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem
        }

        .kenapa-title {
            font-weight: 700;
            color: var(--hijau-tua);
            font-size: 1rem;
            margin-bottom: .2rem
        }

        .kenapa-text {
            color: var(--teks-abu);
            font-size: .87rem;
            line-height: 1.6
        }

        .cta-tentang {
            background: var(--krem-tua);
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center
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

    <?php $navActive = ''; include __DIR__ . '/../includes/navbar.php'; ?>

    <section class="page-hero">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0" style="font-size:.83rem">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item active">Tentang Kami</li>
                </ol>
            </nav>
            <h1 class="page-hero-title">Tentang <span>SAH Umrah</span></h1>
            <p class="page-hero-sub">Melayani perjalanan ibadah dengan amanah, sejak <?= htmlspecialchars($settings['tahun_berdiri'] ?? '2016') ?></p>
        </div>
    </section>

    <!-- ===== PROFIL PERUSAHAAN ===== -->
    <section class="profil-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5" data-aos="fade-right">
                    <div class="profil-img">
                        <img src="https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?w=700&q=80"
                            alt="Jamaah Umrah SAH Travel">
                    </div>
                    <div class="profil-badge">
                        <i class="bi bi-patch-check-fill"></i>
                        <div>
                            <div class="profil-badge-num"><?= htmlspecialchars($settings['jumlah_jamaah'] ?? '5000') ?>+</div>
                            <div class="profil-badge-txt">Jamaah Terlayani</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7" data-aos="fade-left">
                    <div class="section-label">Profil Perusahaan</div>
                    <h2 class="section-title mb-3"><?= htmlspecialchars($settings['nama_perusahaan'] ?? 'PT. Sumatera Andalan Haramain') ?></h2>
                    <p class="section-desc" style="max-width:100%">
                        <?= htmlspecialchars($settings['nama_perusahaan'] ?? 'PT. Sumatera Andalan Haramain') ?>
                        (SAH Umrah) adalah biro perjalanan umrah resmi yang berbasis di Sumatera Utara. Kami hadir
                        untuk memudahkan langkah setiap muslim menuju Tanah Suci dengan pelayanan yang aman, nyaman,
                        dan sesuai syariat — mulai dari pengurusan dokumen, akomodasi, transportasi, hingga bimbingan
                        manasik yang dipandu pembimbing berpengalaman.
                    </p>
                    <p class="section-desc" style="max-width:100%">
                        <?= htmlspecialchars($settings['tagline'] ?? 'Perjalanan Suci, Pelayanan Terpercaya') ?>
                        adalah komitmen kami di setiap keberangkatan jamaah, besar maupun kecil rombongan.
                    </p>

                    <div class="legalitas-card">
                        <i class="bi bi-shield-check"></i>
                        <div>
                            <div class="legalitas-label">Izin PPIU (Penyelenggara Perjalanan Ibadah Umrah)</div>
                            <div class="legalitas-value"><?= htmlspecialchars($izin_ppiu) ?></div>
                        </div>
                    </div>
                    <?php if (!empty($settings['no_nib'])): ?>
                        <div class="legalitas-card">
                            <i class="bi bi-file-earmark-check"></i>
                            <div>
                                <div class="legalitas-label">Nomor Induk Berusaha (NIB)</div>
                                <div class="legalitas-value"><?= htmlspecialchars($settings['no_nib']) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== STATISTIK ===== -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-item" data-aos="fade-up">
                        <div class="stat-num"><?= htmlspecialchars($settings['tahun_berdiri'] ?? '2016') ?></div>
                        <div class="stat-label">Tahun Berdiri</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item" data-aos="fade-up" data-aos-delay="100">
                        <div class="stat-num"><?= htmlspecialchars($settings['jumlah_jamaah'] ?? '5000') ?>+</div>
                        <div class="stat-label">Jamaah Terlayani</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item" data-aos="fade-up" data-aos-delay="200">
                        <div class="stat-num">4</div>
                        <div class="stat-label">Jenis Paket Umrah</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item" data-aos="fade-up" data-aos-delay="300">
                        <div class="stat-num">100%</div>
                        <div class="stat-label">Berizin Resmi PPIU</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== VISI MISI ===== -->
    <section class="vm-section">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <div class="section-label">Visi &amp; Misi</div>
                <h2 class="section-title">Komitmen Kami untuk Jamaah</h2>
            </div>
            <div class="row g-4">
                <div class="col-lg-6" data-aos="fade-up">
                    <div class="vm-card">
                        <div class="vm-icon"><i class="bi bi-eye-fill"></i></div>
                        <div class="vm-title">Visi</div>
                        <p class="vm-text">Menjadi biro perjalanan umrah terpercaya di Sumatera yang mengutamakan
                            kenyamanan, keamanan, dan kemabruran ibadah setiap jamaah.</p>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="vm-card">
                        <div class="vm-icon"><i class="bi bi-flag-fill"></i></div>
                        <div class="vm-title">Misi</div>
                        <p class="vm-text">Memberikan pelayanan prima secara transparan, membimbing jamaah dengan
                            sepenuh hati, serta menjalin kerja sama dengan mitra terbaik di Tanah Suci agar setiap
                            perjalanan berjalan lancar dan berkesan.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== KENAPA MEMILIH KAMI ===== -->
    <section class="kenapa-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5" data-aos="fade-right">
                    <div class="section-label">Kenapa Memilih Kami</div>
                    <h2 class="section-title mb-3">Alasan Jamaah Mempercayai SAH Umrah</h2>
                    <p class="section-desc" style="max-width:100%">Kami berkomitmen menghadirkan pengalaman ibadah
                        umrah yang tenang dari awal keberangkatan hingga kembali ke tanah air.</p>
                </div>
                <div class="col-lg-7" data-aos="fade-left">
                    <div class="kenapa-item">
                        <div class="kenapa-icon"><i class="bi bi-patch-check"></i></div>
                        <div>
                            <div class="kenapa-title">Izin Resmi PPIU</div>
                            <div class="kenapa-text">Terdaftar dan berizin resmi sebagai Penyelenggara Perjalanan
                                Ibadah Umrah dari Kementerian Agama RI.</div>
                        </div>
                    </div>
                    <div class="kenapa-item">
                        <div class="kenapa-icon"><i class="bi bi-people"></i></div>
                        <div>
                            <div class="kenapa-title">Pembimbing Berpengalaman</div>
                            <div class="kenapa-text">Didampingi muthawwif dan pembimbing manasik yang berpengalaman
                                mendampingi ribuan jamaah.</div>
                        </div>
                    </div>
                    <div class="kenapa-item">
                        <div class="kenapa-icon"><i class="bi bi-building"></i></div>
                        <div>
                            <div class="kenapa-title">Akomodasi Terpilih</div>
                            <div class="kenapa-text">Bekerja sama dengan hotel dan maskapai terpercaya untuk
                                kenyamanan selama di Tanah Suci.</div>
                        </div>
                    </div>
                    <div class="kenapa-item">
                        <div class="kenapa-icon"><i class="bi bi-cash-coin"></i></div>
                        <div>
                            <div class="kenapa-title">Harga Transparan</div>
                            <div class="kenapa-text">Tidak ada biaya tersembunyi, dengan pilihan cicilan yang
                                fleksibel sesuai kemampuan jamaah.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CTA ===== -->
    <section style="padding:0 0 80px;background:#fff">
        <div class="container">
            <div class="cta-tentang" data-aos="fade-up">
                <h3 class="section-title mb-2">Siap Memulai Perjalanan Suci Anda?</h3>
                <p class="section-desc mx-auto mb-4">Konsultasikan kebutuhan umrah Anda bersama tim kami, gratis
                    tanpa biaya.</p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="paket.php" class="btn btn-success rounded-pill px-4"><i class="bi bi-bag-check me-2"></i>Lihat
                        Paket Umrah</a>
                    <a href="<?= waLink('Assalamu\'alaikum, saya ingin tahu lebih lanjut tentang SAH Umrah') ?>"
                        target="_blank" class="btn btn-outline-success rounded-pill px-4"><i
                            class="bi bi-whatsapp me-2"></i>Hubungi Kami</a>
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
                        Legalitas</div>
                    <p style="font-size:.83rem;line-height:1.6">Izin PPIU: <?= htmlspecialchars($izin_ppiu) ?></p>
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