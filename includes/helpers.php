<?php
// ============================================================
// includes/helpers.php
// ============================================================

/**
 * Sanitize input
 */
function sanitize(string $input): string
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect
 */
function redirect(string $url, string $message = '', string $type = ''): void
{
    if ($message !== '') {
        $flashType = ($type === 'sukses' || $type === 'success') ? 'success' : 'error';
        setFlash($flashType, $message);
    }
    header("Location: $url");
    exit;
}


/**
 * renderFlash — FUNGSI BARU
 *
 * getFlash() mengembalikan array|null, TIDAK bisa langsung di-echo.
 * renderFlash() membungkusnya jadi HTML alert Bootstrap yang siap tampil.
 *
 * Ganti semua:  <?= getFlash() ?>
 * Menjadi:      <?= renderFlash() ?>
 */
function renderFlash(): string
{
    $flash = getFlash();
    if (!$flash) {
        return '';
    }

    $type = $flash['type'] ?? 'success';
    $alertClass = $type === 'success' ? 'alert-success' : 'alert-danger';
    $icon = $type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';

    return '<div class="alert ' . $alertClass . ' rounded-3 mb-3 d-flex align-items-center gap-2" style="font-size:.85rem">'
        . '<i class="bi ' . $icon . '"></i>'
        . htmlspecialchars($flash['message'])
        . '</div>';
}

/**
 * Format Rupiah
 */
function rupiah(float $angka): string
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Format tanggal Indonesia
 */
function tglIndo(string $tanggal): string
{
    $bulan = [
        '',
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    $t = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $t[2] . ' ' . $bulan[(int) $t[1]] . ' ' . $t[0];
}

/**
 * Time ago Indonesia
 */
function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)
        return $diff . ' detik lalu';
    if ($diff < 3600)
        return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400)
        return floor($diff / 3600) . ' jam lalu';
    if ($diff < 2592000)
        return floor($diff / 86400) . ' hari lalu';
    return tglIndo($datetime);
}

/**
 * Generate slug
 */
function makeSlug(string $str): string
{
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/\s+/', '-', trim($str));
    return preg_replace('/-+/', '-', $str);
}

/**
 * Generate kode booking
 */
function generateBookingCode(): string
{
    return 'SAH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Generate kode pembayaran
 */
function generatePaymentCode(): string
{
    return 'PAY-' . date('YmdHis') . '-' . rand(100, 999);
}

/**
 * Generate nomor invoice
 */
function generateInvoiceNo(): string
{
    return 'INV/' . date('Y') . '/' . date('m') . '/' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Flash message
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): array|null
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Upload file
 */
function uploadFile(array $file, string $dir, array $allowed = [], int $maxSize = 0): array
{
    if ($maxSize === 0)
        $maxSize = MAX_FILE_SIZE;
    if (empty($allowed))
        $allowed = ALLOWED_IMAGE_TYPES;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload gagal: ' . $file['error']];
    }
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal ' . ($maxSize / 1048576) . 'MB'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('sah_', true) . '.' . strtolower($ext);
    $fullDir = UPLOAD_PATH . $dir . '/';

    if (!is_dir($fullDir))
        mkdir($fullDir, 0755, true);

    if (move_uploaded_file($file['tmp_name'], $fullDir . $filename)) {
        return ['success' => true, 'filename' => $dir . '/' . $filename];
    }
    return ['success' => false, 'message' => 'Gagal menyimpan file'];
}

/**
 * Delete file
 */
