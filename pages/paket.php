<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Filter & Search
$kategori = clean($_GET['kategori'] ?? '');
$search = clean($_GET['q'] ?? '');
$sort = clean($_GET['sort'] ?? 'urutan');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;

// Build query
$where = ["status = 'aktif'"];
$params = [];
$types = '';

if ($kategori) {
    $where[] = "kategori = ?";
    $params[] = $kategori;
    $types .= 's';
}
if ($search) {
    $where[] = "(nama_paket LIKE ? OR deskripsi LIKE ? OR maskapai LIKE ?)";
    $s = "%$search%";
    $params[] = $s;
    $params[] = $s;
    $params[] = $s;
    $types .= 'sss';
}

$whereStr = implode(' AND ', $where);
$orderMap = ['harga_asc' => 'harga ASC', 'harga_desc' => 'harga DESC', 'durasi' => 'durasi ASC', 'urutan' => 'urutan ASC, id DESC'];
$order = $orderMap[$sort] ?? 'urutan ASC';

$total = (int) db()->fetchOne("SELECT COUNT(*) as c FROM paket_umrah WHERE $whereStr", $types, $params)['c'];
$offset = ($page - 1) * $perPage;
$pakets = db()->fetchAll("SELECT * FROM paket_umrah WHERE $whereStr ORDER BY $order LIMIT ? OFFSET ?", $types . 'ii', array_merge($params, [$perPage, $offset]));

