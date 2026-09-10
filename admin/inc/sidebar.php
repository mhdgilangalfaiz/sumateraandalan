<?php
// admin/inc/sidebar.php
$current = basename($_SERVER['PHP_SELF']);
$pendingBooking = (int) (db()->fetchOne("SELECT COUNT(*) as c FROM booking WHERE status='pending'", '',)['c'] ?? 0);
$pendingBayar = (int) (db()->fetchOne("SELECT COUNT(*) as c FROM pembayaran WHERE status='pending'", '',)['c'] ?? 0);
$pendingTesti = (int) (db()->fetchOne("SELECT COUNT(*) as c FROM testimoni WHERE status='pending'", '',)['c'] ?? 0);
$pendingVisa = (int) (db()->fetchOne("SELECT COUNT(*) as c FROM visa_applications WHERE status IN ('dokumen_lengkap','menunggu_pembayaran','perlu_revisi')", '')['c'] ?? 0);
function isActive(string $file): string
{
  global $current;
  return $current === $file ? 'active' : '';
}
?>
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-moon-stars-fill"></i></div>
    <div>
      <div class="brand-name">SAH <span>Travel</span></div>
      <div class="brand-sub">Panel Admin</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-group-label">Utama</div>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link-item <?= isActive('dashboard.php') ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="<?= BASE_URL ?>/admin/booking.php"
      class="nav-link-item <?= isActive('booking.php') ?> <?= isActive('booking-detail.php') ?>">
      <i class="bi bi-calendar-check"></i> Data Booking
      <?php if ($pendingBooking): ?><span class="nav-badge"><?= $pendingBooking ?></span><?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>/admin/pembayaran.php" class="nav-link-item <?= isActive('pembayaran.php') ?>">
      <i class="bi bi-credit-card"></i> Pembayaran
      <?php if ($pendingBayar): ?><span class="nav-badge"><?= $pendingBayar ?></span><?php endif; ?>
    </a>
    <div class="nav-group-label"><?= isSuperadmin() ? 'Laporan Produk & Layanan' : 'Produk & Layanan' ?></div>
    <a href="<?= BASE_URL ?>/admin/paket.php" class="nav-link-item <?= isActive('paket.php') ?>">
      <i class="bi bi-briefcase"></i> Paket Umrah
    </a>
    <a href="<?= BASE_URL ?>/admin/tiket.php" class="nav-link-item <?= isActive('tiket.php') ?>">
      <i class="bi bi-airplane"></i> Tiket Pesawat
    </a>
    <a href="<?= BASE_URL ?>/admin/visa.php" class="nav-link-item <?= isActive('visa.php') ?>">
      <i class="bi bi-file-earmark-text"></i> Visa Umrah
      <?php if ($pendingVisa): ?><span class="nav-badge"><?= $pendingVisa ?></span><?php endif; ?>
    </a>
    <div class="nav-group-label"><?= isSuperadmin() ? 'Laporan Konten' : 'Konten Website' ?></div>
    <a href="<?= BASE_URL ?>/admin/testimoni.php" class="nav-link-item <?= isActive('testimoni.php') ?>">
      <i class="bi bi-chat-quote"></i> Testimoni
      <?php if ($pendingTesti): ?><span class="nav-badge"><?= $pendingTesti ?></span><?php endif; ?>
    </a>
    <div class="nav-group-label">Sistem</div>
    <a href="<?= BASE_URL ?>/admin/pengaturan.php" class="nav-link-item <?= isActive('pengaturan.php') ?>">
      <i class="bi bi-gear"></i> <?= isSuperadmin() ? 'Lihat Pengaturan' : 'Pengaturan' ?>
    </a>
    <?php if (isSuperadmin()): ?>
      <a href="<?= BASE_URL ?>/admin/kelola-staff.php" class="nav-link-item <?= isActive('kelola-staff.php') ?>">
        <i class="bi bi-people"></i> Kelola Staff
      </a>
    <?php endif; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="admin-card">
      <div class="admin-ava"><?= strtoupper(substr($_SESSION['admin_nama'] ?? 'A', 0, 1)) ?></div>
      <div>
        <div class="admin-name"><?= htmlspecialchars($_SESSION['admin_nama'] ?? '') ?></div>
        <div class="admin-role"><?= ucfirst($_SESSION['admin_role'] ?? '') ?></div>
        <?php if (isSuperadmin()): ?>
          <span class="role-tag superadmin"><i class="bi bi-shield-lock-fill"></i> Superadmin · Read Only</span>
        <?php else: ?>
          <span class="role-tag staff"><i class="bi bi-person-badge-fill"></i> Staff Admin</span>
        <?php endif; ?>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/user/logout.php" class="logout-link">
      <i class="bi bi-box-arrow-left"></i> Keluar
    </a>  
  </div>
</aside>