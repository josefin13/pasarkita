<?php
session_start();
require_once __DIR__ . '/../config/database.php';
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Username atau password salah.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Admin | PasarKita</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
body{min-height:100vh;background:linear-gradient(135deg,#f6f3ea,#e9edde);display:grid;place-items:center}
.login-card{width:min(100% - 32px,430px);background:#fff;border:1px solid #e7e0d0;border-radius:18px;padding:32px;box-shadow:0 18px 55px #344e4115}
.login-brand{font-size:1.8rem;font-weight:800;color:#344e41}
</style>
</head>
<body>
<div class="login-card">
  <div class="text-center mb-4">
    <div class="login-brand">Pasar<span style="color:#a66a4c">Kita</span></div>
    <p class="text-muted mb-0">Masuk ke panel administrator</p>
  </div>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required autofocus></div>
    <div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required></div>
    <button class="btn btn-admin w-100 py-2" type="submit">Login</button>
  </form>
  <div class="text-center mt-3"><a href="../index.php" class="small text-decoration-none">← Kembali ke website</a></div>
</div>
</body>
</html>
