<?php
declare(strict_types=1);
require_once __DIR__ . "/functions.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: /auth/login.php");
  exit;
}

csrf_verify($_POST["csrf"] ?? null);

$email = trim((string)($_POST["email"] ?? ""));
$pass  = (string)($_POST["password"] ?? "");
$role  = (string)($_POST["role"] ?? "user");
$role  = in_array($role, ["user","admin"], true) ? $role : "user";

if ($email === "" || $pass === "") {
  flash_set("Email dan kata sandi wajib diisi.");
  header("Location: /auth/login.php");
  exit;
}

try {
  $stmt = db()->prepare("SELECT id, name, email, password_hash, role, is_active FROM users WHERE email = ? LIMIT 1");
  $stmt->execute([$email]);
  $u = $stmt->fetch();

  if (!$u || (int)$u["is_active"] !== 1) {
    flash_set("Akun tidak ditemukan atau nonaktif.");
    header("Location: /auth/login.php");
    exit;
  }

  if (!password_verify($pass, $u["password_hash"])) {
    flash_set("Email atau kata sandi salah.");
    header("Location: /auth/login.php");
    exit;
  }

  // Penting: role yang dipilih harus match role di database
  if ($u["role"] !== $role) {
    flash_set("Peran tidak sesuai. Pilih tab role yang benar (User/Admin).");
    header("Location: /auth/login.php");
    exit;
  }

  // Login sukses
  session_regenerate_id(true);
  $_SESSION["auth"] = [
    "id" => (int)$u["id"],
    "name" => $u["name"],
    "email" => $u["email"],
    "role" => $u["role"],
  ];

  redirect_by_role($u["role"]);

} catch (Throwable $e) {
  flash_set("Server error. Coba lagi.");
  header("Location: /auth/login.php");
  exit;
}
