<?php
// admin/paket.php
// Digabung dengan jadwal.php: 1 paket bisa punya banyak jadwal keberangkatan,
// ditampilkan sebagai accordion (expand/collapse) di bawah tiap baris paket.
require_once __DIR__ . '/../config/config.php';
cekAdmin();
$pageTitle = 'Paket Umrah';

// ================== HAPUS PAKET ==================
if (isset($_GET['hapus'])) {
    blockIfSuperadmin(BASE_URL . '/admin/paket.php');
    $id = (int) $_GET['hapus'];
    $paket = db()->fetchOne("SELECT * FROM paket_umrah WHERE id = ?", 'i', [$id]);
    if ($paket) {
        if ($paket['banner'])
            deleteFile($paket['banner']);
        // Semua jadwal keberangkatan milik paket ini ikut dihapus (tidak ada
        // FK cascade di database, jadi dibersihkan manual di sini)
        db()->execute("DELETE FROM jadwal WHERE paket_id = ?", 'i', [$id]);
        db()->execute("DELETE FROM paket_umrah WHERE id = ?", 'i', [$id]);
        redirect(BASE_URL . '/admin/paket.php', 'Paket beserta seluruh jadwalnya berhasil dihapus.', 'sukses');
    }
    redirect(BASE_URL . '/admin/paket.php', 'Paket tidak ditemukan.', 'error');
}

// ================== HAPUS JADWAL ==================
if (isset($_GET['hapus_jadwal'])) {
    blockIfSuperadmin(BASE_URL . '/admin/paket.php');
    $jid = (int) $_GET['hapus_jadwal'];
    $j = db()->fetchOne("SELECT paket_id FROM jadwal WHERE id = ?", 'i', [$jid]);
    db()->execute("DELETE FROM jadwal WHERE id = ?", 'i', [$jid]);
    $bukaId = $j['paket_id'] ?? 0;
    redirect(BASE_URL . "/admin/paket.php?buka=$bukaId#paket-$bukaId", 'Jadwal berhasil dihapus.', 'sukses');
}

