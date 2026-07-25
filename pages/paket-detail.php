<?php
require_once __DIR__ . '/../config/config.php';

$slug = sanitize($_GET['slug'] ?? '');
if (!$slug)
    redirect(BASE_URL . '/pages/paket.php');

$paket = db()->fetchOne("SELECT * FROM paket_umrah WHERE slug = ? AND status != 'nonaktif'", 's', [$slug]);
if (!$paket) {
    setFlash('error', 'Paket tidak ditemukan.');
    redirect(BASE_URL . '/pages/paket.php');
}

// Ambil jadwal aktif
$jadwals = db()->fetchAll("SELECT * FROM jadwal WHERE paket_id = ? AND status = 'aktif' AND tanggal_berangkat >= CURDATE() ORDER BY tanggal_berangkat ASC", 'i', [$paket['id']]);

// Paket lain (related)
$related = db()->fetchAll("SELECT * FROM paket_umrah WHERE kategori = ? AND id != ? AND status='aktif' LIMIT 3", 'si', [$paket['kategori'], $paket['id']]);

$fasilitas = json_decode($paket['fasilitas'] ?? '[]', true);
$include = $paket['include'] ?? '';
$exclude = $paket['exclude'] ?? '';
$galeri = json_decode($paket['galeri'] ?? '[]', true);

$settings = [];
foreach (db()->fetchAll("SELECT nama_key, nilai FROM pengaturan") as $p)
    $settings[$p['nama_key']] = $p['nilai'];

