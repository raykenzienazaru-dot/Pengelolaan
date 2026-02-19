<?php
declare(strict_types=1);
require_once __DIR__ . "/functions.php";

$flash = flash_get();
$u = auth_user();
if ($u) {
  redirect_by_role($u["role"]);
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login • Pelaporan Air & Sanitasi</title>
  <link rel="stylesheet" href="/auth/login.css" />
</head>
<body>

<header class="topbar">
  <div class="container topbar__inner">
    <div class="brand">
      <div class="brand__logo" aria-hidden="true">💧</div>
      <div>
        <div class="brand__title">Pelaporan Air & Sanitasi</div>
        <div class="brand__sub">Masuk sebagai User atau Admin</div>
      </div>
    </div>
    <div class="pill">Secure Login • PHP Session</div>
  </div>
</header>

<main class="container">
  <section class="auth">
    <div class="auth__card">
      <div class="auth__head">
        <h1 class="h1">Masuk</h1>
        <p class="p">Pilih peran, lalu login menggunakan email & kata sandi.</p>
      </div>

      <div class="role-tabs" role="tablist" aria-label="Pilih Peran">
        <button class="role-tab is-active" type="button" data-role="user" role="tab" aria-selected="true">User</button>
        <button class="role-tab" type="button" data-role="admin" role="tab" aria-selected="false">Admin</button>
      </div>

      <?php if ($flash): ?>
        <div class="callout <?= htmlspecialchars($flash["type"]) ?>">
          <?= htmlspecialchars($flash["msg"]) ?>
        </div>
      <?php endif; ?>

      <form class="form" method="post" action="/auth/process_login.php" autocomplete="on">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
        <input type="hidden" name="role" id="role" value="user" />

        <div class="field">
          <label for="email">Email</label>
          <div class="input">
            <span class="input__icon" aria-hidden="true">✉️</span>
            <input id="email" name="email" type="email" placeholder="nama@email.com" required />
          </div>
        </div>

        <div class="field">
          <label for="password">Kata Sandi</label>
          <div class="input">
            <span class="input__icon" aria-hidden="true">🔒</span>
            <input id="password" name="password" type="password" placeholder="••••••••" required />
            <button class="input__btn" type="button" id="togglePwd" aria-label="Tampilkan/sembunyikan password">Lihat</button>
          </div>
          <div class="hint">Tips: gunakan minimal 8 karakter, kombinasi huruf besar & angka.</div>
        </div>

        <div class="row row--between">
          <label class="check">
            <input type="checkbox" name="remember" value="1" />
            <span>Ingat saya (opsional)</span>
          </label>
          <a class="link" href="#" onclick="alert('Fitur reset password bisa ditambahkan.'); return false;">Lupa password?</a>
        </div>

        <button class="btn" type="submit">Masuk</button>

        <div class="divider"></div>

        <div class="small">
          Dengan login, kamu menyetujui kebijakan penggunaan platform.
        </div>
      </form>
    </div>

    <aside class="auth__side">
      <div class="side-card">
        <div class="side-card__title">Akses Berdasarkan Peran</div>
        <ul class="side-list">
          <li><b>User</b>: buat laporan (foto + GPS), lihat status di peta.</li>
          <li><b>Admin</b>: verifikasi, update status, analisis wilayah.</li>
        </ul>
      </div>

      <div class="side-card">
        <div class="side-card__title">Keamanan</div>
        <div class="small">
          Password disimpan sebagai <b>hash</b> (bukan plaintext). Login memakai session PHP + CSRF token.
        </div>
      </div>
    </aside>
  </section>
</main>

<script src="/auth/login.js"></script>
</body>
</html>
