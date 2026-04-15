<?php
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } elseif (!login($username, $password, $remember)) {
        $error = 'Invalid username or password.';
    } else {
        header('Location: /');
        exit;
    }
}

$appName = getSetting('app_name', 'Ultimate Dashboard');
$appLogo = getSetting('app_logo', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login – <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="/logo.svg">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="login-page">

<canvas id="bg-canvas" aria-hidden="true"></canvas>

<div class="login-wrap">
  <div class="login-card">

    <div class="login-logo">
      <?php if ($appLogo): ?>
        <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo" class="login-logo-img">
      <?php else: ?>
        <div class="login-logo-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
          </svg>
        </div>
      <?php endif; ?>
      <h1 class="login-title"><?= htmlspecialchars($appName) ?></h1>
      <p class="login-subtitle">Your personal browser start page</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/login" autocomplete="on" id="login-form">
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <div class="input-wrap">
          <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
          <input type="text" id="username" name="username" class="form-control" placeholder="Enter username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus autocomplete="username">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <input type="password" id="password" name="password" class="form-control" placeholder="Enter password"
                 required autocomplete="current-password">
          <button type="button" class="pw-toggle" aria-label="Toggle password" onclick="togglePw()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="form-check">
        <input type="checkbox" id="remember" name="remember" class="check-input" checked>
        <label for="remember" class="check-label">Remember me permanently</label>
      </div>

      <button type="submit" class="btn btn-primary btn-full">
        <span>Sign In</span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
        </svg>
      </button>
    </form>

    <p class="login-hint">Default credentials: <code>admin</code> / <code>admin</code></p>
  </div>
</div>

<script src="/assets/js/main.js"></script>
<script>
function togglePw() {
  var i = document.getElementById('password');
  i.type = i.type === 'password' ? 'text' : 'password';
}
initParticles('bg-canvas');
</script>
</body>
</html>
