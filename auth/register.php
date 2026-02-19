<?php
require_once 'functions.php';
if (isLoggedIn()) {
    header('Location: ' . ($_SESSION['user_role'] === 'admin' ? '../admin.php' : '../user.php'));
    exit;
}

$err = '';
$ok  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']      ?? '');
    $email    = trim($_POST['email']     ?? '');
    $password = trim($_POST['password']  ?? '');
    $konfirm  = trim($_POST['konfirm']   ?? '');
    $role     = $_POST['role'] ?? 'user';

    // Validasi
    if (!$nama || !$email || !$password || !$konfirm) {
        $err = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $err = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirm) {
        $err = 'Konfirmasi password tidak cocok.';
    } elseif (!in_array($role, ['admin','user'])) {
        $err = 'Role tidak valid.';
    } else {
        try {
            $db = getDB();
            // Cek email sudah ada
            $cek = $db->prepare("SELECT id FROM users WHERE email = ?");
            $cek->execute([$email]);
            if ($cek->fetch()) {
                $err = 'Email sudah terdaftar. Silakan gunakan email lain.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins  = $db->prepare(
                    "INSERT INTO users (nama, email, password, role) VALUES (?,?,?,?)"
                );
                $ins->execute([$nama, $email, $hash, $role]);
                // Langsung login setelah daftar
                $user = $db->prepare("SELECT * FROM users WHERE email = ?");
                $user->execute([$email]);
                $u = $user->fetch();
                $_SESSION['user_id']    = $u['id'];
                $_SESSION['user_nama']  = $u['nama'];
                $_SESSION['user_role']  = $u['role'];
                $_SESSION['user_email'] = $u['email'];

                header('Location: ' . ($u['role'] === 'admin' ? '../admin.php' : '../user.php'));
                exit;
            }
        } catch (Exception $e) {
            $err = 'Kesalahan server: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Daftar — Sistem Laporan</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:      #0b1120;
      --card:    #131d33;
      --border:  #253050;
      --accent:  #3b82f6;
      --text:    #e2e8f0;
      --muted:   #8898b8;
      --danger:  #ef4444;
      --success: #22c55e;
      --radius:  12px;
    }
    body {
      background: var(--bg); min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Segoe UI', system-ui, sans-serif; padding: 20px;
    }
    .card {
      background: var(--card); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 36px 32px;
      width: 100%; max-width: 440px;
      box-shadow: 0 20px 60px rgba(0,0,0,.5);
    }
    .logo { text-align: center; margin-bottom: 28px; }
    .logo-icon  { font-size: 42px; }
    .logo-title { font-size: 22px; font-weight: 800; color: var(--text); margin-top: 8px; }
    .logo-sub   { font-size: 13px; color: var(--muted); margin-top: 4px; }

    .field       { margin-bottom: 16px; }
    label        { display: block; font-size: 12px; color: var(--muted); margin-bottom: 6px; font-weight: 600; letter-spacing:.4px; text-transform:uppercase; }
    input, select {
      width: 100%; background: #0b1120;
      border: 1px solid var(--border); border-radius: 8px;
      color: var(--text); padding: 11px 14px; font-size: 14px;
      outline: none; transition: border .2s;
    }
    input:focus, select:focus { border-color: var(--accent); }

    /* Role selector */
    .role-group { display: flex; gap: 10px; }
    .role-card  {
      flex: 1; border: 2px solid var(--border);
      border-radius: 10px; padding: 14px 10px;
      text-align: center; cursor: pointer;
      transition: all .2s; background: #0b1120;
    }
    .role-card:hover          { border-color: var(--accent); }
    .role-card input[type=radio] { display: none; }
    .role-card.selected       { border-color: var(--accent); background: rgba(59,130,246,.1); }
    .role-icon  { font-size: 26px; }
    .role-label { font-size: 13px; font-weight: 700; color: var(--text); margin-top: 6px; }
    .role-desc  { font-size: 11px; color: var(--muted); margin-top: 3px; }

    /* Password strength */
    .strength-bar { height: 4px; border-radius: 2px; margin-top: 6px; background: var(--border); overflow: hidden; }
    .strength-fill { height: 100%; width: 0; transition: all .3s; border-radius: 2px; }
    .strength-text { font-size: 11px; margin-top: 4px; }

    .btn-primary {
      width: 100%; padding: 12px; background: var(--accent); color: #fff;
      border: none; border-radius: 8px; font-size: 15px; font-weight: 700;
      cursor: pointer; transition: opacity .2s, transform .1s; margin-top: 4px;
    }
    .btn-primary:hover  { opacity: .88; }
    .btn-primary:active { transform: scale(.98); }

    .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; }
    .alert-err { background:#450a0a33; color:#fca5a5; border:1px solid #ef444455; }

    .divider {
      display: flex; align-items: center; gap: 10px;
      margin: 20px 0; color: var(--muted); font-size: 12px;
    }
    .divider::before, .divider::after { content:''; flex:1; height:1px; background: var(--border); }

    .link-row { text-align: center; font-size: 13px; color: var(--muted); margin-top: 20px; }
    .link-row a { color: var(--accent); text-decoration: none; font-weight: 600; }
    .link-row a:hover { text-decoration: underline; }
  </style>
</head>
<body>
<div class="card">

  <div class="logo">
    <div class="logo-icon">📝</div>
    <div class="logo-title">Buat Akun Baru</div>
    <div class="logo-sub">Sistem Laporan Air Bersih & Sanitasi</div>
  </div>

  <?php if ($err): ?>
    <div class="alert alert-err">⚠️ <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <form method="POST" id="regForm">

    <!-- Role Selector -->
    <div class="field">
      <label>Daftar Sebagai</label>
      <div class="role-group">
        <label class="role-card selected" id="cardUser">
          <input type="radio" name="role" value="user" checked />
          <div class="role-icon">👤</div>
          <div class="role-label">User</div>
          <div class="role-desc">Kirim & pantau laporan</div>
        </label>
        <label class="role-card" id="cardAdmin">
          <input type="radio" name="role" value="admin" />
          <div class="role-icon">🛡️</div>
          <div class="role-label">Admin</div>
          <div class="role-desc">Verifikasi & kelola laporan</div>
        </label>
      </div>
    </div>

    <div class="field">
      <label for="nama">Nama Lengkap</label>
      <input id="nama" name="nama" type="text" placeholder="Nama Anda"
             value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required/>
    </div>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" placeholder="contoh@email.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
    </div>

    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password"
             placeholder="Minimal 6 karakter" required/>
      <div class="strength-bar"><div class="strength-fill" id="sBar"></div></div>
      <div class="strength-text" id="sText" style="color:var(--muted)"></div>
    </div>

    <div class="field">
      <label for="konfirm">Konfirmasi Password</label>
      <input id="konfirm" name="konfirm" type="password"
             placeholder="Ulangi password" required/>
      <div class="strength-text" id="matchText"></div>
    </div>

    <button class="btn-primary" type="submit">Daftar Sekarang →</button>
  </form>

  <div class="divider">atau</div>

  <div class="link-row">
    Sudah punya akun? <a href="login.php">Masuk di sini</a>
  </div>
</div>

<script>
// Role card highlight
document.querySelectorAll('.role-card').forEach(card => {
  card.addEventListener('click', () => {
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
  });
});

// Password strength
const pw = document.getElementById('password');
const bar = document.getElementById('sBar');
const sTxt = document.getElementById('sText');
pw.addEventListener('input', () => {
  const v = pw.value;
  let score = 0;
  if (v.length >= 6)  score++;
  if (v.length >= 10) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;

  const levels = [
    { w:'0%',   color:'',          label:'' },
    { w:'25%',  color:'#ef4444',   label:'🔴 Terlalu lemah' },
    { w:'50%',  color:'#f59e0b',   label:'🟡 Cukup' },
    { w:'75%',  color:'#3b82f6',   label:'🔵 Kuat' },
    { w:'100%', color:'#22c55e',   label:'🟢 Sangat kuat' },
  ];
  const l = levels[Math.min(score, 4)];
  bar.style.width       = l.w;
  bar.style.background  = l.color;
  sTxt.textContent      = l.label;
  sTxt.style.color      = l.color;
});

// Konfirmasi match
document.getElementById('konfirm').addEventListener('input', function() {
  const el = document.getElementById('matchText');
  if (this.value === pw.value) {
    el.textContent = '✅ Password cocok';
    el.style.color = '#22c55e';
  } else {
    el.textContent = '❌ Tidak cocok';
    el.style.color = '#ef4444';
  }
});
</script>
</body>
</html>
