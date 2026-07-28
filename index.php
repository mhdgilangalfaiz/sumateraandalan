<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Ambil data dari database
$paket_list = db()->fetchAll("SELECT * FROM paket_umrah WHERE status = 'aktif' AND featured = 1 ORDER BY urutan ASC LIMIT 6");
$testimoni_list = db()->fetchAll("SELECT * FROM testimoni WHERE status = 'approved' AND featured = 1 ORDER BY created_at DESC LIMIT 6");
$faq_list = db()->fetchAll("SELECT * FROM faq WHERE status = 1 ORDER BY urutan ASC LIMIT 8");
$pengaturan = db()->fetchAll("SELECT nama_key, nilai FROM pengaturan");
$settings = [];
foreach ($pengaturan as $p) {
    $settings[$p['nama_key']] = $p['nilai'];
}
$slider_list = db()->fetchAll("SELECT * FROM slider WHERE status = 1 ORDER BY urutan ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($settings['meta_title'] ?? 'SAH Umrah - Travel Umrah Terpercaya') ?>
    </title>
    <meta name="description" content="<?= htmlspecialchars($settings['meta_desc'] ?? '') ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <!-- AOS Animation -->
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
            --font-body: 'DM Sans', sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--putih);
            color: var(--teks-gelap);
            overflow-x: hidden;
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--krem);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--hijau);
            border-radius: 3px;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background: transparent;
            padding: 1.2rem 0;
            transition: all 0.4s ease;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            opacity: 0;
            transform: translateY(-100%);
            pointer-events: none;
        }

        .navbar.scrolled {
            background: rgba(27, 77, 46, 0.97);
            backdrop-filter: blur(10px);
            padding: 0.8rem 0;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .navbar-brand {
            font-family: var(--font-display);
            font-size: 1.4rem;
            font-weight: 700;
            color: white !important;
            letter-spacing: 0.5px;
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
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
            padding: 0.5rem 1rem !important;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: var(--emas) !important;
        }

        .btn-navbar {
            background: var(--emas);
            color: var(--hijau-tua) !important;
            border-radius: 50px;
            padding: 0.5rem 1.4rem !important;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .btn-navbar:hover {
            background: var(--emas-muda);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(201, 168, 76, 0.4);
        }

        /* ===== HERO ===== */
        .hero-section {
            min-height: 100vh;
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #0D2B1A 0%, #1B4D2E 40%, #1B6B3A 100%);
        }

        .hero-pattern {
            position: absolute;
            inset: 0;
            opacity: 0.06;
            background-image: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23C9A84C' fill-opacity='1'%3E%3Cpath d='M40 0L53 27H80L57 44L66 71L40 54L14 71L23 44L0 27H27L40 0Z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .hero-glow {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201, 168, 76, 0.12) 0%, transparent 70%);
            right: -100px;
            top: 50%;
            transform: translateY(-50%);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(201, 168, 76, 0.15);
            border: 1px solid rgba(201, 168, 76, 0.4);
            color: var(--emas-muda);
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            margin-bottom: 1.5rem;
        }

        .hero-arabic {
            font-family: var(--font-arab);
            font-size: 2.8rem;
            color: var(--emas);
            line-height: 1.4;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 20px rgba(201, 168, 76, 0.3);
        }

        .hero-title {
            font-family: var(--font-display);
            font-size: clamp(2.2rem, 5vw, 3.8rem);
            font-weight: 700;
            color: white;
            line-height: 1.15;
            margin-bottom: 1.2rem;
        }

        .hero-title span {
            color: var(--emas);
        }

        .hero-desc {
            color: rgba(255, 255, 255, 0.75);
            font-size: 1.05rem;
            line-height: 1.7;
            max-width: 520px;
            margin-bottom: 2.2rem;
        }

        .btn-hero-primary {
            background: var(--emas);
            color: var(--hijau-tua);
            border: none;
            padding: 0.85rem 2rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(201, 168, 76, 0.35);
        }

        .btn-hero-primary:hover {
            background: var(--emas-muda);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(201, 168, 76, 0.45);
            color: var(--hijau-tua);
        }

        .btn-hero-outline {
            background: transparent;
            color: white;
            border: 1.5px solid rgba(255, 255, 255, 0.4);
            padding: 0.85rem 2rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-hero-outline:hover {
            border-color: var(--emas);
            color: var(--emas);
        }

        .hero-stats {
            display: flex;
            gap: 2.5rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .hero-stat-num {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--emas);
        }

        .hero-stat-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .hero-image-wrap {
            position: relative;
            z-index: 2;
        }

        .hero-img-card {
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
        }

        .hero-img-card img {
            width: 100%;
            height: 480px;
            object-fit: cover;
        }

        .hero-img-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(27, 77, 46, 0.85));
            padding: 2rem;
        }

        .hero-img-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(201, 168, 76, 0.9);
            color: var(--hijau-tua);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .floating-card {
            position: absolute;
            background: white;
            border-radius: 14px;
            padding: 1rem 1.2rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            z-index: 10;
        }

        .floating-card-left {
            left: -40px;
            top: 30%;
            animation: floatUp 3s ease-in-out infinite;
        }

        .floating-card-bottom {
            right: -20px;
            bottom: 15%;
            animation: floatUp 3s ease-in-out infinite 1.5s;
        }

        @keyframes floatUp {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .fc-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
        }

        .fc-val {
            font-weight: 700;
            font-size: 1rem;
            color: var(--teks-gelap);
        }

        .fc-lbl {
            font-size: 0.72rem;
            color: var(--teks-abu);
        }

        /* ===== SECTION STYLES ===== */
        .section-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--hijau);
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 0.8rem;
        }

        .section-label::before,
        .section-label::after {
            content: '';
            width: 30px;
            height: 1.5px;
            background: var(--emas);
        }

        .section-title {
            font-family: var(--font-display);
            font-size: clamp(1.7rem, 3vw, 2.4rem);
            font-weight: 700;
            color: var(--hijau-tua);
            line-height: 1.25;
            margin-bottom: 1rem;
        }

        .section-desc {
            color: var(--teks-abu);
            font-size: 1rem;
            line-height: 1.7;
            max-width: 550px;
        }

        /* ===== KENAPA KAMI ===== */
        .why-section {
            background: var(--krem);
            padding: 90px 0;
        }

        .why-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            height: 100%;
            border: 1px solid rgba(27, 107, 58, 0.08);
            transition: all 0.3s;
        }

        .why-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(27, 77, 46, 0.1);
            border-color: var(--emas);
        }

        .why-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--hijau), var(--hijau-muda));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.4rem;
            margin-bottom: 1.2rem;
        }

        .why-title {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--hijau-tua);
            margin-bottom: 0.5rem;
        }

        .why-desc {
            font-size: 0.88rem;
            color: var(--teks-abu);
            line-height: 1.6;
        }

        /* ===== PAKET ===== */
        .paket-section {
            padding: 90px 0;
            background: white;
        }

        .paket-card {
            border-radius: 18px;
            overflow: hidden;
            background: white;
            border: 1px solid rgba(27, 107, 58, 0.1);
            transition: all 0.35s;
            height: 100%;
        }

        .paket-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 60px rgba(27, 77, 46, 0.15);
            border-color: var(--hijau);
        }

        .paket-img {
            height: 200px;
            background: linear-gradient(135deg, var(--hijau-tua), var(--hijau-muda));
            position: relative;
            overflow: hidden;
        }

        .paket-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .paket-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            padding: 0.3rem 0.85rem;
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-reguler {
            background: rgba(27, 107, 58, 0.9);
            color: white;
        }

        .badge-plus {
            background: rgba(201, 168, 76, 0.9);
            color: #1A1A1A;
        }

        .badge-vip {
            background: rgba(139, 0, 0, 0.85);
            color: white;
        }

        .badge-promo {
            background: rgba(220, 53, 69, 0.9);
            color: white;
        }

        .paket-body {
            padding: 1.5rem;
        }

        .paket-name {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--hijau-tua);
            margin-bottom: 0.8rem;
        }

        .paket-info {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            margin-bottom: 1rem;
        }

        .paket-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.83rem;
            color: var(--teks-abu);
        }

        .paket-info-item i {
            color: var(--hijau);
            font-size: 0.9rem;
            width: 16px;
        }

        .paket-price-old {
            font-size: 0.82rem;
            color: #aaa;
            text-decoration: line-through;
        }

        .paket-price {
            font-family: var(--font-display);
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--hijau);
        }

        .paket-price span {
            font-size: 0.8rem;
            font-weight: 400;
            color: var(--teks-abu);
        }

        .btn-paket {
            background: var(--hijau);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.6rem 1.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-paket:hover {
            background: var(--hijau-tua);
            color: white;
            transform: translateX(3px);
        }

        .kuota-bar {
            height: 4px;
            background: #eee;
            border-radius: 2px;
            margin-bottom: 0.5rem;
            overflow: hidden;
        }

        .kuota-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--hijau), var(--emas));
            border-radius: 2px;
            transition: width 1s ease;
        }

        .kuota-text {
            font-size: 0.75rem;
            color: var(--teks-abu);
        }

        /* ===== PORTOFOLIO ===== */
        .porto-section {
            padding: 90px 0;
            background: var(--krem);
        }

        .porto-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: auto;
            gap: 12px;
        }

        .porto-item {
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
        }

        .porto-item:first-child {
            grid-column: span 2;
            grid-row: span 2;
        }

        .porto-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
            display: block;
            min-height: 160px;
        }

        .porto-item:first-child img {
            min-height: 320px;
        }

        .porto-item:hover img {
            transform: scale(1.05);
        }

        .porto-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(transparent, rgba(27, 77, 46, 0.8));
            opacity: 0;
            transition: opacity 0.3s;
            display: flex;
            align-items: flex-end;
            padding: 1rem;
        }

        .porto-item:hover .porto-overlay {
            opacity: 1;
        }

        .porto-overlay-text {
            color: white;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* ===== TESTIMONI ===== */
        .testi-section {
            padding: 90px 0;
            background: white;
        }

        .testi-card {
            background: var(--krem);
            border-radius: 18px;
            padding: 1.8rem;
            border: 1px solid var(--krem-tua);
            height: 100%;
            transition: all 0.3s;
            position: relative;
        }

        .testi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(27, 77, 46, 0.1);
        }

        .testi-quote {
            font-family: var(--font-arab);
            font-size: 3rem;
            color: var(--emas);
            line-height: 1;
            margin-bottom: 0.8rem;
            opacity: 0.5;
        }

        .testi-text {
            font-size: 0.9rem;
            color: var(--teks-gelap);
            line-height: 1.7;
            margin-bottom: 1.2rem;
            font-style: italic;
        }

        .testi-stars {
            color: #F59E0B;
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }

        .testi-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--hijau), var(--emas));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .testi-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--teks-gelap);
        }

        .testi-from {
            font-size: 0.78rem;
            color: var(--teks-abu);
        }

        .testi-paket {
            font-size: 0.75rem;
            color: var(--hijau);
            font-weight: 500;
            margin-top: 2px;
        }

        /* ===== FAQ ===== */
        .faq-section {
            padding: 90px 0;
            background: var(--krem);
        }

        .accordion-button {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--hijau-tua) !important;
            background: white !important;
            border: none;
            box-shadow: none !important;
        }

        .accordion-button:not(.collapsed) {
            color: var(--hijau) !important;
            background: white !important;
        }

        .accordion-button::after {
            filter: none;
        }

        .accordion-item {
            border: 1px solid rgba(27, 107, 58, 0.12) !important;
            border-radius: 12px !important;
            margin-bottom: 0.7rem;
            overflow: hidden;
        }

        .accordion-body {
            font-size: 0.88rem;
            color: var(--teks-abu);
            line-height: 1.7;
        }

        /* ===== KONTAK & CTA ===== */
        .cta-section {
            padding: 90px 0;
            background: linear-gradient(135deg, var(--hijau-tua) 0%, var(--hijau) 60%, var(--hijau-muda) 100%);
            position: relative;
            overflow: hidden;
        }

        .cta-pattern {
            position: absolute;
            inset: 0;
            opacity: 0.04;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23C9A84C' fill-opacity='1'%3E%3Cpath d='M30 0L39 20.5H60L42.5 33.2L49.5 53.5L30 40.5L10.5 53.5L17.5 33.2L0 20.5H21L30 0Z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .cta-arabic {
            font-family: var(--font-arab);
            font-size: 2rem;
            color: var(--emas);
            opacity: 0.7;
            margin-bottom: 1rem;
        }

        .cta-title {
            font-family: var(--font-display);
            font-size: clamp(1.8rem, 3.5vw, 2.8rem);
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
        }

        .cta-desc {
            color: rgba(255, 255, 255, 0.75);
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .btn-wa {
            background: #25D366;
            color: white;
            border: none;
            padding: 1rem 2.2rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.35);
        }

        .btn-wa:hover {
            background: #22c35e;
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(37, 211, 102, 0.45);
            color: white;
        }

        .kontak-info {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .kontak-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            color: rgba(255, 255, 255, 0.85);
        }

        .kontak-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(201, 168, 76, 0.2);
            border: 1px solid rgba(201, 168, 76, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--emas);
            font-size: 1rem;
            flex-shrink: 0;
        }

        .kontak-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .kontak-val {
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* ===== FOOTER ===== */
        footer {
            background: var(--hijau-tua);
            padding: 60px 0 30px;
            color: rgba(255, 255, 255, 0.75);
        }

        .footer-brand {
            font-family: var(--font-display);
            font-size: 1.4rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .footer-brand span {
            color: var(--emas);
        }

        .footer-desc {
            font-size: 0.85rem;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .footer-title {
            color: var(--emas);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1.2rem;
        }

        .footer-link {
            color: rgba(255, 255, 255, 0.65);
            text-decoration: none;
            font-size: 0.85rem;
            display: block;
            margin-bottom: 0.6rem;
            transition: color 0.3s;
        }

        .footer-link:hover {
            color: var(--emas);
        }

        .footer-social a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.9rem;
            text-decoration: none;
            margin-right: 0.5rem;
            transition: all 0.3s;
        }

        .footer-social a:hover {
            background: var(--emas);
            color: var(--hijau-tua);
        }

        .footer-bottom {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.45);
        }

        .izin-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(201, 168, 76, 0.15);
            border: 1px solid rgba(201, 168, 76, 0.3);
            color: var(--emas-muda);
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* ===== WA FLOAT ===== */
        .wa-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #25D366;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.45);
            text-decoration: none;
            animation: pulse-wa 2s infinite;
            transition: transform 0.3s;
        }

        .wa-float:hover {
            transform: scale(1.1);
            color: white;
        }

        @keyframes pulse-wa {

            0%,
            100% {
                box-shadow: 0 8px 25px rgba(37, 211, 102, 0.45);
            }

            50% {
                box-shadow: 0 8px 35px rgba(37, 211, 102, 0.7);
            }
        }

        /* ===== BACK TO TOP ===== */
        .back-top {
            position: fixed;
            bottom: 30px;
            left: 30px;
            z-index: 999;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--hijau);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            text-decoration: none;
            opacity: 0;
            transition: all 0.3s;
            pointer-events: none;
        }

        .back-top.show {
            opacity: 1;
            pointer-events: all;
        }

        .back-top:hover {
            background: var(--hijau-tua);
            color: white;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .floating-card {
                display: none;
            }

            .hero-image-wrap {
                margin-top: 3rem;
            }

            .porto-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .porto-item:first-child {
                grid-column: span 2;
            }
        }

        @media (max-width: 768px) {
            .hero-stats {
                gap: 1.5rem;
            }

            .porto-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar navbar-expand-lg" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="<?= BASE_URL ?>/assets/img/logo-sah.png" alt="Logo SAH Umrah" class="navbar-logo me-2">
                SAH <span>Umrah</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <i class="bi bi-list text-white fs-4"></i>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                    <li class="nav-item"><a class="nav-link" href="#paket">Paket Umrah</a></li>
                    <li class="nav-item"><a class="nav-link" href="#porto">Portofolio</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimoni">Testimoni</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                    <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
                    <li class="nav-item ms-2">
                        <a class="nav-link btn-navbar" href="pages/booking.php">
                            <i class="bi bi-calendar-check me-1"></i>Daftar Sekarang
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ===== HERO ===== -->
    <section class="hero-section">
        <div class="hero-bg"></div>
        <div class="hero-pattern"></div>
        <div class="hero-glow"></div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content" data-aos="fade-right" data-aos-duration="500">
                    <div class="hero-badge">
                        <i class="bi bi-patch-check-fill"></i>
                        Izin PPIU Resmi Kemenag RI
                    </div>
                    <div class="hero-arabic">بِسْمِ اللهِ الرَّحْمٰنِ الرَّحِيْمِ</div>
                    <h1 class="hero-title">
                        Perjalanan Suci<br>
                        Ke <span>Tanah Haram</span><br>
                        Penuh Berkah
                    </h1>
                    <p class="hero-desc">
                        Percayakan ibadah umrah Anda kepada
                        <?= htmlspecialchars($settings['nama_perusahaan'] ?? 'PT. Sumatera Andalan Haramain') ?>.
                        Melayani jamaah dengan sepenuh hati sejak
                        <?= $settings['tahun_berdiri'] ?? '2015' ?>.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="#paket" class="btn-hero-primary">
                            <i class="bi bi-grid-3x3-gap-fill"></i>
                            Lihat Paket Kami
                        </a>
                        <a href="https://wa.me/<?= $settings['whatsapp'] ?? '6281360000000' ?>?text=<?= urlencode(WA_DEFAULT_MSG) ?>"
                            target="_blank" class="btn-hero-outline">
                            <i class="bi bi-whatsapp"></i>
                            Konsultasi Gratis
                        </a>
                    </div>
                    <div class="hero-stats">
                        <div>
                            <div class="hero-stat-num">
                                <?= number_format(intval($settings['jumlah_jamaah'] ?? 5000)) ?>+
                            </div>
                            <div class="hero-stat-label">Jamaah</div>
                        </div>
                        <div>
                            <div class="hero-stat-num">
                                <?= date('Y') - intval($settings['tahun_berdiri'] ?? 2015) ?>+
                            </div>
                            <div class="hero-stat-label">Tahun Pengalaman</div>
                        </div>
                        <div>
                            <div class="hero-stat-num">5★</div>
                            <div class="hero-stat-label">Rating Jamaah</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 hero-image-wrap" data-aos="fade-left" data-aos-duration="500" data-aos-delay="120">
                    <div style="position: relative; padding: 0 20px;">
                        <div class="hero-img-card">
                            <img src="https://images.unsplash.com/photo-1564769625905-50e93615e769?w=800&q=80"
                                alt="Masjidil Haram" loading="lazy">
                            <div class="hero-img-overlay">
                                <div class="hero-img-badge">
                                    <i class="bi bi-star-fill me-1"></i>
                                    Hotel Bintang 5 Dekat Masjidil Haram
                                </div>
                            </div>
                        </div>
                        <div class="floating-card floating-card-left">
                            <div class="fc-icon" style="background: #FEF3C7;"><span>✈️</span></div>
                            <div class="fc-val">BatikAir Malaysia</div>
                            <div class="fc-lbl">Maskapai Partner</div>
                        </div>
                        <div class="floating-card floating-card-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <div class="fc-icon" style="background: #DCFCE7; color: #16a34a; font-size: 1rem;"><i
                                        class="bi bi-shield-check-fill"></i></div>
                                <div>
                                    <div class="fc-val">100% Terpercaya</div>
                                    <div class="fc-lbl">Izin Resmi PPIU</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== KENAPA KAMI ===== -->
    <section class="why-section">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <div class="section-label">Keunggulan Kami</div>
                <h2 class="section-title">Mengapa Memilih SAH Travel?</h2>
                <p class="section-desc mx-auto">Kami hadir dengan komitmen penuh untuk memberikan pengalaman ibadah yang
                    nyaman, aman, dan bermakna</p>
            </div>
            <div class="row g-4">
                <?php
                $keunggulan = [
                    ['icon' => 'bi-patch-check-fill', 'title' => 'Izin Resmi PPIU', 'desc' => 'Terdaftar dan diawasi langsung oleh Kementerian Agama RI dengan nomor izin ' . ($settings['izin_ppiu'] ?? 'D/333/2024')],
                    ['icon' => 'bi-people-fill', 'title' => 'Muthawwif Berpengalaman', 'desc' => 'Pembimbing ibadah profesional, berpengalaman, dan hafal seluk-beluk Tanah Haram'],
                    ['icon' => 'bi-building', 'title' => 'Hotel Premium', 'desc' => 'Akomodasi hotel bintang 4-5 yang berlokasi strategis dekat Masjidil Haram dan Masjid Nabawi'],
                    ['icon' => 'bi-airplane-fill', 'title' => 'Maskapai Terbaik', 'desc' => 'Penerbangan langsung dengan maskapai terpercaya seperti Garuda Indonesia dan Saudi Airlines'],
                    ['icon' => 'bi-wallet2', 'title' => 'Harga Transparan', 'desc' => 'Tidak ada biaya tersembunyi. Semua sudah termasuk dalam paket yang telah kami rancang'],
                    ['icon' => 'bi-headset', 'title' => 'Layanan 24 Jam', 'desc' => 'Tim kami siap membantu Anda 24 jam selama perjalanan, mulai dari keberangkatan hingga kepulangan'],
                ];
                foreach ($keunggulan as $i => $item):
                    ?>
                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $i * 50 ?>">
                        <div class="why-card">
                            <div class="why-icon"><i class="bi <?= $item['icon'] ?>"></i></div>
                            <div class="why-title">
                                <?= $item['title'] ?>
                            </div>
                            <div class="why-desc">
                                <?= $item['desc'] ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ===== PAKET UMRAH ===== -->
    <section class="paket-section" id="paket">
        <div class="container">
            <div class="row align-items-end mb-5">
                <div class="col-lg-7" data-aos="fade-right">
                    <div class="section-label">Pilihan Paket</div>
                    <h2 class="section-title">Paket Umrah Terbaik Kami</h2>
                    <p class="section-desc">Tersedia berbagai pilihan paket umrah sesuai kebutuhan dan anggaran Anda</p>
                </div>
                <div class="col-lg-5 text-lg-end mt-3 mt-lg-0" data-aos="fade-left">
                    <a href="pages/paket.php" class="btn btn-outline-success rounded-pill px-4">
                        Lihat Semua Paket <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
            <div class="row g-4">
                <?php if (!empty($paket_list)): ?>
                    <?php foreach ($paket_list as $i => $paket): ?>
                        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $i * 60 ?>">
                            <div class="paket-card">
                                <div class="paket-img">
                                    <?php if (!empty($paket['banner'])): ?>
                                        <img src="<?= UPLOAD_URL . $paket['banner'] ?>"
                                            alt="<?= htmlspecialchars($paket['nama_paket']) ?>">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                                            <i class="bi bi-building text-white opacity-25" style="font-size: 4rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="paket-badge badge-<?= $paket['kategori'] ?>">
                                        <?= ucfirst($paket['kategori']) ?>
                                    </span>
                                </div>
                                <div class="paket-body">
                                    <div class="paket-name">
                                        <?= htmlspecialchars($paket['nama_paket']) ?>
                                    </div>
                                    <div class="paket-info">
                                        <div class="paket-info-item">
                                            <i class="bi bi-calendar3"></i>
                                            <?= $paket['durasi'] ?> Hari
                                        </div>
                                        <div class="paket-info-item">
                                            <i class="bi bi-airplane"></i>
                                            <?= htmlspecialchars($paket['maskapai']) ?>
                                        </div>
                                        <div class="paket-info-item">
                                            <i class="bi bi-building"></i>
                                            <?= htmlspecialchars($paket['hotel_mekkah']) ?>
                                            <?= str_repeat('★', $paket['bintang_mekkah']) ?>
                                        </div>
                                        <div class="paket-info-item">
                                            <i class="bi bi-geo-alt"></i>
                                            <?= htmlspecialchars($paket['hotel_madinah']) ?>
                                            <?= str_repeat('★', $paket['bintang_madinah']) ?>
                                        </div>
                                    </div>
                                    <?php
                                    $terisi = $paket['kuota'] - $paket['sisa_kuota'];
                                    $persen = $paket['kuota'] > 0 ? round(($terisi / $paket['kuota']) * 100) : 0;
                                    ?>
                                    <div class="kuota-bar">
                                        <div class="kuota-fill" style="width: <?= $persen ?>%"></div>
                                    </div>
                                    <div class="kuota-text mb-3">Sisa
                                        <?= $paket['sisa_kuota'] ?> kursi dari
                                        <?= $paket['kuota'] ?> kuota
                                    </div>
                                    <div class="d-flex align-items-end justify-content-between">
                                        <div>
                                            <?php if ($paket['harga_coret']): ?>
                                                <div class="paket-price-old">Rp
                                                    <?= number_format($paket['harga_coret'], 0, ',', '.') ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="paket-price">
                                                Rp
                                                <?= number_format($paket['harga'], 0, ',', '.') ?>
                                                <span>/orang</span>
                                            </div>
                                        </div>
                                        <a href="pages/paket-detail.php?slug=<?= $paket['slug'] ?>" class="btn-paket">
                                            Detail <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Placeholder cards jika belum ada data -->
                    <?php
                    $placeholders = [
                        ['nama' => 'Paket Reguler 9 Hari', 'kat' => 'reguler', 'durasi' => 9, 'maskapai' => 'Garuda Indonesia', 'hotel_m' => 'Grand Zam Zam Tower ★★★★★', 'hotel_mad' => 'Dallah Taibah ★★★★', 'harga' => 25000000, 'harga_c' => 28000000, 'sisa' => 30, 'kuota' => 45],
                        ['nama' => 'Paket Plus 12 Hari', 'kat' => 'plus', 'durasi' => 12, 'maskapai' => 'Saudi Airlines', 'hotel_m' => 'Swissotel Al Maqam ★★★★★', 'hotel_mad' => 'Anwar Al Madinah ★★★★★', 'harga' => 35000000, 'harga_c' => null, 'sisa' => 20, 'kuota' => 45],
                        ['nama' => 'Paket VIP 15 Hari', 'kat' => 'vip', 'durasi' => 15, 'maskapai' => 'Garuda Indonesia', 'hotel_m' => 'Raffles Makkah Palace ★★★★★', 'hotel_mad' => 'Oberoi Madinah ★★★★★', 'harga' => 65000000, 'harga_c' => null, 'sisa' => 15, 'kuota' => 20],
                    ];
                    foreach ($placeholders as $i => $p):
                        $persen = round((($p['kuota'] - $p['sisa']) / $p['kuota']) * 100);
                        ?>
                        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
                            <div class="paket-card">
                                <div class="paket-img">
                                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                                        <i class="bi bi-building text-white opacity-25" style="font-size: 4rem;"></i>
                                    </div>
                                    <span class="paket-badge badge-<?= $p['kat'] ?>">
                                        <?= ucfirst($p['kat']) ?>
                                    </span>
                                </div>
                                <div class="paket-body">
                                    <div class="paket-name">
                                        <?= $p['nama'] ?>
                                    </div>
                                    <div class="paket-info">
                                        <div class="paket-info-item"><i class="bi bi-calendar3"></i>
                                            <?= $p['durasi'] ?> Hari
                                        </div>
                                        <div class="paket-info-item"><i class="bi bi-airplane"></i>
                                            <?= $p['maskapai'] ?>
                                        </div>
                                        <div class="paket-info-item"><i class="bi bi-building"></i>
                                            <?= $p['hotel_m'] ?>
                                        </div>
                                        <div class="paket-info-item"><i class="bi bi-geo-alt"></i>
                                            <?= $p['hotel_mad'] ?>
                                        </div>
                                    </div>
                                    <div class="kuota-bar">
                                        <div class="kuota-fill" style="width:<?= $persen ?>%"></div>
                                    </div>
                                    <div class="kuota-text mb-3">Sisa
                                        <?= $p['sisa'] ?> kursi dari
                                        <?= $p['kuota'] ?> kuota
                                    </div>
                                    <div class="d-flex align-items-end justify-content-between">
                                        <div>
                                            <?php if ($p['harga_c']): ?>
                                                <div class="paket-price-old">Rp
                                                    <?= number_format($p['harga_c'], 0, ',', '.') ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="paket-price">Rp
                                                <?= number_format($p['harga'], 0, ',', '.') ?><span>/orang</span>
                                            </div>
                                        </div>
                                        <a href="#" class="btn-paket">Detail <i class="bi bi-arrow-right"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== PORTOFOLIO ===== -->
    <section class="porto-section" id="porto">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <div class="section-label">Portofolio</div>
                <h2 class="section-title">Momen Perjalanan Jamaah Kami</h2>
                <p class="section-desc mx-auto">Ribuan jamaah telah mempercayakan ibadah umrah mereka kepada kami.
                    Berikut sebagian momen berharga mereka.</p>
            </div>
            <div class="porto-grid" data-aos="fade-up" data-aos-delay="60">
                <?php
                $porto_imgs = [
                    ['src' => 'https://images.unsplash.com/photo-1575039375208-e4e8e7a66cc9?w=800&q=80', 'label' => 'Masjidil Haram, Mekkah'],
                    ['src' => 'https://images.unsplash.com/photo-1591672299888-e16a08b6c7ce?w=400&q=80', 'label' => 'Masjid Nabawi, Madinah'],
                    ['src' => 'https://images.unsplash.com/photo-1566378246598-5b11a0d486cc?w=400&q=80', 'label' => 'Jamaah Berdoa'],
                    ['src' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=400&q=80', 'label' => 'Hotel Akomodasi'],
                    ['src' => 'https://images.unsplash.com/photo-1587974928442-77dc3e0dba72?w=400&q=80', 'label' => 'Ziarah Bersejarah'],
                ];
                foreach ($porto_imgs as $pi => $img):
                    ?>
                    <div class="porto-item">
                        <img src="<?= $img['src'] ?>" alt="<?= $img['label'] ?>" loading="lazy">
                        <div class="porto-overlay">
                            <div class="porto-overlay-text"><i class="bi bi-geo-alt me-1"></i>
                                <?= $img['label'] ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4" data-aos="fade-up">
                <a href="pages/galeri.php" class="btn btn-outline-success rounded-pill px-4">
                    <i class="bi bi-images me-2"></i>Lihat Galeri Lengkap
                </a>
            </div>
        </div>
    </section>

    <!-- ===== TESTIMONI ===== -->
    <section class="testi-section" id="testimoni">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <div class="section-label">Testimoni</div>
                <h2 class="section-title">Kata Mereka Tentang Kami</h2>
                <p class="section-desc mx-auto">Kepuasan jamaah adalah prioritas kami. Ini adalah sebagian ungkapan hati
                    dari mereka</p>
            </div>
            <div class="row g-4">
                <?php
                $testi_data = !empty($testimoni_list) ? $testimoni_list : [
                    ['nama' => 'Hj. Siti Aminah', 'asal_kota' => 'Medan', 'paket' => 'Paket Plus 12 Hari', 'rating' => 5, 'isi' => 'Alhamdulillah, perjalanan umrah bersama SAH Travel sangat berkesan. Pelayanan prima, muthawwif berpengalaman, dan hotel yang nyaman. Insya Allah akan umrah lagi bersama SAH Travel.', 'foto' => null],
                    ['nama' => 'H. Budi Santoso', 'asal_kota' => 'Binjai', 'paket' => 'Paket VIP 15 Hari', 'rating' => 5, 'isi' => 'Luar biasa! Paket VIP benar-benar sepadan dengan biayanya. Hotel langsung menghadap Ka\'bah, transportasi mewah, dan pelayanan yang sangat personal. Recommended!', 'foto' => null],
                    ['nama' => 'Ibu Rahma Dewi', 'asal_kota' => 'Deli Serdang', 'paket' => 'Paket Reguler 9 Hari', 'rating' => 5, 'isi' => 'Ini umrah pertama saya dan keluarga. Terima kasih SAH Travel sudah membimbing kami dengan sabar. Semua berjalan lancar sesuai jadwal. Jazakumullahu khairan.', 'foto' => null],
                ];
                foreach ($testi_data as $i => $t):
                    $initial = strtoupper(mb_substr($t['nama'], 0, 1));
                    ?>
                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
                        <div class="testi-card">
                            <div class="testi-quote">"</div>
                            <div class="testi-stars">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="bi bi-star<?= $s <= $t['rating'] ? '-fill' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="testi-text">
                                <?= htmlspecialchars($t['isi']) ?>
                            </p>
                            <div class="d-flex align-items-center gap-3 mt-auto">
                                <div class="testi-avatar">
                                    <?= $initial ?>
                                </div>
                                <div>
                                    <div class="testi-name">
                                        <?= htmlspecialchars($t['nama']) ?>
                                    </div>
                                    <div class="testi-from"><i class="bi bi-geo-alt-fill me-1"></i>
                                        <?= htmlspecialchars($t['asal_kota'] ?? '') ?>
                                    </div>
                                    <div class="testi-paket"><i class="bi bi-suitcase2 me-1"></i>
                                        <?= htmlspecialchars($t['paket'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ===== FAQ ===== -->
    <section class="faq-section" id="faq">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-5" data-aos="fade-up">
                        <div class="section-label">FAQ</div>
                        <h2 class="section-title">Pertanyaan yang Sering Ditanyakan</h2>
                        <p class="section-desc mx-auto">Temukan jawaban atas pertanyaan umum seputar ibadah umrah
                            bersama kami</p>
                    </div>
                    <div class="accordion" id="faqAccordion" data-aos="fade-up" data-aos-delay="60">
                        <?php
                        $faq_data = !empty($faq_list) ? $faq_list : [
                            ['pertanyaan' => 'Apa itu umrah?', 'jawaban' => 'Umrah adalah ibadah yang dilakukan dengan cara mengunjungi Baitullah (Ka\'bah) di Mekkah Al-Mukarramah dengan syarat dan rukun tertentu.'],
                            ['pertanyaan' => 'Berapa biaya umrah di SAH Travel?', 'jawaban' => 'Biaya umrah kami mulai dari Rp 25.000.000 untuk paket reguler. Harga sudah termasuk tiket pesawat, hotel, visa, dan konsumsi.'],
                            ['pertanyaan' => 'Apa saja dokumen yang diperlukan?', 'jawaban' => 'Dokumen yang diperlukan: KTP, Paspor (berlaku min. 7 bulan), Kartu Keluarga, Surat Nikah (bagi pasangan), Akta Lahir (bagi anak-anak), dan foto terbaru.'],
                            ['pertanyaan' => 'Apakah ada cicilan pembayaran?', 'jawaban' => 'Ya, kami menyediakan program cicilan. Dengan DP minimal 30% dari total biaya, sisanya dapat dicicil hingga keberangkatan.'],
                            ['pertanyaan' => 'Berapa lama proses visa umrah?', 'jawaban' => 'Proses pengurusan visa umrah memakan waktu 7-14 hari kerja setelah dokumen lengkap diserahkan.'],
                            ['pertanyaan' => 'Apakah SAH Travel memiliki izin resmi?', 'jawaban' => 'Ya, PT. Sumatera Andalan Haramain memiliki izin PPIU (Penyelenggara Perjalanan Ibadah Umrah) resmi dari Kementerian Agama RI.'],
                        ];
                        foreach ($faq_data as $fi => $faq):
                            ?>
                            <div class="accordion-item" data-aos="fade-up" data-aos-delay="<?= $fi * 40 ?>">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?= $fi > 0 ? 'collapsed' : '' ?>" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq<?= $fi ?>">
                                        <i class="bi bi-question-circle-fill me-2" style="color: var(--emas)"></i>
                                        <?= htmlspecialchars($faq['pertanyaan']) ?>
                                    </button>
                                </h2>
                                <div id="faq<?= $fi ?>" class="accordion-collapse collapse <?= $fi === 0 ? 'show' : '' ?>"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <?= htmlspecialchars($faq['jawaban']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== KONTAK & CTA ===== -->
    <section class="cta-section" id="kontak">
        <div class="cta-pattern"></div>
        <div class="container" style="position: relative; z-index: 2;">
            <div class="row align-items-center g-5">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="cta-arabic">لَبَّيْكَ اللَّهُمَّ لَبَّيْكَ</div>
                    <h2 class="cta-title">Siap Berangkat Umrah Bersama Kami?</h2>
                    <p class="cta-desc">
                        Hubungi tim kami sekarang untuk konsultasi gratis. Kami siap membantu Anda memilih paket umrah
                        yang sesuai dengan kebutuhan dan anggaran Anda.
                    </p>
                    <a href="https://wa.me/<?= $settings['whatsapp'] ?? '6281360000000' ?>?text=<?= urlencode(WA_DEFAULT_MSG) ?>"
                        target="_blank" class="btn-wa me-3 mb-3">
                        <i class="bi bi-whatsapp fs-5"></i>
                        Chat via WhatsApp
                    </a>
                    <a href="pages/booking.php" class="btn-hero-outline d-inline-flex mb-3">
                        <i class="bi bi-calendar-check"></i>
                        Daftar Sekarang
                    </a>
                </div>
                <div class="col-lg-6" data-aos="fade-left" data-aos-delay="120">
                    <div class="kontak-info">
                        <div class="kontak-item">
                            <div class="kontak-icon"><i class="bi bi-geo-alt-fill"></i></div>
                            <div>
                                <div class="kontak-label">Alamat Kantor</div>
                                <div class="kontak-val">
                                    <?= htmlspecialchars($settings['alamat'] ?? 'Jl. Gatot Subroto No. 123, Medan, Sumatera Utara') ?>
                                </div>
                            </div>
                        </div>
                        <div class="kontak-item">
                            <div class="kontak-icon"><i class="bi bi-telephone-fill"></i></div>
                            <div>
                                <div class="kontak-label">Telepon</div>
                                <div class="kontak-val">
                                    <?= htmlspecialchars($settings['telepon'] ?? '+62 813-6000-0000') ?>
                                </div>
                            </div>
                        </div>
                        <div class="kontak-item">
                            <div class="kontak-icon"><i class="bi bi-envelope-fill"></i></div>
                            <div>
                                <div class="kontak-label">Email</div>
                                <div class="kontak-val">
                                    <?= htmlspecialchars($settings['email'] ?? 'info@sah-umrah.com') ?>
                                </div>
                            </div>
                        </div>
                        <div class="kontak-item">
                            <div class="kontak-icon"><i class="bi bi-clock-fill"></i></div>
                            <div>
                                <div class="kontak-label">Jam Operasional</div>
                                <div class="kontak-val">Senin – Sabtu, 08.00 – 17.00 WIB</div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="izin-badge me-2">
                                <i class="bi bi-patch-check-fill"></i>
                                PPIU
                                <?= htmlspecialchars($settings['izin_ppiu'] ?? 'D/333/2024') ?>
                            </span>
                            <span class="izin-badge">
                                <i class="bi bi-award-fill"></i>
                                NIB
                                <?= htmlspecialchars($settings['no_nib'] ?? '1234567890123') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand">
                        <i class="bi bi-moon-stars-fill me-2" style="color: var(--emas)"></i>
                        SAH <span>Travel</span>
                    </div>
                    <p class="footer-desc">
                        <?= htmlspecialchars($settings['tagline'] ?? 'Perjalanan Suci, Pelayanan Terpercaya') ?>.
                        Melayani jamaah umrah dengan sepenuh hati sejak
                        <?= $settings['tahun_berdiri'] ?? '2015' ?>.
                    </p>
                    <div class="footer-social">
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-youtube"></i></a>
                        <a href="https://wa.me/<?= $settings['whatsapp'] ?? '6281360000000' ?>" target="_blank"><i
                                class="bi bi-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="footer-title">Layanan</div>
                    <a href="#paket" class="footer-link">Paket Umrah</a>
                    <a href="pages/booking.php" class="footer-link">Pendaftaran</a>
                    <a href="pages/cek-booking.php" class="footer-link">Cek Booking</a>
                    <a href="pages/pembayaran.php" class="footer-link">Pembayaran</a>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="footer-title">Informasi</div>
                    <a href="pages/tentang.php" class="footer-link">Tentang Kami</a>
                    <a href="#faq" class="footer-link">FAQ</a>
                    <a href="#kontak" class="footer-link">Kontak</a>
                </div>
                <div class="col-lg-4">
                    <div class="footer-title">Legalitas</div>
                    <div class="d-flex flex-column gap-2">
                        <span class="izin-badge" style="width: fit-content;">
                            <i class="bi bi-patch-check-fill"></i>
                            Izin PPIU : 91201032614170001
                        </span>
                        <span class="izin-badge" style="width: fit-content;">
                            <i class="bi bi-award-fill"></i>
                            NIB : -
                        </span>
                    </div>
                </div>
            </div>
            <div class="footer-bottom text-center">
                ©
                <?= date('Y') ?>
                <?= htmlspecialchars($settings['nama_perusahaan'] ?? 'PT. Sumatera Andalan Haramain') ?>. All rights
                reserved.
            </div>
        </div>
    </footer>

    <!-- WhatsApp Float -->
    <a href="https://wa.me/<?= $settings['whatsapp'] ?? '6281360000000' ?>?text=<?= urlencode(WA_DEFAULT_MSG) ?>"
        class="wa-float" target="_blank" title="Chat WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>

    <!-- Back to Top -->
    <a href="#" class="back-top" id="backTop">
        <i class="bi bi-chevron-up"></i>
    </a>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>

    <script>
        // Init AOS
        AOS.init({ once: true, offset: 60, duration: 300 });

        // Navbar scroll
        const nav = document.getElementById('mainNav');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 80);
        });

        // Back to top
        const backTop = document.getElementById('backTop');
        window.addEventListener('scroll', () => {
            backTop.classList.toggle('show', window.scrollY > 400);
        });
        backTop.addEventListener('click', e => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                const target = document.querySelector(a.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Animate kuota bar on scroll
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.querySelectorAll('.kuota-fill').forEach(bar => {
                        bar.style.width = bar.style.width;
                    });
                }
            });
        }, { threshold: 0.2 });
        document.querySelectorAll('.paket-card').forEach(c => observer.observe(c));
    </script>
</body>

</html>