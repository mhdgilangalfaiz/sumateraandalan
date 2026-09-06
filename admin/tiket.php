<?php
// admin/tiket.php
// Mendukung tiket dengan transit (banyak segmen per arah) maupun direct flight,
// serta perjalanan PP (pulang-pergi) atau one-way.
require_once __DIR__ . '/../config/config.php';
cekAdmin(); // admin & superadmin sama-sama boleh lihat; superadmin dibatasi read-only di bawah
$pageTitle = 'Tiket Pesawat';

/**
 * Bersihkan array baris segmen dari form. Baris yang benar-benar kosong
 * (tidak diisi sama sekali) dibuang, supaya baris kosong sisa dari JS
 * "Tambah Segmen" yang tidak jadi dipakai tidak ikut tersimpan.
 */
function bersihkanSegmen(array $rows): array
{
    $hasil = [];
    foreach ($rows as $r) {
        $asal = sanitize($r['kota_asal'] ?? '');
        $tujuan = sanitize($r['kota_tujuan'] ?? '');
        $tanggal = sanitize($r['tanggal'] ?? '');
        $jam = sanitize($r['jam'] ?? '');
        $kode = sanitize($r['kode_penerbangan'] ?? '');
        $ket = sanitize($r['keterangan'] ?? '');
        if ($asal === '' && $tujuan === '' && $tanggal === '' && $jam === '' && $kode === '' && $ket === '') {
            continue; // baris kosong total, skip
        }
        $hasil[] = [
            'kota_asal' => $asal,
            'kota_tujuan' => $tujuan,
            'tanggal' => $tanggal,
            'jam' => $jam ?: null,
            'kode_penerbangan' => $kode ?: null,
            'keterangan' => $ket ?: null,
        ];
    }
    return $hasil;
}

// HAPUS
if (isset($_GET['hapus'])) {
    blockIfSuperadmin(BASE_URL . '/admin/tiket.php');
    // tiket_segmen ikut terhapus otomatis (ON DELETE CASCADE)
    db()->execute("DELETE FROM tiket_pesawat WHERE id=?", 'i', [(int) $_GET['hapus']]);
    redirect(BASE_URL . '/admin/tiket.php', 'Tiket berhasil dihapus.', 'sukses');
}