function deleteFile(string $path): bool
{
    $fullPath = UPLOAD_PATH . $path;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

/**
 * Get setting value
 */
function getSetting(string $key, string $default = ''): string
{
    static $settings = [];
    if (empty($settings)) {
        $rows = db()->fetchAll("SELECT nama_key, nilai FROM pengaturan");
        foreach ($rows as $row) {
            $settings[$row['nama_key']] = $row['nilai'];
        }
    }
    return $settings[$key] ?? $default;
}

/**
 * Truncate text
 */
function truncate(string $text, int $length = 150): string
{
    $text = strip_tags($text);
    if (strlen($text) <= $length)
        return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Stars HTML
 */
function starRating(int $rating): string
{
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="fas fa-star' . ($i <= $rating ? ' text-warning' : ' text-muted') . '"></i>';
    }
    return $html;
}

/**
 * Status badge
 */
function statusBadge(string $status): string
{
    $badges = [
        'aktif' => 'bg-success',
        'pending' => 'bg-warning',
        'confirmed' => 'bg-info',
        'dp_paid' => 'bg-primary',
        'lunas' => 'bg-success',
        'cancelled' => 'bg-danger',
        'selesai' => 'bg-secondary',
        'habis' => 'bg-danger',
        'draft' => 'bg-secondary',
        'published' => 'bg-success',
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        'verified' => 'bg-success',
        'nonaktif' => 'bg-secondary',
        'baru' => 'bg-warning',
        'dokumen_belum_lengkap' => 'bg-danger',
        'dokumen_lengkap' => 'bg-info',
        'menunggu_pembayaran' => 'bg-warning',
        'submitted' => 'bg-primary',
        'in_process' => 'bg-primary',
        'perlu_revisi' => 'bg-warning'
    ];
    $labels = [
        'aktif' => 'Aktif',
        'pending' => 'Pending',
        'confirmed' => 'Dikonfirmasi',
        'dp_paid' => 'DP Terbayar',
        'lunas' => 'Lunas',
        'cancelled' => 'Dibatalkan',
        'selesai' => 'Selesai',
        'habis' => 'Habis',
        'draft' => 'Draft',
        'published' => 'Published',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'verified' => 'Terverifikasi',
        'nonaktif' => 'Nonaktif',
        'baru' => 'Baru',
        'dokumen_belum_lengkap' => 'Dokumen Belum Lengkap',
        'dokumen_lengkap' => 'Dokumen Lengkap',
        'menunggu_pembayaran' => 'Menunggu Pembayaran',
        'submitted' => 'Terkirim ke Provider',
        'in_process' => 'Sedang Diproses',
        'perlu_revisi' => 'Perlu Revisi'
    ];
    $class = $badges[$status] ?? 'bg-secondary';
    $label = $labels[$status] ?? ucfirst($status);
    return "<span class=\"badge $class\">$label</span>";
}

/**
 * Pagination
 */
function paginate(int $total, int $page, int $perPage, string $url): string
{
    $totalPages = ceil($total / $perPage);
    if ($totalPages <= 1)
        return '';

    $html = '<nav><ul class="pagination justify-content-center">';

    // Prev
    $html .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $url . '?page=' . ($page - 1) . '">&laquo;</a></li>';

    // Pages
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);

    for ($i = $start; $i <= $end; $i++) {
        $html .= '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
        $html .= '<a class="page-link" href="' . $url . '?page=' . $i . '">' . $i . '</a></li>';
    }

    // Next
    $html .= '<li class="page-item ' . ($page >= $totalPages ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $url . '?page=' . ($page + 1) . '">&raquo;</a></li>';

    $html .= '</ul></nav>';
    return $html;
}

/**
 * WA Link generator
 */
function waLink(string $message = '', string $number = ''): string
{
    if (!$number)
        $number = WA_NUMBER;
    if (!$message)
        $message = WA_DEFAULT_MSG;
    return 'https://wa.me/' . $number . '?text=' . urlencode($message);
}

/**
 * Kuota bar
 */
function kuotaBar(int $terisi, int $total): string
{
    $persen = $total > 0 ? round(($terisi / $total) * 100) : 0;
    $color = $persen >= 90 ? 'danger' : ($persen >= 70 ? 'warning' : 'success');
    return '<div class="progress" style="height:6px"><div class="progress-bar bg-' . $color . '" style="width:' . $persen . '%"></div></div>
            <small class="text-muted">' . ($total - $terisi) . ' kursi tersisa dari ' . $total . '</small>';
}

/**
 * Hitung cicilan
 */
function hitungCicilan(float $total, float $dpPersen = 30, int $bulan = 6): array
{
    $dp = $total * ($dpPersen / 100);
    $sisa = $total - $dp;
    $cicilan = $sisa / $bulan;
    return ['dp' => $dp, 'sisa' => $sisa, 'cicilan_per_bulan' => $cicilan, 'bulan' => $bulan];
}

// Alias sanitize → clean
function clean(string $input): string
{
    return sanitize($input);
}