$settings = [];
foreach (db()->fetchAll("SELECT nama_key, nilai FROM pengaturan") as $p)
    $settings[$p['nama_key']] = $p['nilai'];

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paket Umrah – <?= htmlspecialchars($settings['nama_perusahaan'] ?? 'SAH Travel') ?></title>
    <meta name="description" content="Temukan paket umrah terbaik dengan harga terjangkau dan fasilitas premium.">
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
            font-size: .85rem;
            transition: all .3s
        }

        .btn-navbar:hover {
            background: var(--emas-muda);
            transform: translateY(-1px)
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

        @media (max-width: 480px) {
            .page-hero {
                padding: 20px 0 20px
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

        .filter-bar {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(27, 77, 46, .08);
            border: 1px solid rgba(27, 107, 58, .08)
        }

        .filter-btn {
            border: 1.5px solid rgba(27, 107, 58, .2);
            background: #fff;
            color: var(--teks-gelap);
            border-radius: 50px;
            padding: .45rem 1.2rem;
            font-size: .83rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .3s;
            text-decoration: none;
            display: inline-block
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--hijau);
            color: #fff;
            border-color: var(--hijau)
        }

        .search-input {
            border: 1.5px solid rgba(27, 107, 58, .2);
            border-radius: 50px;
            padding: .5rem 1.2rem .5rem 2.8rem;
            font-size: .88rem;
            width: 100%;
            outline: none;
            transition: border .3s
        }

        .search-input:focus {
            border-color: var(--hijau)
        }

        .search-wrap {
            position: relative
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--teks-abu)
        }

        .sort-select {
            border: 1.5px solid rgba(27, 107, 58, .2);
            border-radius: 50px;
            padding: .45rem 1rem;
            font-size: .83rem;
            outline: none;
            cursor: pointer
        }

        .paket-card {
            border-radius: 18px;
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(27, 107, 58, .1);
            transition: all .35s;
            height: 100%
        }

        .paket-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 60px rgba(27, 77, 46, .15);
            border-color: var(--hijau)
        }

        .paket-img {
            height: 210px;
            background: linear-gradient(135deg, var(--hijau-tua), var(--hijau-muda));
            position: relative;
            overflow: hidden
        }

        .paket-img img {
            width: 100%;
            height: 100%;
            object-fit: cover
        }

        .paket-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            padding: .3rem .85rem;
            border-radius: 50px;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase
        }

        .badge-reguler {
            background: rgba(27, 107, 58, .9);
            color: #fff
        }

        .badge-plus {
            background: rgba(201, 168, 76, .9);
            color: #1A1A1A
        }

        .badge-vip {
            background: rgba(139, 0, 0, .85);
            color: #fff
        }

        .badge-promo {
            background: rgba(220, 53, 69, .9);
            color: #fff
        }

        .badge-furoda {
            background: rgba(106, 90, 205, .9);
            color: #fff
        }

        .badge-habis {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, .55);
            display: flex;
            align-items: center;
            justify-content: center
        }

        .paket-body {
            padding: 1.5rem
        }

        .paket-name {
            font-family: var(--font-display);
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--hijau-tua);
            margin-bottom: .8rem
        }

        .paket-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .82rem;
            color: var(--teks-abu);
            margin-bottom: .35rem
        }

        .paket-info-item i {
            color: var(--hijau);
            width: 16px
        }

        .kuota-bar {
            height: 4px;
            background: #eee;
            border-radius: 2px;
            margin-bottom: .4rem;
            overflow: hidden
        }

        .kuota-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--hijau), var(--emas));
            border-radius: 2px
        }

        .paket-price {
            font-family: var(--font-display);
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--hijau)
        }

        .paket-price span {
            font-size: .78rem;
            font-weight: 400;
            color: var(--teks-abu)
        }

        .paket-price-old {
            font-size: .8rem;
            color: #aaa;
            text-decoration: line-through
        }

        .btn-paket {
            background: var(--hijau);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: .55rem 1.3rem;
            font-size: .83rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all .3s
        }

        .btn-paket:hover {
            background: var(--hijau-tua);
            color: #fff
        }

        .btn-paket-outline {
            background: transparent;
            color: var(--hijau);
            border: 1.5px solid var(--hijau);
            border-radius: 50px;
            padding: .55rem 1.3rem;
            font-size: .83rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all .3s
        }

        .btn-paket-outline:hover {
            background: var(--hijau);
            color: #fff
        }

        .tag-fasilitas {
            display: inline-block;
            background: var(--krem);
            color: var(--hijau-tua);
            border-radius: 50px;
            padding: .2rem .7rem;
            font-size: .72rem;
            font-weight: 500;
            margin: .15rem
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center
        }

        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem
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
            <a class="navbar-brand" href="../index.php"><img
                src="<?= BASE_URL ?>/assets/img/logo-sah.png" alt="Logo SAH Umrah" class="navbar-logo me-2">SAH
            <span>Umrah</span></a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <i class="bi bi-list text-white fs-4"></i>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="paket.php">Paket Umrah</a></li>
                    <li class="nav-item"><a class="nav-link" href="tentang.php">Tentang Kami</a></li>
                    <li class="nav-item"><a class="nav-link" href="kontak.php">Kontak</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="page-hero">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0" style="font-size:.83rem">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item active">Paket Umrah</li>
                </ol>
            </nav>
            <h1 class="page-hero-title">Paket <span>Umrah</span> Terbaik Kami</h1>
            <p class="page-hero-sub">Temukan paket umrah yang sesuai dengan kebutuhan dan anggaran Anda</p>
        </div>
    </section>

    <section style="padding:50px 0 80px; background:var(--krem)">
        <div class="container">

            <!-- FILTER BAR -->
            <div class="filter-bar mb-4" data-aos="fade-up">
                <form method="GET" action="">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-4">
                            <div class="search-wrap">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                                    class="search-input" placeholder="Cari paket umrah...">
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="d-flex flex-wrap gap-2">
                                <a href="paket.php<?= $search ? '?q=' . $search : '' ?>"
                                    class="filter-btn <?= !$kategori ? 'active' : '' ?>">Semua</a>
                                <?php foreach (['reguler', 'plus', 'vip', 'furoda', 'promo'] as $kat): ?>
                                    <a href="paket.php?kategori=<?= $kat ?><?= $search ? '&q=' . $search : '' ?>"
                                        class="filter-btn <?= $kategori === $kat ? 'active' : '' ?>">
                                        <?= ucfirst($kat) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-lg-3 d-flex gap-2 justify-content-lg-end">
                            <select name="sort" class="sort-select" onchange="this.form.submit()">
                                <option value="urutan" <?= $sort === 'urutan' ? 'selected' : '' ?>>Rekomendasi</option>
                                <option value="harga_asc" <?= $sort === 'harga_asc' ? 'selected' : '' ?>>Harga Terendah
                                </option>
                                <option value="harga_desc" <?= $sort === 'harga_desc' ? 'selected' : '' ?>>Harga Tertinggi
                                </option>
                                <option value="durasi" <?= $sort === 'durasi' ? 'selected' : '' ?>>Durasi Terpendek
                                </option>
                            </select>
                            <button type="submit" class="btn btn-success rounded-pill px-3" style="font-size:.83rem"><i
                                    class="bi bi-search"></i></button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- RESULT INFO -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0" style="font-size:.85rem">
                    Menampilkan <strong><?= count($pakets) ?></strong> dari <strong><?= $total ?></strong> paket
                    <?= $search ? 'untuk "<strong>' . htmlspecialchars($search) . '</strong>"' : '' ?>
                    <?= $kategori ? 'kategori <strong>' . ucfirst($kategori) . '</strong>' : '' ?>
                </p>
            </div>

            <!-- PAKET GRID -->
            <?php if (!empty($pakets)): ?>
                <div class="row g-4">
                    <?php foreach ($pakets as $i => $p):
                        $terisi = $p['kuota'] - $p['sisa_kuota'];
                        $persen = $p['kuota'] > 0 ? round(($terisi / $p['kuota']) * 100) : 0;
                        $fasilitas = json_decode($p['fasilitas'] ?? '[]', true);
                        ?>
                        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= ($i % 3) * 40 ?>">
                            <div class="paket-card">
                                <div class="paket-img">
                                    <?php if (!empty($p['banner'])): ?>
                                        <img src="<?= UPLOAD_URL . $p['banner'] ?>" alt="<?= htmlspecialchars($p['nama_paket']) ?>">
                                    <?php else: ?>
                                        <div
                                            style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--hijau-tua),var(--hijau-muda))">
                                            <i class="bi bi-building text-white opacity-25" style="font-size:4rem"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="paket-badge badge-<?= $p['kategori'] ?>"><?= ucfirst($p['kategori']) ?></span>
                                    <?php if ($p['status'] === 'habis'): ?>
                                        <div class="badge-habis"><span class="badge bg-danger fs-6">Kuota Penuh</span></div>
                                    <?php endif; ?>
                                    <?php if ($p['harga_coret']): ?>
                                        <span
                                            style="position:absolute;top:14px;right:14px;background:rgba(220,53,69,.9);color:#fff;border-radius:50px;padding:.2rem .7rem;font-size:.72rem;font-weight:700">PROMO</span>
                                    <?php endif; ?>
                                </div>
                                <div class="paket-body">
                                    <div class="paket-name"><?= htmlspecialchars($p['nama_paket']) ?></div>
                                    <div class="mb-2">
                                        <div class="paket-info-item"><i class="bi bi-calendar3"></i> <?= $p['durasi'] ?> Hari /
                                            <?= $p['durasi'] - 1 ?> Malam
                                        </div>
                                        <div class="paket-info-item"><i class="bi bi-airplane"></i>
                                            <?= htmlspecialchars($p['maskapai']) ?></div>
                                        <div class="paket-info-item"><i class="bi bi-building"></i> Mekkah:
                                            <?= htmlspecialchars($p['hotel_mekkah']) ?>
                                            <?= str_repeat('★', $p['bintang_mekkah']) ?>
                                        </div>
                                        <div class="paket-info-item"><i class="bi bi-geo-alt"></i> Madinah:
                                            <?= htmlspecialchars($p['hotel_madinah']) ?>
                                            <?= str_repeat('★', $p['bintang_madinah']) ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($fasilitas)): ?>
                                        <div class="mb-2">
                                            <?php foreach (array_slice($fasilitas, 0, 3) as $f): ?>
                                                <span class="tag-fasilitas"><i
                                                        class="bi bi-check2 me-1"></i><?= htmlspecialchars($f) ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($fasilitas) > 3): ?>
                                                <span class="tag-fasilitas">+<?= count($fasilitas) - 3 ?> lainnya</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="kuota-bar">
                                        <div class="kuota-fill" style="width:<?= $persen ?>%"></div>
                                    </div>
                                    <div style="font-size:.75rem;color:var(--teks-abu);margin-bottom:.8rem">
                                        Sisa <?= $p['sisa_kuota'] ?> kursi dari <?= $p['kuota'] ?> kuota
                                    </div>
                                    <div class="d-flex align-items-end justify-content-between">
                                        <div>
                                            <?php if ($p['harga_coret']): ?>
                                                <div class="paket-price-old">Rp <?= number_format($p['harga_coret'], 0, ',', '.') ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="paket-price">Rp
                                                <?= number_format($p['harga'], 0, ',', '.') ?><span>/orang</span>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="<?= waLink('Assalamualaikum, saya tertarik dengan ' . $p['nama_paket']) ?>"
                                                target="_blank" class="btn-paket-outline" title="Tanya via WA"><i
                                                    class="bi bi-whatsapp"></i></a>
                                            <a href="paket-detail.php?slug=<?= $p['slug'] ?>" class="btn-paket">Detail <i
                                                    class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-5">
                    <?= paginate($total, $page, $perPage, 'paket.php?kategori=' . $kategori . '&q=' . $search . '&sort=' . $sort) ?>
                </div>

            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-search d-block"></i>
                    <h5 style="color:var(--teks-abu)">Paket tidak ditemukan</h5>
                    <p class="text-muted">Coba kata kunci lain atau hapus filter</p>
                    <a href="paket.php" class="btn btn-success rounded-pill px-4">Lihat Semua Paket</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- SIMULASI CICILAN MODAL -->
    <div class="modal fade" id="cicilanModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background:var(--hijau);color:#fff">
                    <h5 class="modal-title"><i class="bi bi-calculator me-2"></i>Simulasi Cicilan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga Paket (Rp)</label>
                        <input type="number" id="sim_harga" class="form-control" placeholder="Contoh: 25000000"
                            oninput="hitungCicilan()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">DP (%)</label>
                        <input type="range" id="sim_dp" class="form-range" min="10" max="100" value="30"
                            oninput="hitungCicilan(); document.getElementById('dp_val').textContent=this.value+'%'">
                        <span id="dp_val" class="text-muted">30%</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tenor Cicilan</label>
                        <select id="sim_bulan" class="form-select" onchange="hitungCicilan()">
                            <option value="3">3 Bulan</option>
                            <option value="6" selected>6 Bulan</option>
                            <option value="9">9 Bulan</option>
                            <option value="12">12 Bulan</option>
                        </select>
                    </div>
                    <div id="hasil_cicilan" class="p-3 rounded" style="background:var(--krem);display:none">
                        <div class="row text-center g-3">
                            <div class="col-6">
                                <div style="font-size:.75rem;color:var(--teks-abu)">DP Pertama</div>
                                <div id="res_dp"
                                    style="font-family:var(--font-display);font-size:1.2rem;font-weight:700;color:var(--hijau)">
                                </div>
                            </div>
                            <div class="col-6">
                                <div style="font-size:.75rem;color:var(--teks-abu)">Cicilan / Bulan</div>
                                <div id="res_cicilan"
                                    style="font-family:var(--font-display);font-size:1.2rem;font-weight:700;color:var(--emas)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?= waLink('Halo, saya ingin konsultasi program cicilan umrah') ?>" target="_blank"
                        class="btn btn-success rounded-pill px-4"><i class="bi bi-whatsapp me-2"></i>Konsultasi
                        Cicilan</a>
                </div>
            </div>
        </div>
    </div>

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
                    <a href="galeri.php" class="footer-link">Galeri</a>
                    <a href="kontak.php" class="footer-link">Kontak</a>
                </div>
                <div class="col-lg-4">
                    <div
                        style="color:var(--emas);font-weight:600;font-size:.85rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:1rem">
                        Simulasi Cicilan</div>
                    <p style="font-size:.83rem;line-height:1.6">Ingin tahu estimasi cicilan? Gunakan kalkulator cicilan
                        kami.</p>
                    <button class="btn btn-outline-warning rounded-pill px-3" style="font-size:.83rem"
                        data-bs-toggle="modal" data-bs-target="#cicilanModal">
                        <i class="bi bi-calculator me-2"></i>Hitung Sekarang
                    </button>
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
        function hitungCicilan() {
            const harga = parseFloat(document.getElementById('sim_harga').value) || 0;
            const dp = parseFloat(document.getElementById('sim_dp').value) / 100;
            const bulan = parseInt(document.getElementById('sim_bulan').value);
            if (!harga) { document.getElementById('hasil_cicilan').style.display = 'none'; return; }
            const dpJml = harga * dp;
            const sisa = harga - dpJml;
            const cicilan = sisa / bulan;
            document.getElementById('res_dp').textContent = 'Rp ' + fmt(dpJml);
            document.getElementById('res_cicilan').textContent = 'Rp ' + fmt(cicilan);
            document.getElementById('hasil_cicilan').style.display = 'block';
        }
        function fmt(n) { return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    </script>
</body>

</html>