// SIMPAN (tambah/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    blockIfSuperadmin(BASE_URL . '/admin/tiket.php');
    checkCsrf();

    $id = (int) ($_POST['id'] ?? 0);
    $maskapai = sanitize($_POST['maskapai'] ?? '');
    $tipe = ($_POST['tipe_perjalanan'] ?? 'pp') === 'oneway' ? 'oneway' : 'pp';
    $kelas = sanitize($_POST['kelas'] ?? 'economy');
    $harga = (float) str_replace(['.', ','], ['', '.'], $_POST['harga'] ?? '0');
    $kuota = (int) ($_POST['kuota'] ?? 1);
    $keterangan = sanitize($_POST['keterangan'] ?? '');
    $status = sanitize($_POST['status'] ?? 'aktif');

    $segBerangkat = bersihkanSegmen($_POST['segmen_berangkat'] ?? []);
    $segPulang = $tipe === 'pp' ? bersihkanSegmen($_POST['segmen_pulang'] ?? []) : [];

    $errors = [];
    if (!$maskapai) {
        $errors[] = 'Maskapai wajib diisi.';
    }
    if ($harga <= 0) {
        $errors[] = 'Harga harus lebih dari 0.';
    }
    if ($kuota < 1) {
        $errors[] = 'Kuota minimal 1.';
    }
    if (empty($segBerangkat)) {
        $errors[] = 'Minimal 1 segmen keberangkatan wajib diisi.';
    }
    foreach ($segBerangkat as $s) {
        if (!$s['kota_asal'] || !$s['kota_tujuan'] || !$s['tanggal']) {
            $errors[] = 'Setiap segmen keberangkatan wajib punya kota asal, kota tujuan, dan tanggal.';
            break;
        }
    }
    if ($tipe === 'pp') {
        if (empty($segPulang)) {
            $errors[] = 'Perjalanan PP wajib mengisi minimal 1 segmen kepulangan.';
        }
        foreach ($segPulang as $s) {
            if (!$s['kota_asal'] || !$s['kota_tujuan'] || !$s['tanggal']) {
                $errors[] = 'Setiap segmen kepulangan wajib punya kota asal, kota tujuan, dan tanggal.';
                break;
            }
        }
    }

    if (empty($errors)) {
        db()->beginTransaction();

        if ($id) {
            db()->execute(
                "UPDATE tiket_pesawat SET maskapai=?, tipe_perjalanan=?, kelas=?, harga=?, kuota=?, keterangan=?, status=? WHERE id=?",
                'sssdissi',
                [$maskapai, $tipe, $kelas, $harga, $kuota, $keterangan, $status, $id]
            );
            $tiketId = $id;
            db()->execute("DELETE FROM tiket_segmen WHERE tiket_id=?", 'i', [$tiketId]);
        } else {
            $tiketId = db()->insert(
                "INSERT INTO tiket_pesawat (maskapai, tipe_perjalanan, kelas, harga, kuota, terisi, keterangan, status) VALUES (?,?,?,?,?,0,?,?)",
                'sssdiss',
                [$maskapai, $tipe, $kelas, $harga, $kuota, $keterangan, $status]
            );
        }

        $urutan = 1;
        foreach ($segBerangkat as $s) {
            db()->execute(
                "INSERT INTO tiket_segmen (tiket_id, arah, urutan, kota_asal, kota_tujuan, tanggal, jam, kode_penerbangan, keterangan) VALUES (?,?,?,?,?,?,?,?,?)",
                'isissssss',
                [$tiketId, 'berangkat', $urutan, $s['kota_asal'], $s['kota_tujuan'], $s['tanggal'], $s['jam'], $s['kode_penerbangan'], $s['keterangan']]
            );
            $urutan++;
        }
        $urutan = 1;
        foreach ($segPulang as $s) {
            db()->execute(
                "INSERT INTO tiket_segmen (tiket_id, arah, urutan, kota_asal, kota_tujuan, tanggal, jam, kode_penerbangan, keterangan) VALUES (?,?,?,?,?,?,?,?,?)",
                'isissssss',
                [$tiketId, 'pulang', $urutan, $s['kota_asal'], $s['kota_tujuan'], $s['tanggal'], $s['jam'], $s['kode_penerbangan'], $s['keterangan']]
            );
            $urutan++;
        }

        db()->commit();
        redirect(BASE_URL . '/admin/tiket.php', $id ? 'Tiket pesawat berhasil diperbarui.' : 'Tiket pesawat berhasil ditambahkan.', 'sukses');
    } else {
        setFlash('error', implode(' ', $errors));
    }
}

// Ambil semua tiket + kelompokkan segmen per tiket_id (biar 1 query pendek, tidak N+1)
$tikets = db()->fetchAll("SELECT * FROM tiket_pesawat ORDER BY created_at DESC", '', []);
$semuaSegmen = db()->fetchAll("SELECT * FROM tiket_segmen ORDER BY tiket_id, arah, urutan", '', []);
$segmenPerTiket = [];
foreach ($semuaSegmen as $sg) {
    $segmenPerTiket[$sg['tiket_id']][$sg['arah']][] = $sg;
}

// Data untuk mode EDIT
$edit = null;
$editSegBerangkat = [];
$editSegPulang = [];
if (isset($_GET['edit'])) {
    $edit = db()->fetchOne("SELECT * FROM tiket_pesawat WHERE id=?", 'i', [(int) $_GET['edit']]);
    if ($edit) {
        $editSegBerangkat = $segmenPerTiket[$edit['id']]['berangkat'] ?? [];
        $editSegPulang = $segmenPerTiket[$edit['id']]['pulang'] ?? [];
    }
}
if (empty($editSegBerangkat)) {
    $editSegBerangkat = [['kota_asal' => '', 'kota_tujuan' => '', 'tanggal' => '', 'jam' => '', 'kode_penerbangan' => '', 'keterangan' => '']];
}
if (empty($editSegPulang)) {
    $editSegPulang = [['kota_asal' => '', 'kota_tujuan' => '', 'tanggal' => '', 'jam' => '', 'kode_penerbangan' => '', 'keterangan' => '']];
}

