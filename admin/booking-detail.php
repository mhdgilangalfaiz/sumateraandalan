<?php
// admin/booking-detail.php
require_once __DIR__ . '/../config/config.php';
cekAdmin();

$id = (int) ($_GET['id'] ?? 0);
if (!$id)
  redirect(BASE_URL . '/admin/booking.php');

$booking = db()->fetchOne(
  "SELECT b.*, pu.nama_paket, pu.kategori, pu.durasi, pu.maskapai,
            pu.hotel_mekkah, pu.bintang_mekkah, pu.hotel_madinah, pu.bintang_madinah,
            j.tanggal_berangkat, j.tanggal_pulang
     FROM booking b
     LEFT JOIN paket_umrah pu ON b.paket_id = pu.id
     LEFT JOIN jadwal j ON b.jadwal_id = j.id
     WHERE b.id = ?",
  'i',
  [$id]
);
if (!$booking)
  redirect(BASE_URL . '/admin/booking.php', 'Booking tidak ditemukan.', 'error');

$pembayaran = db()->fetchAll(
  "SELECT * FROM pembayaran WHERE booking_id = ? ORDER BY created_at DESC",
  'i',
  [$id]
);

$total_terbayar = 0;
foreach ($pembayaran as $py) {
  if ($py['status'] === 'verified')
    $total_terbayar += $py['jumlah'];
}

// PROSES AKSI
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  blockIfSuperadmin(BASE_URL . '/admin/booking-detail.php?id=' . $id);
  checkCsrf();
  $aksi = $_POST['aksi'] ?? '';

  if ($aksi === 'ubah_status') {
    $status_baru = sanitize($_POST['status_baru'] ?? '');
    $catatan_admin = sanitize($_POST['catatan_admin'] ?? '');
    $allowed = ['pending', 'confirmed', 'dp_paid', 'lunas', 'berangkat', 'selesai', 'cancelled'];
    if (in_array($status_baru, $allowed)) {
      $status_lama = $booking['status'];

      db()->beginTransaction();
      try {
        db()->execute(
          "UPDATE booking SET status = ?, catatan_admin = ?, updated_at = NOW() WHERE id = ?",
          'ssi',
          [$status_baru, $catatan_admin, $id]
        );

        // Booking baru dibatalkan -> kembalikan kuota yang tadinya terpakai
        if ($status_baru === 'cancelled' && $status_lama !== 'cancelled') {
          db()->execute("UPDATE paket_umrah SET sisa_kuota = sisa_kuota + ? WHERE id = ?", 'ii', [$booking['jumlah_jamaah'], $booking['paket_id']]);
          if ($booking['jadwal_id']) {
            db()->execute("UPDATE jadwal SET terisi = terisi - ? WHERE id = ?", 'ii', [$booking['jumlah_jamaah'], $booking['jadwal_id']]);
          }
        }

        // Booking yang tadinya cancelled diaktifkan lagi -> kurangi kuota lagi,
        // tapi cek dulu kuotanya masih cukup (bisa saja sudah diambil booking lain)
        if ($status_lama === 'cancelled' && $status_baru !== 'cancelled') {
          $cekKuota = db()->fetchOne("SELECT sisa_kuota FROM paket_umrah WHERE id = ? FOR UPDATE", 'i', [$booking['paket_id']]);
          if (!$cekKuota || $cekKuota['sisa_kuota'] < $booking['jumlah_jamaah']) {
            throw new Exception('Tidak bisa mengaktifkan kembali: kuota paket ini sudah tidak mencukupi.');
          }
          db()->execute("UPDATE paket_umrah SET sisa_kuota = sisa_kuota - ? WHERE id = ?", 'ii', [$booking['jumlah_jamaah'], $booking['paket_id']]);
          if ($booking['jadwal_id']) {
            db()->execute("UPDATE jadwal SET terisi = terisi + ? WHERE id = ?", 'ii', [$booking['jumlah_jamaah'], $booking['jadwal_id']]);
          }
        }

        db()->commit();
        redirect("booking-detail.php?id=$id", 'Status booking berhasil diperbarui.', 'sukses');
      } catch (Exception $e) {
        db()->rollback();
        redirect("booking-detail.php?id=$id", $e->getMessage(), 'error');
      }
    }
  }

  if ($aksi === 'verifikasi_bayar') {
    $bayar_id = (int) ($_POST['bayar_id'] ?? 0);
    if ($bayar_id) {
      db()->execute(
        "UPDATE pembayaran SET status = 'verified', verified_by = ?, verified_at = NOW() WHERE id = ? AND booking_id = ?",
        'iii',
        [$_SESSION['admin_id'], $bayar_id, $id]
      );
      // Update status booking otomatis
      $total_v = (float) db()->fetchOne(
        "SELECT COALESCE(SUM(jumlah),0) as t FROM pembayaran WHERE booking_id = ? AND status = 'verified'",
        'i',
        [$id]
      )['t'];
      if ($total_v >= $booking['total_harga']) {
        db()->execute("UPDATE booking SET status = 'lunas' WHERE id = ?", 'i', [$id]);
      } elseif ($total_v >= $booking['dp_amount']) {
        db()->execute("UPDATE booking SET status = 'dp_paid' WHERE id = ?", 'i', [$id]);
      }
      redirect("booking-detail.php?id=$id", 'Pembayaran berhasil diverifikasi.', 'sukses');
    }
  }

  if ($aksi === 'tolak_bayar') {
    $bayar_id = (int) ($_POST['bayar_id'] ?? 0);
    $alasan = sanitize($_POST['alasan'] ?? '');
    if ($bayar_id) {
      db()->execute(
        "UPDATE pembayaran SET status = 'rejected', catatan_admin = ? WHERE id = ? AND booking_id = ?",
        'sii',
        [$alasan, $bayar_id, $id]
      );
      redirect("booking-detail.php?id=$id", 'Pembayaran ditolak.', 'error');
    }
  }
}

