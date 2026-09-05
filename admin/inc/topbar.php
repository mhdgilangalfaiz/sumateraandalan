<?php // admin/inc/topbar.php
$notifCount = (int) (db()->fetchOne("SELECT COUNT(*) as c FROM booking WHERE status='pending'", '', [])['c'] ?? 0)
  + (int) (db()->fetchOne("SELECT COUNT(*) as c FROM pembayaran WHERE status='pending'", '', [])['c'] ?? 0);
?>
<div class="topbar <?= isSuperadmin() ? 'role-superadmin' : '' ?>">
  <div class="topbar-left">
    <button class="hamburger" id="hamburger" onclick="toggleSidebar()">
      <i class="bi bi-list" style="font-size:1.2rem"></i>
    </button>
    <div class="page-heading"><?= $pageTitle ?? 'Admin' ?></div>
    <?php if (isSuperadmin()): ?>
      <span class="topbar-role-badge superadmin"><i class="bi bi-shield-lock-fill"></i> Superadmin</span>
    <?php else: ?>
      <span class="topbar-role-badge staff"><i class="bi bi-person-badge-fill"></i> Staff</span>
    <?php endif; ?>
  </div>
  <div class="topbar-right">
    <div class="topbar-date"><i class="bi bi-calendar3"></i><?= tglIndo(date('Y-m-d')) ?></div>
    <a href="<?= BASE_URL ?>/index.php" target="_blank" class="icon-btn" title="Lihat website">
      <i class="bi bi-box-arrow-up-right" style="font-size:.82rem"></i>
    </a>
    <a href="<?= BASE_URL ?>/admin/booking.php?status=pending" class="icon-btn" title="Notifikasi">
      <i class="bi bi-bell" style="font-size:.88rem"></i>
      <?php if ($notifCount > 0): ?><span class="notif-dot"></span><?php endif; ?>
    </a>
  </div>
</div>
<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
  }
  function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
  }
  window.addEventListener('resize', () => { if (window.innerWidth > 900) closeSidebar(); });
</script>