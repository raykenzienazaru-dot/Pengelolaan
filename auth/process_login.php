<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php'); exit;
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

if (!$email || !$password) {
    header('Location: login.php?err=Isi+email+dan+password'); exit;
}

try {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        header('Location: login.php?err=Email+atau+password+salah'); exit;
    }

    // Set session
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_nama'] = $user['nama'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email']= $user['email'];

    // Redirect sesuai role
    if ($user['role'] === 'admin') {
        header('Location: ../admin.php'); exit;
    } else {
        header('Location: ../user.php'); exit;
    }

} catch (Exception $e) {
    header('Location: login.php?err=' . urlencode('Kesalahan server: ' . $e->getMessage())); exit;
}
