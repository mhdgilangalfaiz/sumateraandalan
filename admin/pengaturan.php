<?php
// admin/pengaturan.php
require_once __DIR__ . '/../config/config.php';
cekAdmin();
$pageTitle = 'Pengaturan';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    foreach ($_POST as $key => $val) {
        if ($key === 'submit' || $key === 'csrf_token')
            continue;
        $key = sanitize($key);
        $val = sanitize((string) $val);
        db()->execute(
            "UPDATE pengaturan SET nilai = ? WHERE nama_key = ?",
            'ss',
            [$val, $key]
        );
    }
    redirect(BASE_URL . '/admin/pengaturan.php', 'Pengaturan berhasil disimpan.', 'sukses');
}

$rows = db()->fetchAll("SELECT * FROM pengaturan ORDER BY grup, id", '', []);
$settings = [];
foreach ($rows as $r)
    $settings[$r['nama_key']] = $r['nilai'];

$groups = [
    'umum' => ['label' => 'Informasi Umum', 'icon' => 'bi-building'],
    'kontak' => ['label' => 'Kontak & Lokasi', 'icon' => 'bi-telephone'],
    'pembayaran' => ['label' => 'Rekening Pembayaran', 'icon' => 'bi-credit-card'],
    'legalitas' => ['label' => 'Legalitas', 'icon' => 'bi-patch-check'],
    'seo' => ['label' => 'SEO & Meta', 'icon' => 'bi-search'],
];

$fieldMap = [
    'nama_perusahaan' => ['label' => 'Nama Perusahaan', 'type' => 'text', 'grup' => 'umum'],
    'tagline' => ['label' => 'Tagline', 'type' => 'text', 'grup' => 'umum'],
    'tahun_berdiri' => ['label' => 'Tahun Berdiri', 'type' => 'text', 'grup' => 'umum'],
    'jumlah_jamaah' => ['label' => 'Jumlah Jamaah (statistik)', 'type' => 'text', 'grup' => 'umum'],
    'telepon' => ['label' => 'Telepon', 'type' => 'text', 'grup' => 'kontak'],
    'whatsapp' => ['label' => 'No WhatsApp (tanpa +)', 'type' => 'text', 'grup' => 'kontak', 'help' => 'Contoh: 6281360000000'],
    'email' => ['label' => 'Email', 'type' => 'email', 'grup' => 'kontak'],
    'alamat' => ['label' => 'Alamat Lengkap', 'type' => 'textarea', 'grup' => 'kontak'],
    'google_maps' => ['label' => 'Link Google Maps', 'type' => 'text', 'grup' => 'kontak'],
    'no_rekening_bca' => ['label' => 'No Rekening BCA', 'type' => 'text', 'grup' => 'pembayaran'],
    'nama_rekening_bca' => ['label' => 'Atas Nama BCA', 'type' => 'text', 'grup' => 'pembayaran'],
    'no_rekening_bni' => ['label' => 'No Rekening BNI', 'type' => 'text', 'grup' => 'pembayaran'],
    'nama_rekening_bni' => ['label' => 'Atas Nama BNI', 'type' => 'text', 'grup' => 'pembayaran'],
    'dp_minimum_persen' => ['label' => 'DP Minimum (%)', 'type' => 'number', 'grup' => 'pembayaran'],
    'izin_ppiu' => ['label' => 'No Izin PPIU', 'type' => 'text', 'grup' => 'legalitas'],
    'no_siup' => ['label' => 'No SIUP', 'type' => 'text', 'grup' => 'legalitas'],
    'no_nib' => ['label' => 'No NIB', 'type' => 'text', 'grup' => 'legalitas'],
    'meta_title' => ['label' => 'Meta Title', 'type' => 'text', 'grup' => 'seo'],
    'meta_desc' => ['label' => 'Meta Description', 'type' => 'textarea', 'grup' => 'seo'],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan — Admin SAH Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <?php include __DIR__ . '/inc/admin-style.php'; ?>
</head>

<body>
    <?php include __DIR__ . '/inc/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/inc/topbar.php'; ?>
        <div class="content">
            <?= renderFlash() ?>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="page-title">Pengaturan Website</h4>
            </div>

            <form method="POST">
            <?= csrfField() ?>
                <div class="row g-4">
                    <?php foreach ($groups as $grupKey => $grupInfo): ?>
                        <div class="col-12">
                            <div class="section-card">
                                <div class="sc-header">
                                    <div class="sc-title">
                                        <i class="bi <?= $grupInfo['icon'] ?> me-2" style="color:var(--emas)"></i>
                                        <?= $grupInfo['label'] ?>
                                    </div>
                                </div>
                                <div style="padding:22px">
                                    <div class="row g-3">
                                        <?php foreach ($fieldMap as $key => $field):
                                            if ($field['grup'] !== $grupKey)
                                                continue;
                                            $val = $settings[$key] ?? '';
                                            ?>
                                            <div class="col-md-<?= $field['type'] === 'textarea' ? '12' : '6' ?>">
                                                <label class="form-label">
                                                    <?= $field['label'] ?>
                                                </label>
                                                <?php if ($field['type'] === 'textarea'): ?>
                                                    <textarea name="<?= $key ?>" class="form-control"
                                                        rows="3"><?= htmlspecialchars($val) ?></textarea>
                                                <?php else: ?>
                                                    <input type="<?= $field['type'] ?>" name="<?= $key ?>" class="form-control"
                                                        value="<?= htmlspecialchars($val) ?>">
                                                <?php endif; ?>
                                                <?php if (!empty($field['help'])): ?>
                                                    <div class="form-text">
                                                        <?= $field['help'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="col-12">
                        <button type="submit" name="submit" class="btn-admin btn-hijau">
                            <i class="bi bi-check-circle-fill me-2"></i>Simpan Semua Pengaturan
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>