$dataJamaah = json_decode($booking['data_jamaah'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Booking <?= $booking['kode_booking'] ?> — Admin SAH Travel</title>
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

      <!-- HEADER -->
      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
          <a href="booking.php" style="color:var(--hijau);text-decoration:none;font-size:.85rem">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Booking
          </a>
          <h4 class="page-title mt-1"><?= $booking['kode_booking'] ?></h4>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <?= statusBadge($booking['status']) ?>
          <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $booking['telepon']) ?>?text=<?= urlencode('Assalamu\'alaikum ' . $booking['nama_pemesan'] . ', kami dari SAH Travel ingin mengkonfirmasi booking Anda dengan kode ' . $booking['kode_booking'] . '.') ?>"
            target="_blank" class="btn-admin-sm" style="background:#25D366;color:white">
            <i class="bi bi-whatsapp"></i> WA Jamaah
          </a>
        </div>
      </div>

      <div class="row g-4">

        <!-- KIRI -->
        <div class="col-lg-8">

          <!-- INFO PEMESAN -->
          <div class="section-card mb-4">
            <div class="sc-header">
              <div class="sc-title"><i class="bi bi-person-fill me-2" style="color:var(--emas)"></i>Data Pemesan</div>
            </div>
            <div style="padding:20px">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="detail-label">Nama Pemesan</div>
                  <div class="detail-val"><?= htmlspecialchars($booking['nama_pemesan']) ?></div>
                </div>
                <div class="col-md-6">
                  <div class="detail-label">No. Telepon</div>
                  <div class="detail-val"><?= htmlspecialchars($booking['telepon']) ?></div>
                </div>
                <div class="col-md-6">
                  <div class="detail-label">Email</div>
                  <div class="detail-val"><?= htmlspecialchars($booking['email'] ?: '-') ?></div>
                </div>
                <div class="col-md-6">
                  <div class="detail-label">Jumlah Jamaah</div>
                  <div class="detail-val"><?= $booking['jumlah_jamaah'] ?> Orang</div>
                </div>
                <div class="col-12">
                  <div class="detail-label">Alamat</div>
                  <div class="detail-val"><?= htmlspecialchars($booking['alamat'] ?: '-') ?></div>
                </div>
                <?php if ($booking['catatan']): ?>
                  <div class="col-12">
                    <div class="detail-label">Catatan Jamaah</div>
                    <div class="detail-val"
                      style="background:var(--krem);padding:10px 14px;border-radius:8px;font-size:.85rem">
                      <?= nl2br(htmlspecialchars($booking['catatan'])) ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- DATA JAMAAH -->
          <?php if (!empty($dataJamaah)): ?>
            <div class="section-card mb-4">
              <div class="sc-header">
                <div class="sc-title"><i class="bi bi-people-fill me-2" style="color:var(--emas)"></i>Data Jamaah</div>
              </div>
              <div style="padding:16px 20px">
                <?php foreach ($dataJamaah as $i => $jm): ?>
                  <div style="background:var(--krem);border-radius:10px;padding:12px 16px;margin-bottom:10px">
                    <div style="font-weight:700;font-size:.85rem;color:var(--hijau-tua);margin-bottom:6px">
                      Jamaah ke-<?= $i + 1 ?>     <?= $i === 0 ? '(Pemesan)' : '' ?>
                    </div>
                    <div class="row g-2" style="font-size:.83rem">
                      <div class="col-md-4"><span style="color:#888">Nama:</span>
                        <?= htmlspecialchars($jm['nama'] ?? '-') ?></div>
                      <div class="col-md-4"><span style="color:#888">KTP:</span> <?= htmlspecialchars($jm['ktp'] ?? '-') ?>
                      </div>
                      <div class="col-md-4"><span style="color:#888">Paspor:</span>
                        <?= htmlspecialchars($jm['paspor'] ?? '-') ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- RIWAYAT PEMBAYARAN -->
          <div class="section-card mb-4">
            <div class="sc-header">
              <div class="sc-title"><i class="bi bi-credit-card-fill me-2" style="color:var(--emas)"></i>Riwayat
                Pembayaran</div>
            </div>
            <?php if (!empty($pembayaran)): ?>
              <div style="overflow-x:auto">
                <table class="admin-table">
                  <thead>
                    <tr>
                      <th>Tanggal</th>
                      <th>Jenis</th>
                      <th>Jumlah</th>
                      <th>Metode</th>
                      <th>Bukti</th>
                      <th>Status</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($pembayaran as $py): ?>
                      <tr>
                        <td style="font-size:.78rem"><?= tglIndo($py['created_at']) ?></td>
                        <td><?= ucfirst($py['jenis']) ?></td>
                        <td style="font-weight:700;color:var(--hijau)"><?= rupiah($py['jumlah']) ?></td>
                        <td style="font-size:.82rem">
                          <?= htmlspecialchars($py['bank'] ? $py['bank'] . ' - ' . $py['metode'] : ($py['metode'] ?? '-')) ?>
                        </td>
                        <td>
                          <?php if ($py['bukti_bayar']): ?>
                            <a href="<?= UPLOAD_URL . $py['bukti_bayar'] ?>" target="_blank" class="btn-admin-sm btn-abu">
                              <i class="bi bi-image"></i> Lihat
                            </a>
                          <?php else: ?>-<?php endif; ?>
                        </td>
                        <td>
                          <?php
                          $badgePy = ['pending' => ['#92400e', '#fef3c7', 'Menunggu'], 'verified' => ['#14532d', '#bbf7d0', 'Terverifikasi'], 'rejected' => ['#991b1b', '#fee2e2', 'Ditolak']];
                          [$c, $bg, $l] = $badgePy[$py['status']] ?? ['#555', '#eee', $py['status']];
                          echo "<span style='background:$bg;color:$c;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700'>$l</span>";
                          ?>
                        </td>
                        <td>
                          <?php if ($py['status'] === 'pending' && !isSuperadmin()): ?>
                            <form method="POST" style="display:inline">
                              <?= csrfField() ?>
                              <input type="hidden" name="aksi" value="verifikasi_bayar">
                              <input type="hidden" name="bayar_id" value="<?= $py['id'] ?>">
                              <button type="submit" class="btn-admin-sm btn-hijau"
                                onclick="return confirm('Verifikasi pembayaran ini?')">
                                <i class="bi bi-check-circle"></i> Verifikasi
                              </button>
                            </form>
                            <button class="btn-admin-sm btn-merah" onclick="tolakBayar(<?= $py['id'] ?>)">
                              <i class="bi bi-x-circle"></i> Tolak
                            </button>
                          <?php elseif ($py['status'] === 'pending'): ?>
                            <span style="color:#94a3b8;font-size:.75rem">–</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div style="padding:24px;text-align:center;color:#aaa;font-size:.85rem">
                <i class="bi bi-credit-card d-block mb-1" style="font-size:1.5rem;opacity:.3"></i>
                Belum ada riwayat pembayaran
              </div>
            <?php endif; ?>
          </div>

        </div>

        <!-- KANAN -->
        <div class="col-lg-4">

          <!-- RINGKASAN PEMBAYARAN -->
          <div class="section-card mb-4">
            <div class="sc-header">
              <div class="sc-title"><i class="bi bi-receipt me-2" style="color:var(--emas)"></i>Ringkasan</div>
            </div>
            <div style="padding:20px">
              <div class="detail-row"><span
                  class="detail-label-sm">Harga/Orang</span><span><?= rupiah($booking['harga_per_orang']) ?></span>
              </div>
              <div class="detail-row"><span class="detail-label-sm">Jumlah
                  Jamaah</span><span><?= $booking['jumlah_jamaah'] ?> orang</span></div>
              <div class="detail-row"><span class="detail-label-sm">Total Tagihan</span><span
                  style="font-weight:700;color:var(--hijau)"><?= rupiah($booking['total_harga']) ?></span></div>
              <div class="detail-row"><span class="detail-label-sm">DP
                  Minimum</span><span><?= rupiah($booking['dp_amount'] ?? 0) ?></span></div>
              <hr style="border-color:#f0f0f0;margin:12px 0">
              <div class="detail-row"><span class="detail-label-sm">Terbayar</span><span
                  style="color:#16a34a;font-weight:700"><?= rupiah($total_terbayar) ?></span></div>
              <div class="detail-row">
                <span class="detail-label-sm">Sisa</span>
                <?php $sisa = $booking['total_harga'] - $total_terbayar; ?>
                <span style="color:<?= $sisa > 0 ? '#dc2626' : '#16a34a' ?>;font-weight:700">
                  <?= $sisa > 0 ? rupiah($sisa) : '✓ Lunas' ?>
                </span>
              </div>
            </div>
          </div>

          <!-- PAKET INFO -->
          <div class="section-card mb-4">
            <div class="sc-header">
              <div class="sc-title"><i class="bi bi-briefcase-fill me-2" style="color:var(--emas)"></i>Paket Dipilih
              </div>
            </div>
            <div style="padding:16px 20px;font-size:.83rem">
              <div
                style="font-family:var(--font-display);font-size:1rem;font-weight:700;color:var(--hijau-tua);margin-bottom:10px">
                <?= htmlspecialchars($booking['nama_paket'] ?? '-') ?>
              </div>
              <div class="detail-row-sm"><i class="bi bi-calendar3 me-2"
                  style="color:var(--emas)"></i><?= $booking['durasi'] ?? '-' ?> Hari</div>
              <div class="detail-row-sm"><i class="bi bi-airplane me-2"
                  style="color:var(--emas)"></i><?= htmlspecialchars($booking['maskapai'] ?? '-') ?></div>
              <div class="detail-row-sm"><i class="bi bi-building me-2"
                  style="color:var(--emas)"></i><?= htmlspecialchars($booking['hotel_mekkah'] ?? '-') ?></div>
              <div class="detail-row-sm"><i class="bi bi-geo-alt me-2"
                  style="color:var(--emas)"></i><?= htmlspecialchars($booking['hotel_madinah'] ?? '-') ?></div>
              <?php if (!empty($booking['tanggal_berangkat'])): ?>
                <div class="detail-row-sm"><i class="bi bi-calendar-event me-2"
                    style="color:var(--emas)"></i><?= tglIndo($booking['tanggal_berangkat']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- UBAH STATUS (disembunyikan untuk superadmin, read-only) -->
          <?php if (!isSuperadmin()): ?>
          <div class="section-card">
            <div class="sc-header">
              <div class="sc-title"><i class="bi bi-arrow-repeat me-2" style="color:var(--emas)"></i>Ubah Status</div>
            </div>
            <div style="padding:16px 20px">
              <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="aksi" value="ubah_status">
                <div class="mb-3">
                  <label style="font-size:.82rem;font-weight:600;color:#444;margin-bottom:4px">Status Baru</label>
                  <select name="status_baru" class="form-select form-select-sm" style="border-radius:8px">
                    <?php foreach (['pending', 'confirmed', 'dp_paid', 'lunas', 'berangkat', 'selesai', 'cancelled'] as $s): ?>
                      <option value="<?= $s ?>" <?= $booking['status'] === $s ? 'selected' : '' ?>>
                        <?= ['pending' => 'Menunggu', 'confirmed' => 'Dikonfirmasi', 'dp_paid' => 'DP Dibayar', 'lunas' => 'Lunas', 'berangkat' => 'Berangkat', 'selesai' => 'Selesai', 'cancelled' => 'Dibatalkan'][$s] ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label style="font-size:.82rem;font-weight:600;color:#444;margin-bottom:4px">Catatan untuk
                    Jamaah</label>
                  <textarea name="catatan_admin" class="form-control form-control-sm" rows="3"
                    placeholder="Pesan yang akan ditampilkan ke jamaah..."
                    style="border-radius:8px;resize:none"><?= htmlspecialchars($booking['catatan_admin'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn-admin btn-hijau w-100">
                  <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                </button>
              </form>
            </div>
          </div>
          <?php else: ?>
          <div class="section-card">
            <div class="sc-header">
              <div class="sc-title"><i class="bi bi-arrow-repeat me-2" style="color:var(--emas)"></i>Status Booking</div>
            </div>
            <div style="padding:16px 20px">
              <?= statusBadge($booking['status']) ?>
              <p class="mt-2 mb-0" style="font-size:.78rem;color:#94a3b8">
                <i class="bi bi-lock-fill"></i> Superadmin tidak bisa mengubah status (read-only).
              </p>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>

  <!-- MODAL TOLAK BAYAR -->
  <div class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header" style="background:#fee2e2;border:none">
          <h6 class="modal-title text-danger"><i class="bi bi-x-circle-fill me-2"></i>Tolak Pembayaran</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="aksi" value="tolak_bayar">
          <input type="hidden" name="bayar_id" id="tolakBayarId">
          <div class="modal-body">
            <label class="form-label fw-semibold" style="font-size:.85rem">Alasan penolakan</label>
            <textarea name="alasan" class="form-control" rows="3" placeholder="Contoh: Bukti transfer tidak jelas..."
              required></textarea>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-danger btn-sm rounded-pill">Tolak Pembayaran</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function tolakBayar(id) {
      document.getElementById('tolakBayarId').value = id;
      new bootstrap.Modal(document.getElementById('modalTolak')).show();
    }
  </script>
</body>

</html>