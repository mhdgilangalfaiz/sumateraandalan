<style>
  :root {
    --hijau-tua: #1B4D2E;
    --hijau: #1B6B3A;
    --hijau-muda: #2E8B57;
    --emas: #C9A84C;
    --emas-muda: #E8C97A;
    --krem: #F9F5EE;
    --sidebar-w: 260px;
    --font-display: 'Playfair Display', serif;
    --font-body: 'DM Sans', sans-serif;
  }

  *,
  *::before,
  *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  body {
    font-family: var(--font-body);
    background: #F0F4F8;
    min-height: 100vh;
  }

  /* SIDEBAR */
  .sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    width: var(--sidebar-w);
    background: linear-gradient(180deg, #0D2B1A 0%, #1B4D2E 100%);
    display: flex;
    flex-direction: column;
    z-index: 200;
    transition: transform .3s cubic-bezier(.4, 0, .2, 1);
    box-shadow: 4px 0 24px rgba(0, 0, 0, .15);
  }

  .sidebar-brand {
    padding: 22px 20px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, .07);
  }

  .brand-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--emas), var(--emas-muda));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: var(--hijau-tua);
  }

  .brand-name {
    font-family: var(--font-display);
    font-size: 1rem;
    font-weight: 700;
    color: white;
  }

  .brand-name span {
    color: var(--emas);
  }

  .brand-sub {
    font-size: .65rem;
    color: rgba(255, 255, 255, .3);
    margin-top: 1px;
  }

  .sidebar-nav {
    flex: 1;
    padding: 14px 12px;
    overflow-y: auto;
    scrollbar-width: none;
  }

  .sidebar-nav::-webkit-scrollbar {
    display: none;
  }

  .nav-group-label {
    font-size: .6rem;
    font-weight: 700;
    color: rgba(255, 255, 255, .25);
    text-transform: uppercase;
    letter-spacing: 1.8px;
    padding: 10px 10px 4px;
  }

  .nav-link-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 10px;
    color: rgba(255, 255, 255, .55);
    text-decoration: none;
    font-size: .845rem;
    font-weight: 500;
    margin-bottom: 2px;
    transition: all .2s;
  }

  .nav-link-item:hover {
    background: rgba(255, 255, 255, .08);
    color: rgba(255, 255, 255, .9);
  }

  .nav-link-item.active {
    background: linear-gradient(135deg, rgba(201, 168, 76, .22), rgba(201, 168, 76, .08));
    color: var(--emas-muda);
    border: 1px solid rgba(201, 168, 76, .18);
  }

  .nav-link-item.active i {
    color: var(--emas);
  }

  .nav-link-item i {
    font-size: .9rem;
    width: 18px;
    text-align: center;
    flex-shrink: 0;
  }

  .nav-badge {
    margin-left: auto;
    background: #ef4444;
    color: white;
    font-size: .6rem;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
  }

  .sidebar-footer {
    padding: 12px;
    border-top: 1px solid rgba(255, 255, 255, .07);
  }

  .admin-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgba(255, 255, 255, .05);
    margin-bottom: 8px;
  }

  .admin-ava {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--hijau-muda), var(--emas));
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: .85rem;
    color: white;
    flex-shrink: 0;
  }

  .admin-name {
    font-size: .8rem;
    font-weight: 600;
    color: white;
  }

  .admin-role {
    font-size: .66rem;
    color: rgba(255, 255, 255, .32);
  }

  .logout-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    border-radius: 10px;
    color: rgba(255, 255, 255, .4);
    text-decoration: none;
    font-size: .8rem;
    transition: all .2s;
  }

  .logout-link:hover {
    background: rgba(239, 68, 68, .15);
    color: #fca5a5;
  }

  /* OVERLAY */
  .sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .5);
    z-index: 199;
    backdrop-filter: blur(2px);
  }

  .sidebar-overlay.show {
    display: block;
  }

  /* MAIN */
  .main {
    margin-left: var(--sidebar-w);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }

  /* TOPBAR */
  .topbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: white;
    padding: 0 28px;
    height: 62px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #E8EDF2;
    box-shadow: 0 1px 0 rgba(0, 0, 0, .04);
  }

  .topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .hamburger {
    display: none;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #f5f7fa;
    border: none;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    color: #555;
    transition: background .2s;
  }

  .hamburger:hover {
    background: #e8edf2;
  }

  .page-heading {
    font-size: .95rem;
    font-weight: 700;
    color: #1a1a1a;
  }

  .topbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .topbar-date {
    font-size: .75rem;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .icon-btn {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: #f5f7fa;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    text-decoration: none;
    transition: all .2s;
    position: relative;
  }

  .icon-btn:hover {
    background: #e8edf2;
    color: #1a1a1a;
  }

  /* ROLE INDICATOR — perbedaan visual Staff Admin vs Superadmin */
  .sidebar.role-superadmin {
    background: linear-gradient(180deg, #2E1A47 0%, #4C2E7A 100%);
  }

  .sidebar.role-superadmin .nav-link-item.active {
    background: linear-gradient(135deg, rgba(168, 130, 234, .28), rgba(168, 130, 234, .1));
    color: #D9C6FA;
    border: 1px solid rgba(168, 130, 234, .25);
  }

  .sidebar.role-superadmin .nav-link-item.active i {
    color: #B794F6;
  }

  .sidebar.role-superadmin .brand-icon {
    background: linear-gradient(135deg, #A882EA, #D9C6FA);
    color: #2E1A47;
  }

  .sidebar.role-superadmin .brand-name span {
    color: #B794F6;
  }

  .sidebar.role-superadmin .admin-ava {
    background: linear-gradient(135deg, #6B46C1, #A882EA);
  }

  .role-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .6rem;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 3px 9px;
    border-radius: 20px;
    margin-top: 6px;
  }

  .role-tag.staff {
    background: rgba(201, 168, 76, .18);
    color: var(--emas-muda);
    border: 1px solid rgba(201, 168, 76, .3);
  }

  .role-tag.superadmin {
    background: rgba(168, 130, 234, .2);
    color: #D9C6FA;
    border: 1px solid rgba(168, 130, 234, .35);
  }

  .topbar.role-superadmin {
    border-bottom: 2px solid #A882EA;
  }

  .topbar-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .62rem;
    font-weight: 800;
    letter-spacing: .5px;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 20px;
    margin-left: 10px;
  }

  .topbar-role-badge.staff {
    background: #FEF3C7;
    color: #92400E;
  }

  .topbar-role-badge.superadmin {
    background: #EDE4FB;
    color: #5B21B6;
  }

  .badge-readonly {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: .72rem;
    font-weight: 700;
    color: #5B21B6;
    background: #EDE4FB;
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid #D9C6FA;
  }

  /* STAT SUMMARY — pengganti tombol "Tambah" di halaman superadmin */
  .stat-summary-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
  }

  .stat-mini-card {
    background: white;
    border: 1px solid #EDE4FB;
    border-left: 4px solid #A882EA;
    border-radius: 12px;
    padding: 10px 16px;
    min-width: 130px;
    box-shadow: 0 1px 3px rgba(107, 70, 193, .06);
  }

  .stat-mini-value {
    font-family: var(--font-display);
    font-size: 1.35rem;
    font-weight: 700;
    color: #5B21B6;
    line-height: 1.1;
  }

  .stat-mini-label {
    font-size: .68rem;
    color: #8b7ba8;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-top: 2px;
  }

  /* PENGATURAN — tampilan laporan untuk superadmin (bukan form) */
  .settings-view-item {
    padding: 10px 0;
    border-bottom: 1px dashed #eef0f3;
  }

  .settings-view-item:last-child {
    border-bottom: none;
  }

  .settings-view-label {
    font-size: .72rem;
    color: #94a3b8;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 3px;
  }

  .settings-view-value {
    font-size: .88rem;
    color: #1a1a1a;
    font-weight: 500;
    white-space: pre-line;
  }

  .notif-dot {
    position: absolute;
    top: 7px;
    right: 7px;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #ef4444;
    border: 1.5px solid white;
  }

  /* CONTENT */
  .content {
    padding: 24px 28px;
    flex: 1;
  }

  .page-title {
    font-family: var(--font-display);
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--hijau-tua);
    margin: 0;
  }

  /* SECTION CARD */
  .section-card {
    background: white;
    border-radius: 14px;
    border: 1px solid #E8EDF2;
    overflow: hidden;
  }

  .sc-header {
    padding: 14px 20px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .sc-title {
    font-size: .87rem;
    font-weight: 700;
    color: #1a1a1a;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .sc-link {
    font-size: .77rem;
    color: var(--hijau);
    text-decoration: none;
    font-weight: 600;
  }

  /* TABLE */
  .admin-table {
    width: 100%;
    border-collapse: collapse;
  }

  .admin-table thead th {
    padding: 10px 18px;
    text-align: left;
    font-size: .7rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .5px;
    background: #FAFBFC;
    white-space: nowrap;
  }

  .admin-table tbody td {
    padding: 13px 18px;
    font-size: .83rem;
    border-top: 1px solid #F8FAFC;
    color: #374151;
    vertical-align: middle;
  }

  .admin-table tbody tr:hover td {
    background: #FAFBFC;
  }

  .kode-booking {
    font-family: monospace;
    font-size: .78rem;
    background: #F1F5F9;
    padding: 3px 8px;
    border-radius: 6px;
    color: #475569;
    font-weight: 600;
  }

  .empty-cell {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
    font-size: .85rem;
  }

  /* BUTTONS */
  .btn-admin {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 20px;
    border-radius: 50px;
    font-size: .83rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all .2s;
    font-family: var(--font-body);
    white-space: nowrap;
  }

  .btn-admin-sm {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: .75rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all .2s;
    font-family: var(--font-body);
    white-space: nowrap;
  }

  .btn-hijau {
    background: var(--hijau);
    color: white !important;
  }

  .btn-hijau:hover {
    background: var(--hijau-tua);
  }

  .btn-emas {
    background: var(--emas);
    color: var(--hijau-tua) !important;
  }

  .btn-emas:hover {
    background: var(--emas-muda);
  }

  .btn-merah {
    background: #fee2e2;
    color: #dc2626 !important;
  }

  .btn-merah:hover {
    background: #fecaca;
  }

  .btn-abu {
    background: #f3f4f6;
    color: #555 !important;
  }

  .btn-abu:hover {
    background: #e5e7eb;
  }

  .btn-biru {
    background: #dbeafe;
    color: #2563eb !important;
  }

  .btn-biru:hover {
    background: #bfdbfe;
  }

  /* FILTER TABS */
  .filter-tabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
  }

  .filter-tab {
    padding: 6px 14px;
    border-radius: 50px;
    font-size: .78rem;
    font-weight: 500;
    text-decoration: none;
    color: #64748b;
    background: #f1f5f9;
    border: 1.5px solid transparent;
    transition: all .2s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }

  .filter-tab:hover {
    background: #e2e8f0;
    color: #1a1a1a;
  }

  .filter-tab.active {
    background: var(--hijau);
    color: white !important;
    border-color: var(--hijau);
  }

  .tab-count {
    background: rgba(0, 0, 0, .15);
    border-radius: 10px;
    padding: 1px 6px;
    font-size: .65rem;
  }

  .filter-tab.active .tab-count {
    background: rgba(255, 255, 255, .25);
  }

  /* FORM */
  .form-control,
  .form-select,
  .form-control-sm,
  .form-select-sm {
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: .87rem;
    font-family: var(--font-body);
    transition: border-color .2s, box-shadow .2s;
    color: #1a1a1a;
  }

  .form-control:focus,
  .form-select:focus {
    border-color: var(--hijau);
    box-shadow: 0 0 0 3px rgba(27, 107, 58, .1);
    outline: none;
  }

  .form-label {
    font-size: .8rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 5px;
  }

  .form-text {
    font-size: .73rem;
    color: #94a3b8;
    margin-top: 3px;
  }

  .input-group-text {
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    font-size: .85rem;
  }

  /* DETAIL PAGE */
  .detail-label {
    font-size: .7rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 3px;
  }

  .detail-val {
    font-size: .88rem;
    font-weight: 500;
    color: #1a1a1a;
  }

  .detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #F8FAFC;
    font-size: .83rem;
  }

  .detail-row:last-child {
    border-bottom: none;
  }

  .detail-label-sm {
    color: #94a3b8;
    font-size: .82rem;
  }

  .detail-row-sm {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 7px;
    color: #64748b;
    font-size: .82rem;
  }

  /* BADGE */
  .badge-custom {
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
    display: inline-block;
  }

  /* RESPONSIVE */
  @media (max-width:900px) {
    .sidebar {
      transform: translateX(-100%);
    }

    .sidebar.open {
      transform: translateX(0);
    }

    .main {
      margin-left: 0;
    }

    .hamburger {
      display: flex;
    }

    .topbar {
      padding: 0 16px;
    }

    .content {
      padding: 16px;
    }
  }

  @media (max-width:600px) {
    .topbar-date {
      display: none;
    }
  }
</style>