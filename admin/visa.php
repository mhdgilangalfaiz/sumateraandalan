<?php
// admin/visa.php
require_once __DIR__ . '/../config/config.php';
cekAdmin(); // admin & superadmin sama-sama boleh lihat; superadmin dibatasi read-only di bawah
$pageTitle = 'Visa Umrah';

$statusAktif = ['dokumen_belum_lengkap', 'dokumen_lengkap', 'menunggu_pembayaran', 'submitted', 'in_process', 'perlu_revisi'];
$statusSelesai = ['approved', 'rejected'];
$semuaStatus = array_merge($statusAktif, $statusSelesai);

// HAPUS
if (isset($_GET['hapus'])) {
    blockIfSuperadmin(BASE_URL . '/admin/visa.php');
    $v = db()->fetchOne("SELECT * FROM visa_applications WHERE id=?", 'i', [(int) $_GET['hapus']]);
    if ($v) {
        // Bersihkan file dokumen yang pernah diupload, kalau ada
        foreach (['dokumen_paspor', 'dokumen_foto', 'dokumen_vaksin'] as $f) {
            if (!empty($v[$f]))
                deleteFile($v[$f]);
        }
        db()->execute("DELETE FROM visa_applications WHERE id=?", 'i', [(int) $_GET['hapus']]);
    }
    redirect(BASE_URL . '/admin/visa.php', 'Data visa berhasil dihapus.', 'sukses');
}

// SIMPAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    blockIfSuperadmin(BASE_URL . '/admin/visa.php');
    checkCsrf();
    $id = (int) ($_POST['id'] ?? 0);
    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    $nama_jamaah = sanitize($_POST['nama_jamaah'] ?? '');
    $no_paspor = sanitize($_POST['no_paspor'] ?? '');
    $status_baru = sanitize($_POST['status'] ?? 'dokumen_belum_lengkap');
    $catatan_revisi = sanitize($_POST['catatan_revisi'] ?? '');
    $provider_reference_id = sanitize($_POST['provider_reference_id'] ?? '');
    $deadline_keberangkatan = $_POST['deadline_keberangkatan'] ?: null;

    $errors = [];
    $existing = $id ? db()->fetchOne("SELECT * FROM visa_applications WHERE id=?", 'i', [$id]) : null;
    $booking = db()->fetchOne("SELECT * FROM booking WHERE id=?", 'i', [$booking_id]);

    if (!$booking) {
        $errors[] = 'Booking terkait tidak ditemukan.';
    }

    // ATURAN BISNIS: visa (baik bundling paket maupun standalone dari agen)
    // baru boleh dikirim ke provider setelah booking-nya berstatus LUNAS.
    // Tidak ada opsi DP untuk visa standalone — harus full payment.
    $statusButuhLunas = ['submitted', 'in_process', 'approved'];
    if ($booking && in_array($status_baru, $statusButuhLunas) && $booking['status'] !== 'lunas') {
        $errors[] = 'Tidak bisa mengubah ke status "' . $status_baru . '" karena booking ini belum berstatus Lunas. Visa hanya boleh diproses setelah pembayaran penuh diterima.';
    }

    // Handle upload dokumen (opsional saat edit, boleh nyusul belakangan)
    $dokumen = ['dokumen_paspor' => $existing['dokumen_paspor'] ?? null, 'dokumen_foto' => $existing['dokumen_foto'] ?? null, 'dokumen_vaksin' => $existing['dokumen_vaksin'] ?? null];
    foreach (array_keys($dokumen) as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $up = uploadFile($_FILES[$field], 'visa', ALLOWED_DOC_TYPES);
            if ($up['success']) {
                if (!empty($dokumen[$field]))
                    deleteFile($dokumen[$field]); // buang file lama
                $dokumen[$field] = $up['filename'];
            } else {
                $errors[] = $field . ': ' . $up['message'];
            }
        }
    }

    if (empty($errors)) {
        if ($id) {
            db()->execute(
                "UPDATE visa_applications SET booking_id=?, nama_jamaah=?, no_paspor=?, dokumen_paspor=?, dokumen_foto=?, dokumen_vaksin=?, status=?, catatan_revisi=?, provider_reference_id=?, deadline_keberangkatan=?, submitted_at=IF(? = 'submitted' AND submitted_at IS NULL, NOW(), submitted_at), approved_at=IF(? = 'approved' AND approved_at IS NULL, NOW(), approved_at) WHERE id=?",
                'isssssssssssi',
                [$booking_id, $nama_jamaah, $no_paspor, $dokumen['dokumen_paspor'], $dokumen['dokumen_foto'], $dokumen['dokumen_vaksin'], $status_baru, $catatan_revisi, $provider_reference_id, $deadline_keberangkatan, $status_baru, $status_baru, $id]
            );
            redirect(BASE_URL . '/admin/visa.php', 'Data visa berhasil diperbarui.', 'sukses');
        } else {
            db()->insert(
                "INSERT INTO visa_applications (booking_id, nama_jamaah, no_paspor, dokumen_paspor, dokumen_foto, dokumen_vaksin, status, catatan_revisi, provider_reference_id, deadline_keberangkatan) VALUES (?,?,?,?,?,?,?,?,?,?)",
                'isssssssss',
                [$booking_id, $nama_jamaah, $no_paspor, $dokumen['dokumen_paspor'], $dokumen['dokumen_foto'], $dokumen['dokumen_vaksin'], $status_baru, $catatan_revisi, $provider_reference_id, $deadline_keberangkatan]
            );
            redirect(BASE_URL . '/admin/visa.php', 'Data visa berhasil ditambahkan.', 'sukses');
        }
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = db()->fetchOne("SELECT * FROM visa_applications WHERE id=?", 'i', [(int) $_GET['edit']]);
}

