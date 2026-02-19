<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

function csrf_token(): string {
  if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
  }
  return $_SESSION["csrf"];
}

function csrf_verify(?string $token): void {
  if (!$token || empty($_SESSION["csrf"]) || !hash_equals($_SESSION["csrf"], $token)) {
    http_response_code(403);
    exit("CSRF token tidak valid.");
  }
}

function auth_user(): ?array {
  return $_SESSION["auth"] ?? null;
}

function require_login(?string $role = null): void {
  $u = auth_user();
  if (!$u) {
    header("Location: /auth/login.php");
    exit;
  }
  if ($role && ($u["role"] ?? "") !== $role) {
    http_response_code(403);
    exit("Akses ditolak.");
  }
}

function redirect_by_role(string $role): void {
  if ($role === "admin") {
    header("Location: /admin/index.php");
  } else {
    header("Location: /user/index.php");
  }
  exit;
}

function flash_set(string $msg, string $type = "error"): void {
  $_SESSION["flash"] = ["msg" => $msg, "type" => $type];
}

function flash_get(): ?array {
  $f = $_SESSION["flash"] ?? null;
  unset($_SESSION["flash"]);
  return $f;
}