// ================== SIMPAN (PAKET / JADWAL) ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    blockIfSuperadmin(BASE_URL . '/admin/paket.php');
    checkCsrf();
    $formType = $_POST['form_type'] ?? 'paket';

    // ---------- SIMPAN JADWAL ----------
    if ($formType === 'jadwal') {
        $id = (int) ($_POST['id'] ?? 0);
        $paket_id = (int) ($_POST['paket_id'] ?? 0);
        $tgl_brkt = sanitize($_POST['tanggal_berangkat'] ?? '');
        $tgl_plg = sanitize($_POST['tanggal_pulang'] ?? '');
        $kuota = (int) ($_POST['kuota'] ?? 45);
        $harga_khs = $_POST['harga_khusus'] ? (float) str_replace(['.', ','], ['', '.'], $_POST['harga_khusus']) : null;
        $keterangan = sanitize($_POST['keterangan'] ?? '');
        $status = sanitize($_POST['status'] ?? 'aktif');

        if (!$paket_id) {
            redirect(BASE_URL . '/admin/paket.php', 'Paket tujuan jadwal tidak valid.', 'error');
        }

        if ($id) {
            db()->execute(
                "UPDATE jadwal SET paket_id=?,tanggal_berangkat=?,tanggal_pulang=?,kuota=?,harga_khusus=?,keterangan=?,status=? WHERE id=?",
                'issidssi',
                [$paket_id, $tgl_brkt, $tgl_plg, $kuota, $harga_khs, $keterangan, $status, $id]
            );
            redirect(BASE_URL . "/admin/paket.php?buka=$paket_id#paket-$paket_id", 'Jadwal berhasil diperbarui.', 'sukses');
        } else {
            db()->insert(
                "INSERT INTO jadwal (paket_id,tanggal_berangkat,tanggal_pulang,kuota,terisi,harga_khusus,keterangan,status) VALUES (?,?,?,?,0,?,?,?)",
                'issidss',
                [$paket_id, $tgl_brkt, $tgl_plg, $kuota, $harga_khs, $keterangan, $status]
            );
            redirect(BASE_URL . "/admin/paket.php?buka=$paket_id#paket-$paket_id", 'Jadwal berhasil ditambahkan.', 'sukses');
        }
    }

    // ---------- SIMPAN PAKET ----------
    $id = (int) ($_POST['id'] ?? 0);
    $nama = sanitize($_POST['nama_paket'] ?? '');
    $slug = makeSlug($nama) . ($id ? '' : '-' . time());
    $kategori = sanitize($_POST['kategori'] ?? 'reguler');
    $harga = (float) str_replace(['.', ','], ['', '.'], $_POST['harga'] ?? 0);
    $harga_coret = $_POST['harga_coret'] ? (float) str_replace(['.', ','], ['', '.'], $_POST['harga_coret']) : null;
    $durasi = (int) ($_POST['durasi'] ?? 0);
    $maskapai = sanitize($_POST['maskapai'] ?? '');
    $hotel_m = sanitize($_POST['hotel_mekkah'] ?? '');
    $bintang_m = (int) ($_POST['bintang_mekkah'] ?? 4);
    $hotel_mad = sanitize($_POST['hotel_madinah'] ?? '');
    $bintang_mad = (int) ($_POST['bintang_madinah'] ?? 4);
    $kuota = (int) ($_POST['kuota'] ?? 45);
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');
    $include = sanitize($_POST['include'] ?? '');
    $exclude = sanitize($_POST['exclude'] ?? '');
    $status = sanitize($_POST['status'] ?? 'aktif');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $urutan = (int) ($_POST['urutan'] ?? 0);

    $fasilitas_raw = array_filter(array_map('trim', explode("\n", $_POST['fasilitas'] ?? '')));
    $fasilitas = json_encode(array_values($fasilitas_raw));

    $banner = sanitize($_POST['banner_lama'] ?? '');
    if (!empty($_FILES['banner']['name'])) {
        $up = uploadFile($_FILES['banner'], 'paket');
        if ($up['success']) {
            if ($banner)
                deleteFile($banner);
            $banner = $up['filename'];
        }
    }

    if ($id) {
        db()->execute(
            "UPDATE paket_umrah SET nama_paket=?,slug=?,kategori=?,harga=?,harga_coret=?,durasi=?,
             maskapai=?,hotel_mekkah=?,bintang_mekkah=?,hotel_madinah=?,bintang_madinah=?,
             kuota=?,deskripsi=?,fasilitas=?,`include`=?,`exclude`=?,banner=?,status=?,featured=?,urutan=?,
             updated_at=NOW() WHERE id=?",
            'sssddisisisiisssssiii',
            [
                $nama,
                $slug,
                $kategori,
                $harga,
                $harga_coret,
                $durasi,
                $maskapai,
                $hotel_m,
                $bintang_m,
                $hotel_mad,
                $bintang_mad,
                $kuota,
                $deskripsi,
                $fasilitas,
                $include,
                $exclude,
                $banner,
                $status,
                $featured,
                $urutan,
                $id
            ]
        );
        redirect(BASE_URL . '/admin/paket.php', 'Paket berhasil diperbarui.', 'sukses');
    } else {
        db()->insert(
            "INSERT INTO paket_umrah (nama_paket,slug,kategori,harga,harga_coret,durasi,maskapai,
             hotel_mekkah,bintang_mekkah,hotel_madinah,bintang_madinah,kuota,sisa_kuota,deskripsi,
             fasilitas,`include`,`exclude`,banner,status,featured,urutan,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            'sssddisisisiisssssiii',
            [
                $nama,
                $slug,
                $kategori,
                $harga,
                $harga_coret,
                $durasi,
                $maskapai,
                $hotel_m,
                $bintang_m,
                $hotel_mad,
                $bintang_mad,
                $kuota,
                $kuota,
                $deskripsi,
                $fasilitas,
                $include,
                $exclude,
                $banner,
                $status,
                $featured,
                $urutan,
                $_SESSION['admin_id']
            ]
        );
        redirect(BASE_URL . '/admin/paket.php', 'Paket berhasil ditambahkan.', 'sukses');
    }
}