// Filter status (opsional lewat ?status=)
$filterStatus = sanitize($_GET['status'] ?? '');
$where = '';
$params = [];
$types = '';
if ($filterStatus && in_array($filterStatus, $semuaStatus)) {
    $where = 'WHERE va.status = ?';
    $types = 's';
    $params = [$filterStatus];
}

// Default urutan: yang statusnya masih aktif & deadline paling dekat di atas,
// yang sudah selesai (approved/rejected) di bagian bawah.
$visas = db()->fetchAll(
    "SELECT va.*, b.kode_booking, b.nama_pemesan, b.status AS booking_status
     FROM visa_applications va
     LEFT JOIN booking b ON va.booking_id = b.id
     $where
     ORDER BY
        (va.status IN ('approved','rejected')) ASC,
        va.deadline_keberangkatan IS NULL ASC,
        va.deadline_keberangkatan ASC",
    $types,
    $params
);

$bookings = db()->fetchAll(
    "SELECT id, kode_booking, nama_pemesan FROM booking WHERE status != 'cancelled' ORDER BY created_at DESC LIMIT 200",
    '',
    []
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visa Umrah — Admin SAH Travel</title>
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

            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <h4 class="page-title">Visa Umrah</h4>
                <?php if (!isSuperadmin()): ?>
                <button class="btn-admin btn-hijau"
                    onclick="document.getElementById('formPanel').scrollIntoView({behavior:'smooth'})">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Pengajuan Visa
                </button>
                <?php endif; ?>
            </div>

            <?php if (isSuperadmin()):
                $totalVisaAktif = 0; $totalVisaApproved = 0; $totalVisaMepet = 0;
                foreach ($visas as $vv) {
                    if (!in_array($vv['status'], ['approved', 'rejected'])) $totalVisaAktif++;
                    if ($vv['status'] === 'approved') $totalVisaApproved++;
                    if ($vv['deadline_keberangkatan'] && !in_array($vv['status'], ['approved', 'rejected'])) {
                        $sisaHariVV = (strtotime($vv['deadline_keberangkatan']) - time()) / 86400;
                        if ($sisaHariVV <= 7) $totalVisaMepet++;
                    }
                }
            ?>
            <div class="stat-summary-row mb-3">
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= count($visas) ?></div>
                    <div class="stat-mini-label">Total Pengajuan</div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= $totalVisaAktif ?></div>
                    <div class="stat-mini-label">Masih Diproses</div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= $totalVisaApproved ?></div>
                    <div class="stat-mini-label">Disetujui</div>
                </div>
                <div class="stat-mini-card" style="<?= $totalVisaMepet ? 'border-left-color:#ef4444' : '' ?>">
                    <div class="stat-mini-value" style="<?= $totalVisaMepet ? 'color:#ef4444' : '' ?>"><?= $totalVisaMepet ?></div>
                    <div class="stat-mini-label">Mendekati Deadline</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- FILTER STATUS -->
            <div class="d-flex gap-2 flex-wrap mb-3">
                <a href="visa.php" class="btn-admin-sm <?= $filterStatus === '' ? 'btn-hijau' : 'btn-abu' ?>">Semua</a>
                <?php foreach ($statusAktif as $s): ?>
                    <a href="visa.php?status=<?= $s ?>"
                        class="btn-admin-sm <?= $filterStatus === $s ? 'btn-hijau' : 'btn-abu' ?>"><?= ucwords(str_replace('_', ' ', $s)) ?></a>
                <?php endforeach; ?>
            </div>

            <!-- TABEL -->
            <div class="section-card mb-4">
                <div class="sc-header">
                    <div class="sc-title"><i class="bi bi-file-earmark-text me-2" style="color:var(--emas)"></i><?= isSuperadmin() ? 'Laporan' : 'Daftar' ?>
                        Pengajuan Visa</div>
                </div>
                <div style="overflow-x:auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Jamaah</th>
                                <th>No. Booking</th>
                                <th>Status Bayar</th>
                                <th>Deadline Berangkat</th>
                                <th>Status Visa</th>
                                <?php if (!isSuperadmin()): ?><th>Aksi</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($visas)): ?>
                                <?php foreach ($visas as $v):
                                    $mepet = false;
                                    if ($v['deadline_keberangkatan'] && !in_array($v['status'], ['approved', 'rejected'])) {
                                        $sisaHari = (strtotime($v['deadline_keberangkatan']) - time()) / 86400;
                                        $mepet = $sisaHari <= 7;
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;font-size:.85rem">
                                                <?= htmlspecialchars($v['nama_jamaah']) ?>
                                            </div>
                                            <?php if ($v['no_paspor']): ?>
                                                <div style="font-size:.72rem;color:#94a3b8">Paspor:
                                                    <?= htmlspecialchars($v['no_paspor']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size:.83rem">
                                            <?= htmlspecialchars($v['kode_booking'] ?? '-') ?>
                                            <div style="font-size:.72rem;color:#94a3b8">
                                                <?= htmlspecialchars($v['nama_pemesan'] ?? '') ?>
                                            </div>
                                        </td>
                                        <td><?= statusBadge($v['booking_status'] ?? '-') ?></td>
                                        <td style="font-size:.83rem;<?= $mepet ? 'color:#ef4444;font-weight:700' : '' ?>">
                                            <?= $v['deadline_keberangkatan'] ? tglIndo($v['deadline_keberangkatan']) : '<span style="color:#d1d5db">–</span>' ?>
                                            <?php if ($mepet): ?><div style="font-size:.7rem"><i
                                                        class="bi bi-exclamation-triangle-fill"></i> Mendekati deadline</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= statusBadge($v['status']) ?></td>
                                        <?php if (!isSuperadmin()): ?>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="visa.php?edit=<?= $v['id'] ?>" class="btn-admin-sm btn-biru"><i
                                                        class="bi bi-pencil"></i> Edit</a>
                                                <a href="visa.php?hapus=<?= $v['id'] ?>" class="btn-admin-sm btn-merah"
                                                    onclick="return confirm('Hapus pengajuan visa ini?')"><i
                                                        class="bi bi-trash"></i></a>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= isSuperadmin() ? 5 : 6 ?>" class="empty-cell">
                                        <i class="bi bi-file-earmark-x d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                                        Belum ada pengajuan visa
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
                        <?= $edit ? 'Edit Pengajuan Visa' : 'Tambah Pengajuan Visa' ?>
                    </div>
                    <?php if ($edit): ?>
                        <a href="visa.php" class="btn-admin-sm btn-abu"><i class="bi bi-x"></i> Batal</a>
                    <?php endif; ?>
                </div>
                <div style="padding:22px">
                    <?php if ($edit && ($edit['status'] !== 'approved' && $edit['status'] !== 'rejected')):
                        $bookingCek = db()->fetchOne("SELECT status FROM booking WHERE id=?", 'i', [$edit['booking_id']]);
                        if ($bookingCek && $bookingCek['status'] !== 'lunas'):
                            ?>
                            <div class="alert alert-warning py-2 px-3" style="font-size:.83rem">
                                <i class="bi bi-info-circle-fill me-1"></i> Booking ini masih berstatus
                                "<?= $bookingCek['status'] ?>". Visa tidak akan bisa dikirim ke provider (status
                                submitted/in_process/approved) sampai booking berstatus Lunas.
                            </div>
                        <?php endif; endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Booking Terkait <span class="text-danger">*</span></label>
                                <select name="booking_id" class="form-select" required>
                                    <option value="">-- Pilih Booking --</option>
                                    <?php foreach ($bookings as $b): ?>
                                        <option value="<?= $b['id'] ?>" <?= ($edit['booking_id'] ?? '') == $b['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($b['kode_booking'] . ' — ' . $b['nama_pemesan']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Jamaah <span class="text-danger">*</span></label>
                                <input type="text" name="nama_jamaah" class="form-control"
                                    value="<?= htmlspecialchars($edit['nama_jamaah'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">No. Paspor</label>
                                <input type="text" name="no_paspor" class="form-control"
                                    value="<?= htmlspecialchars($edit['no_paspor'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Deadline Keberangkatan</label>
                                <input type="date" name="deadline_keberangkatan" class="form-control"
                                    value="<?= $edit['deadline_keberangkatan'] ?? '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status Visa</label>
                                <select name="status" class="form-select">
                                    <?php foreach ($semuaStatus as $s): ?>
                                        <option value="<?= $s ?>" <?= ($edit['status'] ?? 'dokumen_belum_lengkap') === $s ? 'selected' : '' ?>>
                                            <?= ucwords(str_replace('_', ' ', $s)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Dokumen Paspor</label>
                                <input type="file" name="dokumen_paspor" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                <?php if (!empty($edit['dokumen_paspor'])): ?>
                                    <div style="font-size:.72rem;margin-top:4px"><a
                                            href="<?= UPLOAD_URL . $edit['dokumen_paspor'] ?>" target="_blank"><i
                                                class="bi bi-paperclip"></i> Lihat file saat ini</a></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Dokumen Foto</label>
                                <input type="file" name="dokumen_foto" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                <?php if (!empty($edit['dokumen_foto'])): ?>
                                    <div style="font-size:.72rem;margin-top:4px"><a
                                            href="<?= UPLOAD_URL . $edit['dokumen_foto'] ?>" target="_blank"><i
                                                class="bi bi-paperclip"></i> Lihat file saat ini</a></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Dokumen Vaksin Meningitis</label>
                                <input type="file" name="dokumen_vaksin" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                <?php if (!empty($edit['dokumen_vaksin'])): ?>
                                    <div style="font-size:.72rem;margin-top:4px"><a
                                            href="<?= UPLOAD_URL . $edit['dokumen_vaksin'] ?>" target="_blank"><i
                                                class="bi bi-paperclip"></i> Lihat file saat ini</a></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Referensi Provider (opsional)</label>
                                <input type="text" name="provider_reference_id" class="form-control"
                                    value="<?= htmlspecialchars($edit['provider_reference_id'] ?? '') ?>"
                                    placeholder="Nomor referensi dari provider visa">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catatan Revisi (kalau ada)</label>
                                <input type="text" name="catatan_revisi" class="form-control"
                                    value="<?= htmlspecialchars($edit['catatan_revisi'] ?? '') ?>"
                                    placeholder="Alasan revisi dari provider, ditampilkan ke customer">
                            </div>

                            <div class="col-12 pt-1">
                                <button type="submit" class="btn-admin btn-hijau">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    <?= $edit ? 'Simpan Perubahan' : 'Tambah Pengajuan' ?>
                                </button>
                                <?php if ($edit): ?><a href="visa.php" class="btn-admin btn-abu ms-2">Batal</a>
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
    <?php if ($edit): ?>
        <script>document.getElementById('formPanel').scrollIntoView();</script>
    <?php endif; ?>
</body>

</html>