// Update views (via artikel, skip paket)
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($paket['meta_title'] ?? $paket['nama_paket']) ?> – SAH Travel
    </title>
    <meta name="description"
        content="<?= htmlspecialchars($paket['meta_desc'] ?? truncate($paket['deskripsi'] ?? '', 160)) ?>">
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
            background: rgba(27, 77, 46, .97);
            backdrop-filter: blur(10px);
            padding: .8rem 0;
            box-shadow: 0 4px 30px rgba(0, 0, 0, .2)
        }

        .navbar-brand {
            font-family: var(--font-display);
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff !important
        }

        .navbar-brand span {
            color: var(--emas)
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
            font-size: .85rem
        }

        /* HERO */
        .detail-hero {
            background: linear-gradient(135deg, #0D2B1A, #1B4D2E 50%, #1B6B3A);
            padding: 100px 0 50px;
            position: relative;
            overflow: hidden
        }

        .detail-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: .05;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23C9A84C' fill-opacity='1'%3E%3Cpath d='M30 0L39 20.5H60L42.5 33.2L49.5 53.5L30 40.5L10.5 53.5L17.5 33.2L0 20.5H21L30 0Z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")
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

        /* STICKY BOOKING CARD */
        .booking-card {
            background: #fff;
            border-radius: 18px;
            padding: 1.8rem;
            box-shadow: 0 10px 40px rgba(27, 77, 46, .12);
            border: 1px solid rgba(27, 107, 58, .1);
            position: sticky;
            top: 90px
        }

        .price-big {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 700;
            color: var(--hijau)
        }

        .price-old {
            font-size: .9rem;
            color: #aaa;
            text-decoration: line-through
        }

        .btn-book {
            background: var(--hijau);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: .85rem 2rem;
            font-size: 1rem;
            font-weight: 700;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all .3s;
            text-decoration: none;
            cursor: pointer
        }

        .btn-book:hover {
            background: var(--hijau-tua);
            color: #fff;
            transform: translateY(-2px)
        }

        .btn-wa-book {
            background: #25D366;
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: .7rem 1.5rem;
            font-size: .9rem;
            font-weight: 600;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all .3s;
            text-decoration: none
        }

        .btn-wa-book:hover {
            background: #22c35e;
            color: #fff
        }

        /* TABS */
        .nav-tabs {
            border: none;
            gap: .5rem
        }

        .nav-tabs .nav-link {
            border: 1.5px solid rgba(27, 107, 58, .15) !important;
            border-radius: 50px !important;
            color: var(--teks-abu);
            font-size: .85rem;
            padding: .45rem 1.2rem;
            font-weight: 500
        }

        .nav-tabs .nav-link.active {
            background: var(--hijau) !important;
            color: #fff !important;
            border-color: var(--hijau) !important
        }

        /* INFO ITEMS */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .7rem 0;
            border-bottom: 1px solid rgba(27, 107, 58, .08)
        }

        .info-row:last-child {
            border-bottom: none
        }

        .info-label {
            font-size: .83rem;
            color: var(--teks-abu)
        }

        .info-val {
            font-size: .88rem;
            font-weight: 600;
            color: var(--teks-gelap)
        }

        /* INCLUDE/EXCLUDE */
        .list-include li,
        .list-exclude li {
            padding: .4rem 0;
            font-size: .88rem;
            display: flex;
            align-items: flex-start;
            gap: .6rem
        }

        .list-include li::before {
            content: '✓';
            color: var(--hijau);
            font-weight: 700;
            flex-shrink: 0
        }

        .list-exclude li::before {
            content: '✗';
            color: #dc3545;
            font-weight: 700;
            flex-shrink: 0
        }

        /* JADWAL TABLE */
        .jadwal-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border: 1px solid rgba(27, 107, 58, .12);
            border-radius: 12px;
            margin-bottom: .7rem;
            transition: all .3s;
            cursor: pointer
        }

        .jadwal-row:hover {
            border-color: var(--hijau);
            background: var(--krem)
        }

        .jadwal-row.selected {
            border-color: var(--hijau);
            background: rgba(27, 107, 58, .06);
            box-shadow: 0 0 0 2px rgba(27, 107, 58, .2)
        }

        /* GALERI */
        .galeri-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px
        }

        .galeri-item {
            border-radius: 10px;
            overflow: hidden;
            aspect-ratio: 1;
            cursor: pointer
        }

        .galeri-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .4s
        }

        .galeri-item:hover img {
            transform: scale(1.08)
        }

        /* KUOTA */
        .kuota-bar-lg {
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
            margin: .4rem 0
        }

        .kuota-fill-lg {
            height: 100%;
            background: linear-gradient(90deg, var(--hijau), var(--emas));
            border-radius: 4px
        }

        /* RELATED */
        .related-card {
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(27, 107, 58, .1);
            transition: all .3s;
            text-decoration: none;
            color: inherit;
            display: block
        }

        .related-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(27, 77, 46, .12)
        }

        .related-img {
            height: 140px;
            background: linear-gradient(135deg, var(--hijau-tua), var(--hijau-muda));
            overflow: hidden
        }

        .related-img img {
            width: 100%;
            height: 100%;
            object-fit: cover
        }

        /* WA FLOAT */
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
            text-decoration: none;
            animation: pulse-wa 2s infinite
        }

        @keyframes pulse-wa {

            0%,
            100% {
                box-shadow: 0 8px 25px rgba(37, 211, 102, .45)
            }

            50% {
                box-shadow: 0 8px 35px rgba(37, 211, 102, .7)
            }
        }

        footer {
            background: var(--hijau-tua);
            padding: 40px 0 20px;
            color: rgba(255, 255, 255, .7)
        }

        .footer-bottom {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, .1);
            font-size: .8rem;
            color: rgba(255, 255, 255, .4);
            text-align: center
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><i class="bi bi-moon-stars-fill me-2"
                    style="color:var(--emas)"></i>SAH <span>Travel</span></a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"><i
                    class="bi bi-list text-white fs-4"></i></button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="paket.php">Paket Umrah</a></li>
                    <li class="nav-item"><a class="nav-link" href="tentang.php">Tentang Kami</a></li>
                    <li class="nav-item"><a class="nav-link" href="kontak.php">Kontak</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item ms-2"><a class="nav-link btn-navbar" href="../user/dashboard.php"><i
                                    class="bi bi-person-circle me-1"></i>
                                <?= htmlspecialchars($_SESSION['user_name']) ?>
                            </a></li>
                    <?php else: ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="detail-hero">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0" style="font-size:.83rem">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="paket.php">Paket Umrah</a></li>
                    <li class="breadcrumb-item active">
                        <?= htmlspecialchars($paket['nama_paket']) ?>
                    </li>
                </ol>
            </nav>
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark mb-2">
                        <?= ucfirst($paket['kategori']) ?>
                    </span>
                    <h1
                        style="font-family:var(--font-display);font-size:clamp(1.8rem,4vw,2.6rem);font-weight:700;color:#fff;margin-bottom:.8rem">
                        <?= htmlspecialchars($paket['nama_paket']) ?>
                    </h1>
                    <div class="d-flex flex-wrap gap-3" style="color:rgba(255,255,255,.8);font-size:.88rem">
                        <span><i class="bi bi-calendar3 me-1" style="color:var(--emas)"></i>
                            <?= $paket['durasi'] ?> Hari
                        </span>
                        <span><i class="bi bi-airplane me-1" style="color:var(--emas)"></i>
                            <?= htmlspecialchars($paket['maskapai']) ?>
                        </span>
                        <span><i class="bi bi-building me-1" style="color:var(--emas)"></i>
                            <?= htmlspecialchars($paket['hotel_mekkah']) ?>
                        </span>
                        <span><i class="bi bi-people me-1" style="color:var(--emas)"></i>
                            <?= $paket['sisa_kuota'] ?> kursi tersisa
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MAIN -->
    <section style="padding:50px 0 80px;background:var(--krem)">
        <div class="container">
            <div class="row g-4">
                <!-- LEFT: DETAIL -->
                <div class="col-lg-8">

                    <!-- BANNER -->
                    <?php if (!empty($paket['banner'])): ?>
                        <div
                            style="border-radius:18px;overflow:hidden;margin-bottom:1.5rem;box-shadow:0 10px 30px rgba(27,77,46,.1)">
                            <img src="<?= UPLOAD_URL . $paket['banner'] ?>"
                                alt="<?= htmlspecialchars($paket['nama_paket']) ?>"
                                style="width:100%;height:350px;object-fit:cover">
                        </div>
                    <?php endif; ?>

                    <!-- TABS -->
                    <div style="background:#fff;border-radius:18px;padding:2rem;box-shadow:0 4px 20px rgba(27,77,46,.07);border:1px solid rgba(27,107,58,.08)"
                        data-aos="fade-up">
                        <ul class="nav nav-tabs mb-4" id="detailTab">
                            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab"
                                    data-bs-target="#tab-info">Info Paket</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#tab-fasilitas">Fasilitas</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#tab-jadwal">Jadwal</button></li>
                            <?php if (!empty($galeri)): ?>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                                        data-bs-target="#tab-galeri">Galeri</button></li>
                            <?php endif; ?>
                        </ul>

                        <div class="tab-content">
                            <!-- INFO -->
                            <div class="tab-pane fade show active" id="tab-info">
                                <?php if ($paket['deskripsi']): ?>
                                    <p style="color:var(--teks-abu);line-height:1.8;margin-bottom:1.5rem">
                                        <?= nl2br(htmlspecialchars($paket['deskripsi'])) ?>
                                    </p>
                                <?php endif; ?>
                                <div class="row g-0">
                                    <div class="col-md-6">
                                        <div class="info-row"><span class="info-label"><i class="bi bi-calendar3 me-2"
                                                    style="color:var(--emas)"></i>Durasi</span><span class="info-val">
                                                <?= $paket['durasi'] ?> Hari
                                            </span></div>
                                        <div class="info-row"><span class="info-label"><i class="bi bi-airplane me-2"
                                                    style="color:var(--emas)"></i>Maskapai</span><span class="info-val">
                                                <?= htmlspecialchars($paket['maskapai']) ?>
                                            </span></div>
                                        <div class="info-row"><span class="info-label"><i class="bi bi-building me-2"
                                                    style="color:var(--emas)"></i>Hotel Mekkah</span><span
                                                class="info-val">
                                                <?= htmlspecialchars($paket['hotel_mekkah']) ?>
                                                <?= str_repeat('★', $paket['bintang_mekkah']) ?>
                                            </span></div>
                                        <div class="info-row"><span class="info-label"><i class="bi bi-geo-alt me-2"
                                                    style="color:var(--emas)"></i>Hotel Madinah</span><span
                                                class="info-val">
                                                <?= htmlspecialchars($paket['hotel_madinah']) ?>
                                                <?= str_repeat('★', $paket['bintang_madinah']) ?>
                                            </span></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-row"><span class="info-label"><i class="bi bi-people me-2"
                                                    style="color:var(--emas)"></i>Kuota Total</span><span
                                                class="info-val">
                                                <?= $paket['kuota'] ?> Orang
                                            </span></div>
                                        <div class="info-row"><span class="info-label"><i
                                                    class="bi bi-check-circle me-2" style="color:var(--emas)"></i>Sisa
                                                Kuota</span><span
                                                class="info-val text-<?= $paket['sisa_kuota'] < 10 ? 'danger' : 'success' ?>">
                                                <?= $paket['sisa_kuota'] ?> Orang
                                            </span></div>
                                        <div class="info-row"><span class="info-label"><i class="bi bi-tag me-2"
                                                    style="color:var(--emas)"></i>Kategori</span><span class="info-val">
                                                <?= ucfirst($paket['kategori']) ?>
                                            </span></div>
                                        <div class="info-row"><span class="info-label"><i
                                                    class="bi bi-shield-check me-2"
                                                    style="color:var(--emas)"></i>Status</span><span class="info-val">
                                                <?= statusBadge($paket['status']) ?>
                                            </span></div>
                                    </div>
                                </div>
                                <!-- Kuota bar -->
                                <?php $persen = $paket['kuota'] > 0 ? round((($paket['kuota'] - $paket['sisa_kuota']) / $paket['kuota']) * 100) : 0; ?>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between mb-1" style="font-size:.82rem">
                                        <span style="color:var(--teks-abu)">Pengisian Kuota</span>
                                        <span style="color:var(--hijau);font-weight:600">
                                            <?= $persen ?>% terisi
                                        </span>
                                    </div>
                                    <div class="kuota-bar-lg">
                                        <div class="kuota-fill-lg" style="width:<?= $persen ?>%"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- FASILITAS -->
                            <div class="tab-pane fade" id="tab-fasilitas">
                                <div class="row">
                                    <?php if (!empty($fasilitas)): ?>
                                        <div class="col-md-6 mb-3">
                                            <h6 style="color:var(--hijau-tua);font-weight:700;margin-bottom:1rem"><i
                                                    class="bi bi-star-fill me-2" style="color:var(--emas)"></i>Fasilitas
                                                Paket</h6>
                                            <ul class="list-unstyled list-include">
                                                <?php foreach ($fasilitas as $f): ?>
                                                    <li>
                                                        <?= htmlspecialchars($f) ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($include): ?>
                                        <div class="col-md-6 mb-3">
                                            <h6 style="color:var(--hijau-tua);font-weight:700;margin-bottom:1rem"><i
                                                    class="bi bi-check2-circle me-2 text-success"></i>Sudah Termasuk</h6>
                                            <ul class="list-unstyled list-include">
                                                <?php foreach (explode("\n", trim($include)) as $item):
                                                    if (trim($item)): ?>
                                                        <li>
                                                            <?= htmlspecialchars(trim($item)) ?>
                                                        </li>
                                                    <?php endif; endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($exclude): ?>
                                        <div class="col-md-6 mb-3">
                                            <h6 style="color:var(--hijau-tua);font-weight:700;margin-bottom:1rem"><i
                                                    class="bi bi-x-circle me-2 text-danger"></i>Tidak Termasuk</h6>
                                            <ul class="list-unstyled list-exclude">
                                                <?php foreach (explode("\n", trim($exclude)) as $item):
                                                    if (trim($item)): ?>
                                                        <li>
                                                            <?= htmlspecialchars(trim($item)) ?>
                                                        </li>
                                                    <?php endif; endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- JADWAL -->
                            <div class="tab-pane fade" id="tab-jadwal">
                                <?php if (!empty($jadwals)): ?>
                                    <?php foreach ($jadwals as $jd):
                                        $sisaJ = $jd['kuota'] - $jd['terisi'];
                                        $persenJ = $jd['kuota'] > 0 ? round(($jd['terisi'] / $jd['kuota']) * 100) : 0;
                                        ?>
                                        <div class="jadwal-row"
                                            onclick="pilihJadwal(<?= $jd['id'] ?>,'<?= tglIndo($jd['tanggal_berangkat']) ?>','<?= tglIndo($jd['tanggal_pulang']) ?>')"
                                            id="jadwal-<?= $jd['id'] ?>">
                                            <div>
                                                <div style="font-weight:700;color:var(--hijau-tua);margin-bottom:.2rem"><i
                                                        class="bi bi-calendar-event me-2" style="color:var(--emas)"></i>
                                                    <?= tglIndo($jd['tanggal_berangkat']) ?>
                                                </div>
                                                <div style="font-size:.82rem;color:var(--teks-abu)">Kembali:
                                                    <?= tglIndo($jd['tanggal_pulang']) ?> &nbsp;|&nbsp;
                                                    <?= $paket['durasi'] ?> Hari
                                                </div>
                                                <?php if ($jd['keterangan']): ?>
                                                    <div style="font-size:.78rem;color:var(--hijau);margin-top:.2rem">
                                                        <?= htmlspecialchars($jd['keterangan']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-end">
                                                <div
                                                    style="font-size:.78rem;color:<?= $sisaJ < 5 ? '#dc3545' : ($sisaJ < 15 ? '#f59e0b' : 'var(--hijau)') ?>;font-weight:600">
                                                    <?= $sisaJ ?> kursi tersisa
                                                </div>
                                                <div style="font-size:.72rem;color:var(--teks-abu);margin-top:.2rem">dari
                                                    <?= $jd['kuota'] ?> kuota
                                                </div>
                                                <?php if ($jd['harga_khusus']): ?>
                                                    <div
                                                        style="font-size:.78rem;color:var(--emas);font-weight:600;margin-top:.2rem">
                                                        Harga khusus: Rp
                                                        <?= number_format($jd['harga_khusus'], 0, ',', '.') ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($sisaJ === 0): ?><span class="badge bg-danger">Penuh</span>
                                                <?php else: ?><span class="badge"
                                                        style="background:var(--hijau);font-size:.7rem">Pilih</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-calendar-x"
                                            style="font-size:2.5rem;color:#ccc;display:block;margin-bottom:.8rem"></i>
                                        <p style="color:var(--teks-abu)">Jadwal belum tersedia. Hubungi kami untuk informasi
                                            lebih lanjut.</p>
                                        <a href="<?= waLink('Halo, saya ingin bertanya jadwal keberangkatan ' . $paket['nama_paket']) ?>"
                                            target="_blank" class="btn btn-success rounded-pill px-4">
                                            <i class="bi bi-whatsapp me-2"></i>Tanya Jadwal via WA
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- GALERI -->
                            <?php if (!empty($galeri)): ?>
                                <div class="tab-pane fade" id="tab-galeri">
                                    <div class="galeri-grid">
                                        <?php foreach ($galeri as $gi => $g): ?>
                                            <div class="galeri-item" onclick="openLightbox('<?= UPLOAD_URL . $g ?>')">
                                                <img src="<?= UPLOAD_URL . $g ?>" alt="Galeri <?= $gi + 1 ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- RELATED PAKET -->
                    <?php if (!empty($related)): ?>
                        <div class="mt-4" data-aos="fade-up">
                            <h5 style="font-family:var(--font-display);color:var(--hijau-tua);margin-bottom:1.2rem">Paket
                                Serupa Lainnya</h5>
                            <div class="row g-3">
                                <?php foreach ($related as $r): ?>
                                    <div class="col-md-4">
                                        <a href="paket-detail.php?slug=<?= $r['slug'] ?>" class="related-card">
                                            <div class="related-img">
                                                <?php if ($r['banner']): ?><img src="<?= UPLOAD_URL . $r['banner'] ?>"
                                                        alt="<?= htmlspecialchars($r['nama_paket']) ?>">
                                                <?php else: ?>
                                                    <div
                                                        style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                                                        <i class="bi bi-building text-white opacity-25" style="font-size:2rem"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div style="padding:1rem">
                                                <div
                                                    style="font-weight:700;font-size:.88rem;color:var(--hijau-tua);margin-bottom:.3rem">
                                                    <?= htmlspecialchars($r['nama_paket']) ?>
                                                </div>
                                                <div style="font-size:.8rem;color:var(--teks-abu)">
                                                    <?= $r['durasi'] ?> Hari |
                                                    <?= htmlspecialchars($r['maskapai']) ?>
                                                </div>
                                                <div
                                                    style="font-family:var(--font-display);font-size:1rem;font-weight:700;color:var(--hijau);margin-top:.4rem">
                                                    Rp
                                                    <?= number_format($r['harga'], 0, ',', '.') ?>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- RIGHT: BOOKING CARD -->
                <div class="col-lg-4">
                    <div class="booking-card" data-aos="fade-left">
                        <?php if ($paket['harga_coret']): ?>
                            <div class="price-old">Rp
                                <?= number_format($paket['harga_coret'], 0, ',', '.') ?>
                            </div>
                        <?php endif; ?>
                        <div class="price-big">Rp
                            <?= number_format($paket['harga'], 0, ',', '.') ?>
                        </div>
                        <div style="font-size:.8rem;color:var(--teks-abu);margin-bottom:1.2rem">per orang |
                            <?= $paket['durasi'] ?> hari
                        </div>

                        <!-- Jadwal terpilih -->
                        <div id="jadwal-selected"
                            style="background:var(--krem);border-radius:10px;padding:.8rem 1rem;margin-bottom:1rem;font-size:.83rem;display:none">
                            <div style="color:var(--teks-abu);font-size:.75rem;margin-bottom:.2rem">Jadwal Dipilih:
                            </div>
                            <div id="jadwal-selected-text" style="font-weight:600;color:var(--hijau-tua)"></div>
                        </div>

                        <!-- Jumlah jamaah -->
                        <div class="mb-3">
                            <label
                                style="font-size:.83rem;font-weight:600;color:var(--teks-gelap);margin-bottom:.4rem">Jumlah
                                Jamaah</label>
                            <div class="d-flex align-items-center gap-2">
                                <button onclick="changeQty(-1)" class="btn btn-outline-secondary rounded-circle"
                                    style="width:34px;height:34px;padding:0;font-size:1.1rem">−</button>
                                <input type="number" id="qty" value="1" min="1" max="<?= $paket['sisa_kuota'] ?>"
                                    class="form-control text-center" style="width:60px" onchange="updateTotal()">
                                <button onclick="changeQty(1)" class="btn btn-outline-secondary rounded-circle"
                                    style="width:34px;height:34px;padding:0;font-size:1.1rem">+</button>
                            </div>
                        </div>

                        <!-- Total -->
                        <div
                            style="background:linear-gradient(135deg,var(--hijau-tua),var(--hijau));color:#fff;border-radius:12px;padding:1rem;margin-bottom:1.2rem">
                            <div style="font-size:.78rem;opacity:.8;margin-bottom:.2rem">Total Estimasi</div>
                            <div id="total-harga"
                                style="font-family:var(--font-display);font-size:1.4rem;font-weight:700">Rp
                                <?= number_format($paket['harga'], 0, ',', '.') ?>
                            </div>
                            <div style="font-size:.72rem;opacity:.7;margin-top:.2rem">DP min. Rp
                                <?= number_format($paket['harga'] * 0.3, 0, ',', '.') ?>
                            </div>
                        </div>

                        <?php if ($paket['status'] === 'aktif' && $paket['sisa_kuota'] > 0): ?>
                            <a href="booking.php?paket=<?= $paket['id'] ?>" class="btn-book mb-2" id="btnBook">
                                <i class="bi bi-calendar-check-fill"></i>Daftar Sekarang
                            </a>
                        <?php else: ?>
                            <button class="btn-book mb-2" disabled style="background:#aaa;cursor:not-allowed">
                                <i class="bi bi-x-circle"></i>Kuota Habis
                            </button>
                        <?php endif; ?>
                        <a href="<?= waLink('Assalamualaikum, saya tertarik daftar paket: ' . $paket['nama_paket'] . '. Boleh minta informasi lebih lanjut?') ?>"
                            target="_blank" class="btn-wa-book">
                            <i class="bi bi-whatsapp fs-5"></i>Tanya via WhatsApp
                        </a>

                        <!-- Info singkat -->
                        <div style="margin-top:1.2rem;padding-top:1rem;border-top:1px solid rgba(27,107,58,.1)">
                            <div class="d-flex align-items-center gap-2 mb-2"
                                style="font-size:.8rem;color:var(--teks-abu)"><i
                                    class="bi bi-shield-check-fill text-success"></i> Terdaftar resmi di Kemenag RI
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2"
                                style="font-size:.8rem;color:var(--teks-abu)"><i
                                    class="bi bi-lock-fill text-success"></i> Pembayaran aman & terpercaya</div>
                            <div class="d-flex align-items-center gap-2" style="font-size:.8rem;color:var(--teks-abu)">
                                <i class="bi bi-headset text-success"></i> Dukungan 24 jam selama perjalanan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LIGHTBOX -->
    <div id="lightbox"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:9999;align-items:center;justify-content:center"
        onclick="closeLightbox()">
        <img id="lightbox-img" style="max-width:90%;max-height:90vh;border-radius:10px;object-fit:contain">
        <button onclick="closeLightbox()"
            style="position:absolute;top:20px;right:20px;background:rgba(255,255,255,.2);border:none;color:#fff;font-size:1.5rem;width:42px;height:42px;border-radius:50%;cursor:pointer">×</button>
    </div>

    <footer>
        <div class="container">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div style="font-family:var(--font-display);font-size:1.2rem;font-weight:700;color:#fff">SAH <span
                            style="color:var(--emas)">Travel</span></div>
                    <p style="font-size:.83rem;margin-top:.3rem">
                        <?= htmlspecialchars($settings['tagline'] ?? 'Perjalanan Suci, Pelayanan Terpercaya') ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="paket.php"
                        style="color:rgba(255,255,255,.65);text-decoration:none;font-size:.85rem;margin-right:1.2rem">Semua
                        Paket</a>
                    <a href="kontak.php"
                        style="color:rgba(255,255,255,.65);text-decoration:none;font-size:.85rem">Kontak</a>
                </div>
            </div>
            <div class="footer-bottom">©
                <?= date('Y') ?>
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
        const HARGA = <?= $paket['harga'] ?>;
        let selectedJadwal = null;

        function changeQty(d) {
            const i = document.getElementById('qty');
            const max = <?= $paket['sisa_kuota'] ?>;
            let v = parseInt(i.value) + d;
            if (v < 1) v = 1; if (v > max) v = max;
            i.value = v; updateTotal();
        }
        function updateTotal() {
            const qty = parseInt(document.getElementById('qty').value) || 1;
            const total = HARGA * qty;
            document.getElementById('total-harga').textContent = 'Rp ' + total.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            // Update booking link
            const btn = document.getElementById('btnBook');
            if (btn) btn.href = `booking.php?paket=<?= $paket['id'] ?>&qty=${qty}${selectedJadwal ? '&jadwal=' + selectedJadwal : ''}`;
        }
        function pilihJadwal(id, tglB, tglP) {
            document.querySelectorAll('.jadwal-row').forEach(r => r.classList.remove('selected'));
            document.getElementById('jadwal-' + id).classList.add('selected');
            selectedJadwal = id;
            document.getElementById('jadwal-selected').style.display = 'block';
            document.getElementById('jadwal-selected-text').textContent = tglB + ' s/d ' + tglP;
            updateTotal();
        }
        function openLightbox(src) {
            document.getElementById('lightbox').style.display = 'flex';
            document.getElementById('lightbox-img').src = src;
        }
        function closeLightbox() { document.getElementById('lightbox').style.display = 'none'; }
        updateTotal();
    </script>
</body>

</html>