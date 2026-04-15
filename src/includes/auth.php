<?php
function startAppSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => REMEMBER_LIFETIME,
            'path'     => '/',
            'secure'   => false,   // set true if HTTPS only
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function login(string $username, string $password, bool $remember): bool {
    $stmt = getDb()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    $_SESSION['user_id']      = $user['id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['is_admin']     = (bool)$user['is_admin'];
    $_SESSION['display_name'] = $user['display_name'] ?: $user['username'];

    if ($remember) {
        $token   = bin2hex(random_bytes(32));
        $expires = time() + REMEMBER_LIFETIME;
        getDb()->prepare('INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?,?,?)')
            ->execute([$user['id'], $token, $expires]);
        setcookie(REMEMBER_COOKIE, $token, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    return true;
}

function logout(): void {
    startAppSession();
    if (isset($_COOKIE[REMEMBER_COOKIE])) {
        getDb()->prepare('DELETE FROM auth_tokens WHERE token = ?')
            ->execute([$_COOKIE[REMEMBER_COOKIE]]);
        setcookie(REMEMBER_COOKIE, '', time() - 3600, '/');
    }
    session_destroy();
}

function checkRememberToken(): bool {
    $token = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if (!$token) return false;

    $stmt = getDb()->prepare(
        'SELECT u.* FROM auth_tokens t JOIN users u ON u.id = t.user_id
         WHERE t.token = ? AND t.expires_at > ?'
    );
    $stmt->execute([$token, time()]);
    $user = $stmt->fetch();

    if (!$user) return false;

    $_SESSION['user_id']      = $user['id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['is_admin']     = (bool)$user['is_admin'];
    $_SESSION['display_name'] = $user['display_name'] ?: $user['username'];
    return true;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: /login');
        exit;
    }
}

function isAdmin(): bool {
    return !empty($_SESSION['is_admin']);
}

function currentUser(): array {
    return [
        'id'           => $_SESSION['user_id']      ?? 0,
        'username'     => $_SESSION['username']     ?? '',
        'display_name' => $_SESSION['display_name'] ?? '',
        'is_admin'     => $_SESSION['is_admin']     ?? false,
    ];
}

// Clean expired tokens occasionally
function pruneTokens(): void {
    static $done = false;
    if (!$done && mt_rand(1, 50) === 1) {
        getDb()->exec('DELETE FROM auth_tokens WHERE expires_at < ' . time());
        $done = true;
    }
}