/** Ringkasan rute jadi teks "KNO -> KUL -> JED" dari daftar segmen */
function ringkasRute(array $segmen): string
{
    if (empty($segmen)) {
        return '–';
    }
    $kota = [$segmen[0]['kota_asal']];
    foreach ($segmen as $s) {
        $kota[] = $s['kota_tujuan'];
    }
    return implode(' → ', array_map('htmlspecialchars', $kota));
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Pesawat — Admin SAH Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <?php include __DIR__ . '/inc/admin-style.php'; ?>
    <style>
        .segmen-row {
            display: grid;
            grid-template-columns: 1fr 1fr .8fr .7fr .8fr 1fr auto;
            gap: 10px;
            align-items: end;
            padding: 12px;
            background: #F8FAFC;
            border-radius: 10px;
            margin-bottom: 8px
        }

        .segmen-row label {
            font-size: .68rem;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 3px;
            display: block
        }

        .segmen-row .form-control,
        .segmen-row .form-select {
            font-size: .82rem;
            padding: 6px 10px
        }

        .btn-hapus-segmen {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center
        }

        .btn-tambah-segmen {
            font-size: .78rem;
            font-weight: 600;
            color: var(--hijau);
            background: none;
            border: 1px dashed var(--hijau);
            border-radius: 8px;
            padding: 8px 14px;
            width: 100%
        }

        .rute-transit {
            font-size: .78rem;
            color: #94a3b8;
            margin-top: 2px
        }

        @media (max-width: 900px) {
            .segmen-row {
                grid-template-columns: 1fr 1fr
            }
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
                <h4 class="page-title">Tiket Pesawat</h4>
                <?php if (!isSuperadmin()): ?>
                <button class="btn-admin btn-hijau"
                    onclick="document.getElementById('formPanel').scrollIntoView({behavior:'smooth'})">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Tiket
                </button>
                <?php endif; ?>
            </div>

            <?php if (isSuperadmin()):
                $totalKuotaT = 0; $totalTerisiT = 0; $totalAktifT = 0;
                foreach ($tikets as $tt) {
                    $totalKuotaT += $tt['kuota'];
                    $totalTerisiT += $tt['terisi'];
                    if ($tt['status'] === 'aktif') $totalAktifT++;
                }
            ?>
            <div class="stat-summary-row mb-4">
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= count($tikets) ?></div>
                    <div class="stat-mini-label">Total Tiket</div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= $totalAktifT ?></div>
                    <div class="stat-mini-label">Tiket Aktif</div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= $totalTerisiT ?>/<?= $totalKuotaT ?></div>
                    <div class="stat-mini-label">Kuota Terisi</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- TABEL -->
            <div class="section-card mb-4">
                <div class="sc-header">
                    <div class="sc-title"><i class="bi bi-airplane-fill me-2" style="color:var(--emas)"></i><?= isSuperadmin() ? 'Laporan Tiket Pesawat' : 'Daftar Tiket Pesawat' ?>
                    </div>
                </div>
                <div style="overflow-x:auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Maskapai</th>
                                <th>Rute Berangkat</th>
                                <th>Rute Pulang</th>
                                <th>Kelas</th>
                                <th>Harga</th>
                                <th>Kuota</th>
                                <th>Status</th>
                                <?php if (!isSuperadmin()): ?><th>Aksi</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($tikets)): ?>
                                <?php foreach ($tikets as $t):
                                    $sisa = $t['kuota'] - $t['terisi'];
                                    $persen = $t['kuota'] > 0 ? round(($t['terisi'] / $t['kuota']) * 100) : 0;
                                    $sb = $segmenPerTiket[$t['id']]['berangkat'] ?? [];
                                    $sp = $segmenPerTiket[$t['id']]['pulang'] ?? [];
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;font-size:.85rem">
                                                <?= htmlspecialchars($t['maskapai']) ?>
                                            </div>
                                            <div style="font-size:.72rem;color:#94a3b8">
                                                <?= $t['tipe_perjalanan'] === 'pp' ? 'Pulang-Pergi' : 'One-way' ?>
                                            </div>
                                        </td>
                                        <td style="font-size:.83rem">
                                            <?= ringkasRute($sb) ?>
                                            <?php if (count($sb) > 1): ?>
                                                <div class="rute-transit"><?= count($sb) - 1 ?>x transit</div>
                                            <?php endif; ?>
                                            <?php if (!empty($sb)): ?>
                                                <div style="font-size:.72rem;color:#94a3b8"><?= tglIndo($sb[0]['tanggal']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size:.83rem">
                                            <?php if ($t['tipe_perjalanan'] === 'pp' && !empty($sp)): ?>
                                                <?= ringkasRute($sp) ?>
                                                <?php if (count($sp) > 1): ?>
                                                    <div class="rute-transit"><?= count($sp) - 1 ?>x transit</div>
                                                <?php endif; ?>
                                                <div style="font-size:.72rem;color:#94a3b8"><?= tglIndo($sp[0]['tanggal']) ?></div>
                                            <?php else: ?>
                                                <span style="color:#d1d5db">– one-way –</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size:.8rem"><?= ucfirst($t['kelas']) ?></td>
                                        <td style="font-weight:700;color:var(--hijau);font-size:.85rem">
                                            <?= rupiah($t['harga']) ?>
                                        </td>
                                        <td>
                                            <div style="font-size:.83rem">
                                                <?= $t['terisi'] ?>/<?= $t['kuota'] ?> <span style="color:#94a3b8">(sisa
                                                    <?= $sisa ?>)</span>
                                            </div>
                                            <div
                                                style="height:4px;background:#f1f5f9;border-radius:2px;margin-top:3px;width:60px">
                                                <div
                                                    style="height:100%;width:<?= $persen ?>%;background:<?= $persen >= 90 ? '#ef4444' : ($persen >= 70 ? '#f59e0b' : 'var(--hijau)') ?>;border-radius:2px">
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= statusBadge($t['status']) ?></td>
                                        <?php if (!isSuperadmin()): ?>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="tiket.php?edit=<?= $t['id'] ?>#formPanel" class="btn-admin-sm btn-biru"><i
                                                        class="bi bi-pencil"></i> Edit</a>
                                                <a href="tiket.php?hapus=<?= $t['id'] ?>" class="btn-admin-sm btn-merah"
                                                    onclick="return confirm('Hapus tiket <?= htmlspecialchars(addslashes($t['maskapai'])) ?> ini?')"><i
                                                        class="bi bi-trash"></i></a>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= isSuperadmin() ? 7 : 8 ?>" class="empty-cell">
                                        <i class="bi bi-airplane d-block mb-2" style="font-size:2rem;opacity:.3"></i>Belum
                                        ada tiket pesawat
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FORM (disembunyikan untuk superadmin, read-only) -->
            <?php if (!isSuperadmin()): ?>
            <div class="section-card" id="formPanel">
                <div class="sc-header">
                    <div class="sc-title">
                        <i class="bi bi-<?= $edit ? 'pencil-square' : 'plus-circle-fill' ?> me-2"
                            style="color:var(--emas)"></i>
                        <?= $edit ? 'Edit Tiket Pesawat' : 'Tambah Tiket Pesawat Baru' ?>
                    </div>
                    <?php if ($edit): ?>
                        <a href="tiket.php" class="btn-admin-sm btn-abu"><i class="bi bi-x"></i> Batal</a>
                    <?php endif; ?>
                </div>
                <div style="padding:22px">
                    <form method="POST" id="formTiket">
                    <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

                        <div class="row g-3 mb-2">
                            <div class="col-md-4">
                                <label class="form-label">Maskapai <span class="text-danger">*</span></label>
                                <input type="text" name="maskapai" class="form-control"
                                    value="<?= htmlspecialchars($edit['maskapai'] ?? '') ?>"
                                    placeholder="Contoh: Batik Air Malaysia, Saudia Airlines" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipe Perjalanan</label>
                                <select name="tipe_perjalanan" id="tipePerjalanan" class="form-select">
                                    <option value="pp" <?= ($edit['tipe_perjalanan'] ?? 'pp') === 'pp' ? 'selected' : '' ?>>
                                        Pulang - Pergi (PP)</option>
                                    <option value="oneway" <?= ($edit['tipe_perjalanan'] ?? '') === 'oneway' ? 'selected' : '' ?>>
                                        One-way</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Kelas</label>
                                <select name="kelas" class="form-select">
                                    <?php foreach (['economy' => 'Economy', 'business' => 'Business', 'first' => 'First Class'] as $k => $l): ?>
                                        <option value="<?= $k ?>" <?= ($edit['kelas'] ?? 'economy') === $k ? 'selected' : '' ?>>
                                            <?= $l ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach (['aktif', 'penuh', 'batal', 'selesai'] as $s): ?>
                                        <option value="<?= $s ?>" <?= ($edit['status'] ?? 'aktif') === $s ? 'selected' : '' ?>>
                                            <?= ucfirst($s) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Harga per Kursi <span class="text-danger">*</span></label>
                                <input type="text" name="harga" class="form-control"
                                    value="<?= $edit ? number_format($edit['harga'], 0, ',', '.') : '' ?>"
                                    placeholder="Contoh: 12.500.000" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kuota Kursi</label>
                                <input type="number" name="kuota" class="form-control" min="1"
                                    value="<?= $edit['kuota'] ?? 1 ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Keterangan Umum</label>
                                <input type="text" name="keterangan" class="form-control"
                                    value="<?= htmlspecialchars($edit['keterangan'] ?? '') ?>"
                                    placeholder="Contoh: Promo Ramadan, bagasi 30kg">
                            </div>
                        </div>

                        <hr class="my-3">

                        <!-- SEGMEN BERANGKAT -->
                        <label class="form-label d-flex align-items-center gap-2">
                            <i class="bi bi-airplane-fill" style="color:var(--hijau)"></i> Segmen Keberangkatan
                            <span class="text-danger">*</span>
                        </label>
                        <div style="font-size:.75rem;color:#94a3b8;margin-bottom:8px">
                            Isi 1 baris untuk direct flight. Tambah baris lagi kalau ada transit (contoh: KNO → KUL,
                            lalu KUL → JED).
                        </div>
                        <div id="wrapBerangkat">
                            <?php foreach ($editSegBerangkat as $i => $s): ?>
                                <?= renderSegmenRow('segmen_berangkat', $i, $s) ?>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn-tambah-segmen mb-4" onclick="tambahSegmen('berangkat')">
                            <i class="bi bi-plus-lg"></i> Tambah Segmen / Transit (Berangkat)
                        </button>

                        <!-- SEGMEN PULANG -->
                        <div id="blokPulang" style="<?= ($edit['tipe_perjalanan'] ?? 'pp') === 'oneway' ? 'display:none' : '' ?>">
                            <label class="form-label d-flex align-items-center gap-2">
                                <i class="bi bi-airplane-fill" style="color:var(--hijau);transform:scaleX(-1)"></i>
                                Segmen Kepulangan <span class="text-danger">*</span>
                            </label>
                            <div id="wrapPulang">
                                <?php foreach ($editSegPulang as $i => $s): ?>
                                    <?= renderSegmenRow('segmen_pulang', $i, $s) ?>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn-tambah-segmen mb-3" onclick="tambahSegmen('pulang')">
                                <i class="bi bi-plus-lg"></i> Tambah Segmen / Transit (Pulang)
                            </button>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="btn-admin btn-hijau">
                                <i class="bi bi-check-circle-fill me-1"></i>
                                <?= $edit ? 'Simpan Perubahan' : 'Tambah Tiket' ?>
                            </button>
                            <?php if ($edit): ?><a href="tiket.php" class="btn-admin btn-abu ms-2">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- TEMPLATE baris segmen (dipakai JS untuk clone baris baru) -->
    <template id="templateSegmen">
        <?= renderSegmenRow('__NAMA__', '__IDX__', ['kota_asal' => '', 'kota_tujuan' => '', 'tanggal' => '', 'jam' => '', 'kode_penerbangan' => '', 'keterangan' => '']) ?>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('tipePerjalanan')?.addEventListener('change', function () {
            document.getElementById('blokPulang').style.display = this.value === 'oneway' ? 'none' : '';
        });

        let idxBerangkat = <?= count($editSegBerangkat) ?>;
        let idxPulang = <?= count($editSegPulang) ?>;

        function tambahSegmen(arah) {
            const tpl = document.getElementById('templateSegmen').innerHTML;
            const nama = arah === 'berangkat' ? 'segmen_berangkat' : 'segmen_pulang';
            const idx = arah === 'berangkat' ? idxBerangkat++ : idxPulang++;
            const html = tpl.replaceAll('__NAMA__', nama).replaceAll('__IDX__', idx);
            const wrap = document.getElementById(arah === 'berangkat' ? 'wrapBerangkat' : 'wrapPulang');
            wrap.insertAdjacentHTML('beforeend', html);
        }

        function hapusSegmen(btn) {
            const row = btn.closest('.segmen-row');
            const wrap = row.parentElement;
            if (wrap.querySelectorAll('.segmen-row').length > 1) {
                row.remove();
            } else {
                // baris terakhir, kosongkan saja isinya daripada dihapus total
                row.querySelectorAll('input').forEach(inp => inp.value = '');
            }
        }
    </script>
    <?php if ($edit): ?>
        <script>document.getElementById('formPanel').scrollIntoView();</script>
    <?php endif; ?>
</body>

</html>
<?php
/**
 * Render 1 baris form segmen. Dipanggil dari PHP (baris awal) maupun
 * di-clone dari <template> lewat JS (baris tambahan).
 */
function renderSegmenRow(string $nama, $idx, array $s): string
{
    ob_start();
    ?>
    <div class="segmen-row">
        <div>
            <label>Kota Asal</label>
            <input type="text" name="<?= $nama ?>[<?= $idx ?>][kota_asal]" class="form-control"
                value="<?= htmlspecialchars($s['kota_asal']) ?>" placeholder="Contoh: Medan (KNO)">
        </div>
        <div>
            <label>Kota Tujuan</label>
            <input type="text" name="<?= $nama ?>[<?= $idx ?>][kota_tujuan]" class="form-control"
                value="<?= htmlspecialchars($s['kota_tujuan']) ?>" placeholder="Contoh: Kuala Lumpur (KUL)">
        </div>
        <div>
            <label>Tanggal</label>
            <input type="date" name="<?= $nama ?>[<?= $idx ?>][tanggal]" class="form-control"
                value="<?= htmlspecialchars($s['tanggal']) ?>">
        </div>
        <div>
            <label>Jam</label>
            <input type="time" name="<?= $nama ?>[<?= $idx ?>][jam]" class="form-control"
                value="<?= htmlspecialchars($s['jam'] ?? '') ?>">
        </div>
        <div>
            <label>Kode Penerbangan</label>
            <input type="text" name="<?= $nama ?>[<?= $idx ?>][kode_penerbangan]" class="form-control"
                value="<?= htmlspecialchars($s['kode_penerbangan'] ?? '') ?>" placeholder="OD-172">
        </div>
        <div>
            <label>Keterangan</label>
            <input type="text" name="<?= $nama ?>[<?= $idx ?>][keterangan]" class="form-control"
                value="<?= htmlspecialchars($s['keterangan'] ?? '') ?>" placeholder="Transit 3 jam">
        </div>
        <button type="button" class="btn-hapus-segmen" onclick="hapusSegmen(this)" title="Hapus baris ini">
            <i class="bi bi-trash"></i>
        </button>
    </div>
    <?php
    return ob_get_clean();
}