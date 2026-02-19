<?php
require_once 'functions.php';
// Kalau sudah login, redirect langsung
if (isLoggedIn()) {
    header('Location: ' . ($_SESSION['user_role'] === 'admin' ? '../admin.php' : '../user.php'));
    exit;
}
$err = $_GET['err'] ?? '';
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Masuk — Sistem Laporan</title>
  <link rel="stylesheet" href="login.css"/>
  <style>
    /* ── Base ── */
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
      background: var(--bg);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', system-ui, sans-serif;
      padding: 20px;
    }

    /* ── Card ── */
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 36px 32px;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 20px 60px rgba(0,0,0,.5);
    }

    /* ── Logo ── */
    .logo { text-align: center; margin-bottom: 28px; }
    .logo-icon { font-size: 42px; }
    .logo-title {
      font-size: 22px; font-weight: 800;
      color: var(--text); margin-top: 8px;
    }
    .logo-sub { font-size: 13px; color: var(--muted); margin-top: 4px; }

    /* ── Form ── */
    .field       { margin-bottom: 18px; }
    label        { display: block; font-size: 12px; color: var(--muted); margin-bottom: 6px; font-weight: 600; letter-spacing:.4px; text-transform:uppercase; }
    input        {
      width: 100%; background: #0b1120;
      border: 1px solid var(--border);
      border-radius: 8px; color: var(--text);
      padding: 11px 14px; font-size: 14px;
      outline: none; transition: border .2s;
    }
    input:focus  { border-color: var(--accent); }

    /* ── Button ── */
    .btn-primary {
      width: 100%; padding: 12px;
      background: var(--accent); color: #fff;
      border: none; border-radius: 8px;
      font-size: 15px; font-weight: 700;
      cursor: pointer; transition: opacity .2s, transform .1s;
      margin-top: 4px;
    }
    .btn-primary:hover  { opacity: .88; }
    .btn-primary:active { transform: scale(.98); }

    /* ── Alert ── */
    .alert {
      padding: 10px 14px; border-radius: 8px;
      font-size: 13px; margin-bottom: 18px;
    }
    .alert-err  { background:#450a0a33; color:#fca5a5; border:1px solid #ef444455; }
    .alert-ok   { background:#14532d33; color:#86efac; border:1px solid #22c55e55; }

    /* ── Divider ── */
    .divider {
      display: flex; align-items: center; gap: 10px;
      margin: 20px 0; color: var(--muted); font-size: 12px;
    }
    .divider::before, .divider::after {
      content:''; flex:1; height:1px; background: var(--border);
    }

    /* ── Link ── */
    .link-row { text-align: center; font-size: 13px; color: var(--muted); margin-top: 20px; }
    .link-row a { color: var(--accent); text-decoration: none; font-weight: 600; }
    .link-row a:hover { text-decoration: underline; }

    /* ── Demo accounts ── */
    .demo {
      background: #0f172a; border: 1px solid var(--border);
      border-radius: 8px; padding: 12px 14px;
      margin-bottom: 20px; font-size: 12px; color: var(--muted);
    }
    .demo strong { color: var(--text); }
    .demo-row { display:flex; justify-content:space-between; margin-top:6px; }
    .demo-fill {
      background: none; border: 1px solid var(--border);
      color: var(--accent); border-radius: 5px;
      padding: 2px 8px; font-size: 11px; cursor: pointer;
    }
    .demo-fill:hover { background: var(--accent); color: #fff; }
  </style>
</head>
<body>
<div class="card">

  <div class="logo">
    <div class="logo-icon">🛡️</div>
    <div class="logo-title">Sistem Laporan</div>
    <div class="logo-sub">Air Bersih & Sanitasi</div>
  </div>

  <?php if ($err): ?>
    <div class="alert alert-err">⚠️ <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>
  <?php if ($msg): ?>
    <div class="alert alert-ok">✅ <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- Akun Demo -->
  <div class="demo">
    <strong>Akun demo tersedia:</strong>
    <div class="demo-row">
      <span>👤 Admin &nbsp;<code>admin@laporan.id</code></span>
      <button class="demo-fill" onclick="fillDemo('admin@laporan.id')">Isi</button>
    </div>
    <div class="demo-row">
      <span>👤 User &nbsp;&nbsp;<code>user@laporan.id</code></span>
      <button class="demo-fill" onclick="fillDemo('user@laporan.id')">Isi</button>
    </div>
    <div style="margin-top:6px">Password semua: <strong>password</strong></div>
  </div>

  <form action="process_login.php" method="POST">
    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email"
             placeholder="contoh@email.com" autocomplete="email" required
             value="<?= htmlspecialchars($_GET['email'] ?? '') ?>"/>
    </div>

    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password"
             placeholder="••••••••" autocomplete="current-password" required/>
    </div>

    <button class="btn-primary" type="submit">Masuk →</button>
  </form>

  <div class="divider">atau</div>

  <div class="link-row">
    Belum punya akun? <a href="register.php">Daftar sekarang</a>
  </div>
</div>

<script>
function fillDemo(email) {
  document.getElementById('email').value    = email;
  document.getElementById('password').value = 'password';
}
</script>
</body>
</html>
