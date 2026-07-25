<?php
require_once __DIR__ . '/../config/config.php';

// Jika sudah login, redirect
if (isLoggedIn()) redirect(APP_URL . '/user/dashboard.php');
if (isAdminLoggedIn()) redirect(APP_URL . '/admin/dashboard.php');

$error = '';
$redirect = sanitize($_GET['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $user = db()->fetchOne("SELECT * FROM users WHERE email = ? AND status = 'aktif'", 's', [$email]);
        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            setFlash('success', 'Selamat datang kembali, ' . $user['nama_lengkap'] . '!');
            redirect($redirect ?: APP_URL . '/user/dashboard.php');
        } else {
            $error = 'Email atau password salah, atau akun tidak aktif.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – SAH Travel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--hijau-tua:#1B4D2E;--hijau:#1B6B3A;--hijau-muda:#2E8B57;--emas:#C9A84C;--emas-muda:#E8C97A;--krem:#F9F5EE;--font-arab:'Amiri',serif;--font-display:'Playfair Display',serif;--font-body:'DM Sans',sans-serif}
*{box-sizing:border-box}body{font-family:var(--font-body);background:var(--krem);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.auth-wrap{display:flex;min-height:100vh;width:100%;max-width:900px;border-radius:24px;overflow:hidden;box-shadow:0 30px 80px rgba(27,77,46,.2)}
.auth-left{background:linear-gradient(160deg,#0D2B1A,#1B4D2E 40%,#1B6B3A);flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3rem;position:relative;overflow:hidden}
.auth-left::before{content:'';position:absolute;inset:0;opacity:.05;background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23C9A84C' fill-opacity='1'%3E%3Cpath d='M30 0L39 20.5H60L42.5 33.2L49.5 53.5L30 40.5L10.5 53.5L17.5 33.2L0 20.5H21L30 0Z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
.auth-arabic{font-family:var(--font-arab);font-size:2.2rem;color:var(--emas);text-align:center;margin-bottom:1rem;text-shadow:0 2px 20px rgba(201,168,76,.3)}
.auth-brand{font-family:var(--font-display);font-size:2rem;font-weight:700;color:#fff;text-align:center;margin-bottom:.3rem}
.auth-brand span{color:var(--emas)}.auth-tagline{color:rgba(255,255,255,.65);font-size:.88rem;text-align:center;margin-bottom:2rem}
.auth-feature{display:flex;align-items:center;gap:.8rem;color:rgba(255,255,255,.8);font-size:.85rem;margin-bottom:.7rem}
.auth-feature i{color:var(--emas);font-size:1rem;width:20px}
.auth-right{background:#fff;flex:0 0 420px;padding:3rem;display:flex;flex-direction:column;justify-content:center}
.form-title{font-family:var(--font-display);font-size:1.7rem;font-weight:700;color:var(--hijau-tua);margin-bottom:.3rem}
.form-sub{color:#888;font-size:.88rem;margin-bottom:2rem}
.form-label{font-size:.85rem;font-weight:600;color:#444;margin-bottom:.4rem}
.form-control{border:1.5px solid #e5e7eb;border-radius:10px;padding:.6rem .9rem;font-size:.9rem;transition:border .3s}
.form-control:focus{border-color:var(--hijau);box-shadow:0 0 0 3px rgba(27,107,58,.1);outline:none}
.input-group .form-control{border-right:none}.input-group .btn{border:1.5px solid #e5e7eb;border-left:none;border-radius:0 10px 10px 0;background:#fff;color:#888;transition:all .3s}
.input-group .btn:hover{color:var(--hijau)}.input-group .btn:focus{box-shadow:none}
.btn-login{background:linear-gradient(135deg,var(--hijau-tua),var(--hijau));color:#fff;border:none;border-radius:50px;padding:.8rem 2rem;font-size:.95rem;font-weight:700;width:100%;transition:all .3s;letter-spacing:.3px}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(27,107,58,.35)}
.divider{display:flex;align-items:center;gap:.8rem;color:#bbb;font-size:.82rem;margin:1.2rem 0}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e5e7eb}
.link-reg{color:var(--hijau);text-decoration:none;font-weight:600}.link-reg:hover{text-decoration:underline}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;color:#DC2626;border-radius:10px;padding:.8rem 1rem;font-size:.85rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:.5rem}
@media(max-width:768px){.auth-left{display:none}.auth-right{flex:1;max-width:100%}.auth-wrap{border-radius:16px}}
</style>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-left">
    <div class="auth-arabic">بِسْمِ اللهِ الرَّحْمٰنِ الرَّحِيْمِ</div>
    <div class="auth-brand"><i class="bi bi-moon-stars-fill me-2" style="color:var(--emas)"></i>SAH <span>Travel</span></div>
    <div class="auth-tagline">PT. Sumatera Andalan Haramain</div>
    <div class="mt-3">
      <div class="auth-feature"><i class="bi bi-patch-check-fill"></i> Izin Resmi PPIU Kemenag RI</div>
      <div class="auth-feature"><i class="bi bi-shield-lock-fill"></i> Akun aman & terenkripsi</div>
      <div class="auth-feature"><i class="bi bi-bell-fill"></i> Notifikasi status booking realtime</div>
      <div class="auth-feature"><i class="bi bi-file-earmark-text-fill"></i> Kelola dokumen & invoice</div>
    </div>
  </div>
  <div class="auth-right">
    <div class="form-title">Selamat Datang</div>
    <div class="form-sub">Login ke akun jamaah Anda</div>

    <?php if ($error): ?>
    <div class="alert-error"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type']==='success'?'success':'warning' ?> rounded-3 py-2 px-3" style="font-size:.85rem"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="on">
      <?= csrfField() ?>
      <?php if ($redirect): ?><input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>"><?php endif; ?>
      
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" placeholder="email@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus autocomplete="email">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <div class="input-group">
          <input type="password" name="password" id="pwdField" class="form-control" placeholder="Masukkan password" required autocomplete="current-password">
          <button type="button" class="btn" onclick="togglePwd()"><i class="bi bi-eye" id="eyeIcon"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="remember" name="remember">
          <label class="form-check-label" for="remember" style="font-size:.83rem">Ingat saya</label>
        </div>
        <a href="lupa-password.php" style="font-size:.83rem;color:var(--hijau);text-decoration:none">Lupa Password?</a>
      </div>
      <button type="submit" class="btn-login"><i class="bi bi-box-arrow-in-right me-2"></i>Login</button>
    </form>

    <div class="divider">atau</div>
    <div class="text-center" style="font-size:.88rem">
      Belum punya akun? <a href="register.php" class="link-reg">Daftar di sini</a>
    </div>
    <div class="text-center mt-3">
      <a href="../index.php" style="font-size:.82rem;color:#888;text-decoration:none"><i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda</a>
    </div>
  </div>
</div>
<script>
function togglePwd(){
  const f=document.getElementById('pwdField');
  const i=document.getElementById('eyeIcon');
  if(f.type==='password'){f.type='text';i.className='bi bi-eye-slash';}
  else{f.type='password';i.className='bi bi-eye';}
}
</script>
</body>
</html>