// ================== DATA UNTUK TAMPILAN ==================
$edit = null; // mode edit info PAKET (form besar di bawah)
if (isset($_GET['edit'])) {
    $edit = db()->fetchOne("SELECT * FROM paket_umrah WHERE id = ?", 'i', [(int) $_GET['edit']]);
}

$editJadwal = null; // mode edit 1 baris JADWAL (form kecil di dalam accordion)
if (isset($_GET['edit_jadwal'])) {
    $editJadwal = db()->fetchOne("SELECT * FROM jadwal WHERE id = ?", 'i', [(int) $_GET['edit_jadwal']]);
}

// Paket mana yang accordion-nya harus otomatis terbuka
$bukaPaketId = (int) ($_GET['buka'] ?? $_GET['tambah_jadwal'] ?? ($editJadwal['paket_id'] ?? 0));

$pakets = db()->fetchAll("SELECT * FROM paket_umrah ORDER BY urutan ASC, id DESC", '', []);

// Ambil SEMUA jadwal sekaligus lalu dikelompokkan per paket_id (hindari query N+1)
$semuaJadwal = db()->fetchAll("SELECT * FROM jadwal ORDER BY tanggal_berangkat ASC", '', []);
$jadwalPerPaket = [];
foreach ($semuaJadwal as $j) {
    $jadwalPerPaket[$j['paket_id']][] = $j;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paket Umrah — Admin SAH Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <?php include __DIR__ . '/inc/admin-style.php'; ?>
    <style>
        .paket-img-thumb {
            width: 60px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            background: #f1f5f9;
        }

        .badge-custom {
            display: inline-block;
            padding: .2rem .65rem;
            border-radius: 50px;
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* ===== ACCORDION PAKET + JADWAL ===== */
        .paket-card {
            background: #fff;
            border: 1px solid #eef0f3;
            border-radius: 14px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .paket-head {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            cursor: pointer;
            flex-wrap: wrap;
        }

        .paket-head:hover {
            background: #FAFBFC;
        }

        .paket-chevron {
            font-size: 1rem;
            color: #94a3b8;
            transition: transform .15s;
            flex-shrink: 0;
        }

        .paket-head.is-open .paket-chevron {
            transform: rotate(90deg);
        }

        .paket-info {
            flex: 1;
            min-width: 200px;
        }

        .paket-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }

        .paket-body {
            border-top: 1px solid #eef0f3;
            padding: 16px 18px;
            background: #FAFBFC;
        }

        .jadwal-mini-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .jadwal-mini-table th {
            text-align: left;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #94a3b8;
            padding: 6px 10px;
            border-bottom: 1px solid #eef0f3;
        }

        .jadwal-mini-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eef0f3;
            vertical-align: middle;
        }

        .jadwal-form-inline {
            background: #fff;
            border: 1px dashed #d9e8de;
            border-radius: 10px;
            padding: 16px;
            margin-top: 12px;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/inc/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/inc/topbar.php'; ?>
        <div class="content">
            <?= renderFlash() ?>

            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <h4 class="page-title">Paket Umrah</h4>
                <?php if (!isSuperadmin()): ?>
                    <button class="btn-admin btn-hijau"
                        onclick="document.getElementById('formPanel').scrollIntoView({behavior:'smooth'})">
                        <i class="bi bi-plus-circle-fill"></i> Tambah Paket
                    </button>
                <?php endif; ?>
            </div>

            <?php if (isSuperadmin()):
                $totalAktif = 0; $totalKuota = 0; $totalSisa = 0; $totalFeatured = 0; $totalJadwal = count($semuaJadwal);
                foreach ($pakets as $pp) {
                    if ($pp['status'] === 'aktif') $totalAktif++;
                    $totalKuota += $pp['kuota'];
                    $totalSisa += $pp['sisa_kuota'];
                    if ($pp['featured']) $totalFeatured++;
                }
            ?>
                <div class="stat-summary-row mb-4">
                    <div class="stat-mini-card">
                        <div class="stat-mini-value"><?= count($pakets) ?></div>
                        <div class="stat-mini-label">Total Paket</div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-value"><?= $totalAktif ?></div>
                        <div class="stat-mini-label">Paket Aktif</div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-value"><?= $totalJadwal ?></div>
                        <div class="stat-mini-label">Total Jadwal</div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-value"><?= $totalFeatured ?></div>
                        <div class="stat-mini-label">Featured</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- DAFTAR PAKET (accordion, tiap paket bisa dibuka untuk lihat jadwalnya) -->
            <?php if (empty($pakets)): ?>
                <div class="section-card mb-4">
                    <div class="empty-cell" style="padding:40px">
                        <i class="bi bi-briefcase d-block mb-2" style="font-size:2rem;opacity:.3"></i>Belum ada paket
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($pakets as $p):
                $jadwalList = $jadwalPerPaket[$p['id']] ?? [];
                $isOpen = ($bukaPaketId === (int) $p['id']);
                ?>
                <div class="paket-card" id="paket-<?= $p['id'] ?>">
                    <div class="paket-head <?= $isOpen ? 'is-open' : '' ?>" onclick="toggleAkordeon(<?= $p['id'] ?>)">
                        <i class="bi bi-chevron-right paket-chevron"></i>

                        <?php if ($p['banner']): ?>
                            <img src="<?= UPLOAD_URL . $p['banner'] ?>" class="paket-img-thumb" alt="">
                        <?php else: ?>
                            <div class="paket-img-thumb d-flex align-items-center justify-content-center"
                                style="background:linear-gradient(135deg,var(--hijau-tua),var(--hijau))">
                                <i class="bi bi-building text-white opacity-50"></i>
                            </div>
                        <?php endif; ?>

                        <div class="paket-info">
                            <div style="font-weight:600;font-size:.88rem">
                                <?= htmlspecialchars($p['nama_paket']) ?>
                                <span class="badge-custom" style="background:#e8f5ee;color:var(--hijau);margin-left:4px">
                                    <?= ucfirst($p['kategori']) ?>
                                </span>
                            </div>
                            <div style="font-size:.73rem;color:#94a3b8">
                                <?= $p['durasi'] ?> hari · <?= htmlspecialchars($p['maskapai']) ?> ·
                                <?= count($jadwalList) ?> jadwal keberangkatan
                            </div>
                        </div>

                        <div style="text-align:right;min-width:110px">
                            <div style="font-weight:700;color:var(--hijau);font-size:.85rem">
                                <?= rupiah($p['harga']) ?>
                            </div>
                            <?php if ($p['harga_coret']): ?>
                                <div style="font-size:.7rem;color:#94a3b8;text-decoration:line-through">
                                    <?= rupiah($p['harga_coret']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="min-width:60px"><?= statusBadge($p['status']) ?></div>

                        <div style="min-width:20px">
                            <?php if ($p['featured']): ?>
                                <span style="color:var(--emas)"><i class="bi bi-star-fill"></i></span>
                            <?php else: ?>
                                <span style="color:#d1d5db"><i class="bi bi-star"></i></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!isSuperadmin()): ?>
                            <div class="paket-actions" onclick="event.stopPropagation()">
                                <a href="paket.php?edit=<?= $p['id'] ?>#formPanel" class="btn-admin-sm btn-biru">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="paket.php?hapus=<?= $p['id'] ?>" class="btn-admin-sm btn-merah"
                                    onclick="return confirm('Hapus paket <?= htmlspecialchars(addslashes($p['nama_paket'])) ?> beserta SEMUA jadwalnya?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- BODY: daftar jadwal + form tambah/edit jadwal -->
                    <div class="paket-body" id="body-<?= $p['id'] ?>" style="<?= $isOpen ? '' : 'display:none' ?>">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div style="font-size:.8rem;font-weight:700;color:#475569">
                                <i class="bi bi-calendar3 me-1"></i> Jadwal Keberangkatan (<?= count($jadwalList) ?>)
                            </div>
                        </div>

                        <?php if (!empty($jadwalList)): ?>
                            <div style="overflow-x:auto">
                                <table class="jadwal-mini-table">
                                    <thead>
                                        <tr>
                                            <th>Berangkat</th>
                                            <th>Kembali</th>
                                            <th>Kuota / Terisi</th>
                                            <th>Harga Khusus</th>
                                            <th>Keterangan</th>
                                            <th>Status</th>
                                            <?php if (!isSuperadmin()): ?><th>Aksi</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($jadwalList as $j):
                                            $sisaJ = $j['kuota'] - $j['terisi'];
                                            $persenJ = $j['kuota'] > 0 ? round(($j['terisi'] / $j['kuota']) * 100) : 0;
                                            ?>
                                            <tr>
                                                <td style="font-weight:600"><?= tglIndo($j['tanggal_berangkat']) ?></td>
                                                <td style="color:#64748b"><?= tglIndo($j['tanggal_pulang']) ?></td>
                                                <td>
                                                    <div><?= $j['terisi'] ?>/<?= $j['kuota'] ?>
                                                        <span style="color:#94a3b8">(sisa <?= $sisaJ ?>)</span>
                                                    </div>
                                                    <div style="height:4px;background:#f1f5f9;border-radius:2px;margin-top:3px;width:60px">
                                                        <div style="height:100%;width:<?= $persenJ ?>%;background:<?= $persenJ >= 90 ? '#ef4444' : ($persenJ >= 70 ? '#f59e0b' : 'var(--hijau)') ?>;border-radius:2px">
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= $j['harga_khusus'] ? rupiah($j['harga_khusus']) : '<span style="color:#d1d5db">–</span>' ?></td>
                                                <td style="color:#64748b"><?= htmlspecialchars($j['keterangan'] ?: '–') ?></td>
                                                <td><?= statusBadge($j['status']) ?></td>
                                                <?php if (!isSuperadmin()): ?>
                                                    <td>
                                                        <div class="d-flex gap-1">
                                                            <a href="paket.php?edit_jadwal=<?= $j['id'] ?>&buka=<?= $p['id'] ?>#jadwalform-<?= $p['id'] ?>"
                                                                class="btn-admin-sm btn-biru"><i class="bi bi-pencil"></i></a>
                                                            <a href="paket.php?hapus_jadwal=<?= $j['id'] ?>" class="btn-admin-sm btn-merah"
                                                                onclick="return confirm('Hapus jadwal <?= tglIndo($j['tanggal_berangkat']) ?> ini?')">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div style="font-size:.8rem;color:#94a3b8;padding:10px 0">Belum ada jadwal keberangkatan
                                untuk paket ini.</div>
                        <?php endif; ?>

                        <?php if (!isSuperadmin()):
                            $ej = ($editJadwal && (int) $editJadwal['paket_id'] === (int) $p['id']) ? $editJadwal : null;
                            ?>
                            <div class="jadwal-form-inline" id="jadwalform-<?= $p['id'] ?>">
                                <div style="font-size:.78rem;font-weight:700;color:var(--hijau-tua);margin-bottom:10px">
                                    <i class="bi bi-<?= $ej ? 'pencil-square' : 'plus-circle' ?> me-1"></i>
                                    <?= $ej ? 'Edit Jadwal' : 'Tambah Jadwal Baru' ?>
                                </div>
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="form_type" value="jadwal">
                                    <input type="hidden" name="paket_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="id" value="<?= $ej['id'] ?? 0 ?>">
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <label class="form-label" style="font-size:.72rem">Tgl Berangkat <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="tanggal_berangkat" class="form-control form-control-sm"
                                                value="<?= $ej['tanggal_berangkat'] ?? '' ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" style="font-size:.72rem">Tgl Kembali <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="tanggal_pulang" class="form-control form-control-sm"
                                                value="<?= $ej['tanggal_pulang'] ?? '' ?>" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label" style="font-size:.72rem">Kuota</label>
                                            <input type="number" name="kuota" class="form-control form-control-sm" min="1"
                                                value="<?= $ej['kuota'] ?? $p['kuota'] ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label" style="font-size:.72rem">Status</label>
                                            <select name="status" class="form-select form-select-sm">
                                                <?php foreach (['aktif', 'penuh', 'batal', 'selesai'] as $s): ?>
                                                    <option value="<?= $s ?>" <?= ($ej['status'] ?? 'aktif') === $s ? 'selected' : '' ?>>
                                                        <?= ucfirst($s) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label" style="font-size:.72rem">Harga Khusus</label>
                                            <input type="text" name="harga_khusus" class="form-control form-control-sm"
                                                value="<?= $ej && $ej['harga_khusus'] ? number_format($ej['harga_khusus'], 0, ',', '.') : '' ?>"
                                                placeholder="Opsional">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label" style="font-size:.72rem">Keterangan</label>
                                            <input type="text" name="keterangan" class="form-control form-control-sm"
                                                value="<?= htmlspecialchars($ej['keterangan'] ?? '') ?>"
                                                placeholder="Contoh: Early Bird, Ramadan Spesial">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" class="btn-admin-sm btn-hijau">
                                                <i class="bi bi-check-circle"></i>
                                                <?= $ej ? 'Simpan Perubahan' : 'Tambah Jadwal' ?>
                                            </button>
                                            <?php if ($ej): ?>
                                                <a href="paket.php?buka=<?= $p['id'] ?>#paket-<?= $p['id'] ?>"
                                                    class="btn-admin-sm btn-abu ms-1">Batal</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- FORM TAMBAH/EDIT PAKET (disembunyikan untuk superadmin, read-only) -->
            <?php if (!isSuperadmin()): ?>
                <div class="section-card mt-4" id="formPanel">
                    <div class="sc-header">
                        <div class="sc-title">
                            <i class="bi bi-<?= $edit ? 'pencil-square' : 'plus-circle-fill' ?> me-2"
                                style="color:var(--emas)"></i>
                            <?= $edit ? 'Edit Paket: ' . htmlspecialchars($edit['nama_paket']) : 'Tambah Paket Baru' ?>
                        </div>
                        <?php if ($edit): ?>
                            <a href="paket.php" class="btn-admin-sm btn-abu"><i class="bi bi-x"></i> Batal Edit</a>
                        <?php endif; ?>
                    </div>
                    <div style="padding:24px">
                        <form method="POST" enctype="multipart/form-data">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
                            <input type="hidden" name="banner_lama" value="<?= htmlspecialchars($edit['banner'] ?? '') ?>">

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Nama Paket <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_paket" class="form-control"
                                        value="<?= htmlspecialchars($edit['nama_paket'] ?? '') ?>"
                                        placeholder="Contoh: Paket Reguler 9 Hari" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Kategori</label>
                                    <select name="kategori" class="form-select">
                                        <?php foreach (['reguler', 'plus', 'vip', 'furoda', 'promo'] as $k): ?>
                                            <option value="<?= $k ?>" <?= ($edit['kategori'] ?? '') === $k ? 'selected' : '' ?>>
                                                <?= ucfirst($k) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                                    <input type="text" name="harga" class="form-control"
                                        value="<?= $edit ? number_format($edit['harga'], 0, ',', '.') : '' ?>"
                                        placeholder="25.000.000" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Harga Coret (Rp)</label>
                                    <input type="text" name="harga_coret" class="form-control"
                                        value="<?= $edit && $edit['harga_coret'] ? number_format($edit['harga_coret'], 0, ',', '.') : '' ?>"
                                        placeholder="Kosongkan jika tidak ada">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Durasi (Hari)</label>
                                    <input type="number" name="durasi" class="form-control" min="1"
                                        value="<?= $edit['durasi'] ?? 9 ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Kuota</label>
                                    <input type="number" name="kuota" class="form-control" min="1"
                                        value="<?= $edit['kuota'] ?? 45 ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Maskapai</label>
                                    <input type="text" name="maskapai" class="form-control"
                                        value="<?= htmlspecialchars($edit['maskapai'] ?? '') ?>"
                                        placeholder="Garuda Indonesia">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Hotel Mekkah</label>
                                    <input type="text" name="hotel_mekkah" class="form-control"
                                        value="<?= htmlspecialchars($edit['hotel_mekkah'] ?? '') ?>"
                                        placeholder="Grand Zam Zam Tower">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Bintang</label>
                                    <select name="bintang_mekkah" class="form-select">
                                        <?php for ($i = 3; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>" <?= ($edit['bintang_mekkah'] ?? 4) == $i ? 'selected' : '' ?>>
                                                <?= $i ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Hotel Madinah</label>
                                    <input type="text" name="hotel_madinah" class="form-control"
                                        value="<?= htmlspecialchars($edit['hotel_madinah'] ?? '') ?>"
                                        placeholder="Dallah Taibah">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Bintang</label>
                                    <select name="bintang_madinah" class="form-select">
                                        <?php for ($i = 3; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>" <?= ($edit['bintang_madinah'] ?? 4) == $i ? 'selected' : '' ?>>
                                                <?= $i ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea name="deskripsi" class="form-control" rows="3"
                                        placeholder="Deskripsi singkat paket..."><?= htmlspecialchars($edit['deskripsi'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fasilitas <span class="form-text">(satu per
                                            baris)</span></label>
                                    <textarea name="fasilitas" class="form-control" rows="5"
                                        placeholder="Muthawwif berpengalaman&#10;Visa umrah&#10;Asuransi perjalanan"><?= htmlspecialchars(implode("\n", json_decode($edit['fasilitas'] ?? '[]', true) ?: [])) ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sudah Termasuk <span class="form-text">(satu per
                                            baris)</span></label>
                                    <textarea name="include" class="form-control" rows="5"
                                        placeholder="Tiket pesawat PP&#10;Hotel Mekkah 5 malam&#10;Konsumsi 3x sehari"><?= htmlspecialchars($edit['include'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tidak Termasuk <span class="form-text">(satu per
                                            baris)</span></label>
                                    <textarea name="exclude" class="form-control" rows="5"
                                        placeholder="Biaya paspor&#10;Keperluan pribadi&#10;Tips guide"><?= htmlspecialchars($edit['exclude'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Gambar Banner</label>
                                    <?php if (!empty($edit['banner'])): ?>
                                        <div class="mb-2">
                                            <img src="<?= UPLOAD_URL . $edit['banner'] ?>"
                                                style="height:80px;border-radius:8px;object-fit:cover">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="banner" class="form-control" accept="image/*">
                                    <div class="form-text">JPG/PNG/WEBP, maks 5MB</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <?php foreach (['aktif', 'habis', 'nonaktif', 'coming_soon'] as $s): ?>
                                            <option value="<?= $s ?>" <?= ($edit['status'] ?? 'aktif') === $s ? 'selected' : '' ?>>
                                                <?= ucfirst(str_replace('_', ' ', $s)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Urutan</label>
                                    <input type="number" name="urutan" class="form-control" min="0"
                                        value="<?= $edit['urutan'] ?? 0 ?>">
                                </div>
                                <div class="col-md-1 d-flex align-items-end pb-1">
                                    <div class="form-check">
                                        <input type="checkbox" name="featured" id="featured" class="form-check-input"
                                            <?= ($edit['featured'] ?? 0) ? 'checked' : '' ?>>
                                        <label for="featured" class="form-check-label"
                                            style="font-size:.8rem">Featured</label>
                                    </div>
                                </div>

                                <div class="col-12 pt-2">
                                    <button type="submit" class="btn-admin btn-hijau">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        <?= $edit ? 'Simpan Perubahan' : 'Tambah Paket' ?>
                                    </button>
                                    <?php if ($edit): ?>
                                        <a href="paket.php" class="btn-admin btn-abu ms-2">Batal</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleAkordeon(id) {
            const body = document.getElementById('body-' + id);
            const head = body.previousElementSibling;
            const isOpen = body.style.display !== 'none';
            body.style.display = isOpen ? 'none' : 'block';
            head.classList.toggle('is-open', !isOpen);
        }
        <?php if ($bukaPaketId): ?>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('paket-<?= $bukaPaketId ?>')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        <?php endif; ?>
        <?php if ($edit): ?>
            document.getElementById('formPanel')?.scrollIntoView();
        <?php endif; ?>
    </script>
</body>

</html>