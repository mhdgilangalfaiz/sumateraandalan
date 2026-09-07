<?php
/**
 * cron/expire-booking.php
 *
 * Dijalankan berkala (disarankan tiap 5-10 menit) lewat cron job server, contoh:
 *   php-cli /path/ke/project/cron/expire-booking.php
 *   atau lewat crontab: * /10 * * * * php /var/www/sah_umrah/cron/expire-booking.php
 *
 * Tugasnya cuma satu: cari booking standalone (tiket pesawat dari agen)
 * yang sudah lewat batas_deposit tapi belum ada pembayaran sama sekali,
 * lalu batalkan otomatis dan kembalikan seat-nya ke stok bersama.
 *
 * TIDAK menyentuh booking paket umrah biasa (batas_deposit selalu NULL
 * untuk itu, jadi otomatis tidak pernah kena query di bawah).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$now = date('Y-m-d H:i:s');

// Ambil semua booking standalone tiket yang sudah lewat deadline
// dan masih berstatus pending (belum ada DP masuk sama sekali)
$expiredBookings = db()->fetchAll(
    "SELECT id FROM booking WHERE batas_deposit IS NOT NULL AND batas_deposit < ? AND status = 'pending'",
    's',
    [$now]
);

if (empty($expiredBookings)) {
    echo "[" . $now . "] Tidak ada booking yang perlu di-expire.\n";
    exit;
}

foreach ($expiredBookings as $b) {
    $bookingId = $b['id'];

    db()->beginTransaction();
    try {
        // Ambil tiket terkait booking ini, kunci barisnya supaya aman dari
        // proses lain yang mungkin sedang jalan bersamaan.
        $booking = db()->fetchOne("SELECT tiket_id, jumlah_jamaah FROM booking WHERE id = ?", 'i', [$bookingId]);

        if ($booking && $booking['tiket_id']) {
            db()->execute("SELECT id FROM tiket_pesawat WHERE id = ? FOR UPDATE", 'i', [$booking['tiket_id']]);
            // Lepas kembali seat yang tadinya sudah dianggap terisi, dan
            // kembalikan status jadi 'aktif' kalau sebelumnya 'penuh'
            db()->execute(
                "UPDATE tiket_pesawat SET terisi = GREATEST(0, terisi - ?), status = IF(status = 'penuh', 'aktif', status) WHERE id = ?",
                'ii',
                [$booking['jumlah_jamaah'], $booking['tiket_id']]
            );
        }

        // Batalkan booking-nya
        db()->execute(
            "UPDATE booking SET status = 'cancelled', catatan_admin = CONCAT(COALESCE(catatan_admin, ''), '\n[Sistem] Dibatalkan otomatis - deposit tidak diterima dalam 1x24 jam.') WHERE id = ?",
            'i',
            [$bookingId]
        );

        db()->commit();

        // Notifikasi ke admin
        db()->insert(
            "INSERT INTO notifikasi (judul, pesan, tipe, link) VALUES (?, ?, 'booking', ?)",
            'sss',
            [
                'Order Tiket Dibatalkan Otomatis',
                'Booking #' . $bookingId . ' dibatalkan sistem karena deposit tidak diterima dalam 1x24 jam.',
                BASE_URL . '/admin/booking-detail.php?id=' . $bookingId
            ]
        );

        echo "[" . $now . "] Booking #{$bookingId} berhasil di-expire.\n";
    } catch (Exception $e) {
        db()->rollback();
        echo "[" . $now . "] GAGAL expire booking #{$bookingId}: " . $e->getMessage() . "\n";
    }
}