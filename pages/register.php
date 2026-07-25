<?php
require_once __DIR__ . '/../config/config.php';
if (isLoggedIn()) redirect(APP_URL . '/user/dashboard.php');

$errors = [];
$data   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $data = [
        'nama_lengkap' => sanitize($_POST['nama_lengkap'] ?? ''),
        'email'        => sanitize($_POST['email'] ?? ''),
        'telepon'      => sanitize($_POST['telepon'] ?? ''),
        'password'     => $_POST['password'] ?? '',
        'confirm_pass' => $_POST['confirm_pass'] ?? '',
    ];

    if (!$data['nama_lengkap']) $errors[] = 'Nama lengkap wajib diisi.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (strlen($data['password']) < 8) $errors[] = 'Password minimal 8 karakter.';
    if ($data['password'] !== $data['confirm_pass']) $errors[] = 'Konfirmasi password tidak cocok.';
    if (!$data['telepon']) $errors[] = 'Nomor telepon wajib diisi.';
    if (!isset($_POST['agree'])) $errors[] = 'Anda harus menyetujui syarat dan ketentuan.';

    if (empty($errors)) {
        // Cek email duplikat
        $existing = db()->fetchOne("SELECT id FROM users WHERE email = ?", 's', [$data['email']]);
        if ($existing) {
            $errors[] = 'Email sudah terdaftar. Silakan gunakan email lain atau login.';
        } else {
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $userId = db()->insert(
                "INSERT INTO users (nama_lengkap, email, password, telepon, status) VALUES (?, ?, ?, ?, 'aktif')",
                'ssss', [$data['nama_lengkap'], $data['email'], $hash, $data['telepon']]
            );
            if ($userId) {
                // Auto login
                $user = db()->fetchOne("SELECT * FROM users WHERE id = ?", 'i', [$userId]);
                loginUser($user);
                setFlash('success', 'Selamat datang! Akun Anda berhasil dibuat.');
                redirect(APP_URL . '/user/dashboard.php');
            } else {
                $errors[] = 'Terjadi kesalahan sistem. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Akun – SAH Travel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--hijau-tua:#1B4D2E;--hijau:#1B6B3A;--hijau-muda:#2E8B57;--emas:#C9A84C;--emas-muda:#E8C97A;--krem:#F9F5EE;--font-display:'Playfair Display',serif;--font-body:'DM Sans',sans-serif}
*{box-sizing:border-box}body{font-family:var(--font-body);background:var(--krem);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:30px 20px}
.auth-wrap{display:flex;width:100%;max-width:950px;border-radius:24px;overflow:hidden;box-shadow:0 30px 80px rgba(27,77,46,.2)}
.auth-left{background:linear-gradient(160deg,#0D2B1A,#1B4D2E 40%,#1B6B3A);flex:0 0 340px;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3rem;position:relative;overflow:hidden}
.auth-left::before{content:'';position:absolute;inset:0;opacity:.05;background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23C9A84C' fill-opacity='1'%3E%3Cpath d='M30 0L39 20.5H60L42.5 33.2L49.5 53.5L30 40.5L10.5 53.5L17.5 33.2L0 20.5H21L30 0Z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
.auth-arabic{font-family:'Amiri',serif;font-size:2rem;color:var(--emas);text-align:center;margin-bottom:1rem}
.auth-brand{font-family:var(--font-display);font-size:1.8rem;font-weight:700;color:#fff;text-align:center}.auth-brand span{color:var(--emas)}
.auth-tagline{color:rgba(255,255,255,.6);font-size:.83rem;text-align:center;margin-bottom:1.5rem}
.auth-step{display:flex;align-items:flex-start;gap:.7rem;color:rgba(255,255,255,.8);font-size:.82rem;margin-bottom:.6rem}
.auth-step i{color:var(--emas);margin-top:.1rem}
.auth-right{background:#fff;flex:1;padding:2.5rem;overflow-y:auto}
.form-title{font-family:var(--font-display);font-size:1.6rem;font-weight:700;color:var(--hijau-tua);margin-bottom:.2rem}
.form-sub{color:#888;font-size:.85rem;margin-bottom:1.5rem}
.form-label{font-size:.82rem;font-weight:600;color:#444;margin-bottom:.3rem}
.form-control{border:1.5px solid #e5e7eb;border-radius:10px;padding:.55rem .9rem;font-size:.88rem;transition:border .3s}
.form-control:focus{border-color:var(--hijau);box-shadow:0 0 0 3px rgba(27,107,58,.1);outline:none}
.input-group .form-control{border-right:none;border-radius:10px 0 0 10px}.input-group .btn{border:1.5px solid #e5e7eb;border-left:none;border-radius:0 10px 10px 0;background:#fff;color:#888}
.input-group .btn:focus{box-shadow:none}
.pw-strength{height:4px;border-radius:2px;margin-top:4px;transition:all .3s}
.btn-reg{background:linear-gradient(135deg,var(--hijau-tua),var(--hijau));color:#fff;border:none;border-radius:50px;padding:.75rem 2rem;font-size:.93rem;font-weight:700;width:100%;transition:all .3s}
.btn-reg:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(27,107,58,.35)}
.err-list{background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:.8rem 1rem;margin-bottom:1.2rem}
.err-list li{color:#DC2626;font-size:.83rem}
.link-login{color:var(--hijau);text-decoration:none;font-weight:600}.link-login:hover{text-decoration:underline}
@media(max-width:768px){.auth-left{display:none}}
</style>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-left">
    <div class="auth-arabic">أَهْلًا وَسَهْلًا</div>
    <div class="auth-brand"><i class="bi bi-moon-stars-fill me-2" style="color:var(--emas)"></i>SAH <span>Travel</span></div>
    <div class="auth-tagline">Daftar & Mulai Perjalanan Suci Anda</div>
    <div>
      <div class="auth-step"><i class="bi bi-1-circle-fill"></i><span>Buat akun jamaah</span></div>
      <div class="auth-step"><i class="bi bi-2-circle-fill"></i><span>Pilih paket umrah favorit</span></div>
      <div class="auth-step"><i class="bi bi-3-circle-fill"></i><span>Upload dokumen persyaratan</span></div>
      <div class="auth-step"><i class="bi bi-4-circle-fill"></i><span>Konfirmasi pembayaran</span></div>
      <div class="auth-step"><i class="bi bi-5-circle-fill"></i><span>Siap berangkat ke Tanah Haram 🕋</span></div>
    </div>
  </div>
  <div class="auth-right">
    <div class="form-title">Buat Akun Baru</div>
    <div class="form-sub">Isi data di bawah untuk mendaftar sebagai jamaah</div>

    <?php if (!empty($errors)): ?>
    <ul class="err-list">
      <?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="on">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Nama Lengkap *</label>
          <input type="text" name="nama_lengkap" class="form-control" placeholder="Sesuai KTP / Paspor" value="<?= htmlspecialchars($data['nama_lengkap'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email *</label>
          <input type="email" name="email" class="form-control" placeholder="email@example.com" value="<?= htmlspecialchars($data['email'] ?? '') ?>" required autocomplete="email">
        </div>
        <div class="col-md-6">
          <label class="form-label">No. WhatsApp / HP *</label>
          <input type="tel" name="telepon" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($data['telepon'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Password *</label>
          <div class="input-group">
            <input type="password" name="password" id="pwd1" class="form-control" placeholder="Min. 8 karakter" required oninput="checkStrength(this.value)" autocomplete="new-password">
            <button type="button" class="btn" onclick="togglePwd('pwd1','eye1')"><i class="bi bi-eye" id="eye1"></i></button>
          </div>
          <div id="pw-bar" class="pw-strength mt-1" style="background:#eee;width:0%"></div>
          <div id="pw-txt" style="font-size:.72rem;color:#888;margin-top:2px"></div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Konfirmasi Password *</label>
          <div class="input-group">
            <input type="password" name="confirm_pass" id="pwd2" class="form-control" placeholder="Ulangi password" required autocomplete="new-password">
            <button type="button" class="btn" onclick="togglePwd('pwd2','eye2')"><i class="bi bi-eye" id="eye2"></i></button>
          </div>
          <div id="match-txt" style="font-size:.72rem;margin-top:2px"></div>
        </div>
        <div class="col-12">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="agree" name="agree" required>
            <label class="form-check-label" for="agree" style="font-size:.83rem">
              Saya menyetujui <a href="syarat-ketentuan.php" target="_blank" style="color:var(--hijau)">Syarat & Ketentuan</a> dan <a href="kebijakan-privasi.php" target="_blank" style="color:var(--hijau)">Kebijakan Privasi</a> SAH Travel
            </label>
          </div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn-reg"><i class="bi bi-person-plus-fill me-2"></i>Buat Akun</button>
        </div>
        <div class="col-12 text-center" style="font-size:.86rem">
          Sudah punya akun? <a href="login.php" class="link-login">Login di sini</a>
        </div>
        <div class="col-12 text-center">
          <a href="../index.php" style="font-size:.8rem;color:#aaa;text-decoration:none"><i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda</a>
        </div>
      </div>
    </form>
  </div>
</div>
<script>
function togglePwd(id,ico){
  const f=document.getElementById(id);const i=document.getElementById(ico);
  if(f.type==='password'){f.type='text';i.className='bi bi-eye-slash';}else{f.type='password';i.className='bi bi-eye';}
}
function checkStrength(v){
  const bar=document.getElementById('pw-bar');const txt=document.getElementById('pw-txt');
  let score=0;
  if(v.length>=8)score++;if(/[A-Z]/.test(v))score++;if(/[0-9]/.test(v))score++;if(/[^A-Za-z0-9]/.test(v))score++;
  const colors=['#dc3545','#fd7e14','#ffc107','#28a745'];
  const labels=['Lemah','Cukup','Kuat','Sangat Kuat'];
  if(v.length>0){bar.style.width=(score*25)+'%';bar.style.background=colors[score-1]||'#eee';txt.textContent=labels[score-1]||'';txt.style.color=colors[score-1]||'#888';}
  else{bar.style.width='0';txt.textContent='';}
}
document.getElementById('pwd2').addEventListener('input',function(){
  const m=document.getElementById('match-txt');
  if(this.value===document.getElementById('pwd1').value){m.textContent='✓ Password cocok';m.style.color='#28a745';}
  else{m.textContent='✗ Password tidak cocok';m.style.color='#dc3545';}
});
</script>
</body>
</html>
