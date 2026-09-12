<?php
// includes/navbar.php
// Navbar publik bersama — dipakai di semua halaman (index.php & pages/*.php).
//
// Cara pakai: sebelum include, boleh set variabel berikut (opsional):
//   $navActive = 'paket' | 'visa' | 'tiket' | 'cekbooking' | 'kontak' | '' (default '')
// Semua link pakai BASE_URL absolut, jadi aman di-include dari kedalaman folder manapun.
$navActive = $navActive ?? '';
$navHideUntilScroll = $navHideUntilScroll ?? false; // true = navbar disembunyikan dulu sampai discroll (khusus beranda)
?>
<nav class="navbar navbar-expand-lg <?= $navHideUntilScroll ? 'nav-hero' : '' ?>" id="mainNav">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
            <img src="<?= BASE_URL ?>/assets/img/logo-sah.png" alt="Logo SAH Umrah" class="navbar-logo me-2">
            SAH <span>Umrah</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <i class="bi bi-list text-white fs-4"></i>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#beranda">Beranda</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($navActive, ['paket', 'visa', 'tiket']) ? 'active' : '' ?>"
                        href="#" id="layananDropdown" role="button" data-bs-toggle="dropdown">
                        Layanan Kami
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="layananDropdown">
                        <li><a class="dropdown-item <?= $navActive === 'paket' ? 'active' : '' ?>"
                                href="<?= BASE_URL ?>/pages/paket.php">
                                <i class="bi bi-suitcase-lg me-2"></i>Paket Umrah
                            </a></li>
                        <li><a class="dropdown-item <?= $navActive === 'visa' ? 'active' : '' ?>"
                                href="<?= BASE_URL ?>/pages/visa.php">
                                <i class="bi bi-file-earmark-text me-2"></i>Visa Umrah
                            </a></li>
                        <li><a class="dropdown-item <?= $navActive === 'tiket' ? 'active' : '' ?>"
                                href="<?= BASE_URL ?>/pages/tiket.php">
                                <i class="bi bi-airplane me-2"></i>Tiket Pesawat
                            </a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#testimoni">Testimoni</a>
                </li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#faq">FAQ</a></li>
                <li class="nav-item">
                    <a class="nav-link <?= $navActive === 'kontak' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>/index.php#kontak">Kontak</a>
                </li>
                <?php if (!isUserLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $navActive === 'cekbooking' ? 'active' : '' ?>"
                            href="<?= BASE_URL ?>/pages/cek-booking.php">
                            <i class="bi bi-search me-1"></i>Cek Booking
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (isUserLoggedIn()): ?>
                    <li class="nav-item dropdown ms-2">
                        <a class="nav-link dropdown-toggle btn-navbar" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars(explode(' ', $_SESSION['user_nama'])[0]) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/profil.php">
                                    <i class="bi bi-person me-2"></i>Profil Saya
                                </a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/dashboard.php">
                                    <i class="bi bi-receipt me-2"></i>Riwayat Pesanan
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/user/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Keluar
                                </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-2">
                        <a class="nav-link btn-navbar" href="<?= BASE_URL ?>/user/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script>
    (function () {
        var nav = document.getElementById('mainNav');
        if (!nav) return;
        function toggleScrolled() {
            nav.classList.toggle('scrolled', window.scrollY > 80);
        }
        window.addEventListener('scroll', toggleScrolled);
        toggleScrolled(); // set state awal (kalau halaman dibuka sambil sudah discroll / reload)
    })();
</script>