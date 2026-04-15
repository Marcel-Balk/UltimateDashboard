<?php
header('Content-Type: application/json');

// Bootstrap (when called via index.php these are already loaded, but safe to re-check)
if (!function_exists('getDb')) {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/auth.php';
    startAppSession();
    if (!isLoggedIn()) checkRememberToken();
}

if (!isLoggedIn()) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }

// CSRF check (skip for file uploads which send multipart)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (!str_starts_with($contentType, 'multipart/')) {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $csrf = $body['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
        http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Invalid CSRF']); exit;
    }
} else {
    $body = [];
    $csrf = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
        http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Invalid CSRF']); exit;
    }
}

$action = $_GET['action'] ?? $body['action'] ?? '';
$db     = getDb();
$user   = currentUser();

try {
    switch ($action) {

        // ── TILES ─────────────────────────────────────────────────────────────
        case 'add_tile':
            $gid  = (int)($body['group_id'] ?? 0);
            $name = trim($body['name'] ?? '');
            $url  = trim($body['url'] ?? '');
            if (!$gid || !$name || !$url) throw new \InvalidArgumentException('Missing required fields');

            $db->prepare(
                'INSERT INTO tiles (group_id,user_id,name,url,description,logo_path,color,open_new_tab,sort_order,is_global)
                 VALUES (?,?,?,?,?,?,?,?,
                   (SELECT COALESCE(MAX(sort_order),0)+1 FROM tiles WHERE group_id=?), ?)'
            )->execute([
                $gid, $user['id'], $name, $url,
                trim($body['description'] ?? ''),
                $body['logo_path'] ?? '',
                $body['color'] ?? '#00d4ff',
                isset($body['open_new_tab']) ? (int)(bool)$body['open_new_tab'] : 1,
                $gid,
                isAdmin() ? 1 : 0,
            ]);
            $id = $db->lastInsertId();
            $tile = $db->query("SELECT * FROM tiles WHERE id=$id")->fetch();
            echo json_encode(['ok'=>true, 'tile'=>$tile]);
            break;

        case 'edit_tile':
            $id   = (int)($body['id'] ?? 0);
            $name = trim($body['name'] ?? '');
            $url  = trim($body['url'] ?? '');
            if (!$id || !$name || !$url) throw new \InvalidArgumentException('Missing required fields');
            canModifyTile($id);

            $db->prepare(
                'UPDATE tiles SET name=?,url=?,description=?,logo_path=?,color=?,open_new_tab=?,group_id=? WHERE id=?'
            )->execute([
                $name, $url,
                trim($body['description'] ?? ''),
                $body['logo_path'] ?? '',
                $body['color'] ?? '#00d4ff',
                isset($body['open_new_tab']) ? (int)(bool)$body['open_new_tab'] : 1,
                (int)($body['group_id'] ?? 0) ?: null,
                $id,
            ]);
            $tile = $db->query("SELECT * FROM tiles WHERE id=$id")->fetch();
            echo json_encode(['ok'=>true, 'tile'=>$tile]);
            break;

        case 'delete_tile':
            $id = (int)($body['id'] ?? 0);
            if (!$id) throw new \InvalidArgumentException('Missing tile id');
            canModifyTile($id);
            // Delete logo file if exists
            $t = $db->query("SELECT logo_path FROM tiles WHERE id=$id")->fetch();
            if ($t && $t['logo_path'] && str_starts_with($t['logo_path'], UPLOAD_URL)) {
                $file = UPLOAD_DIR . '/' . basename($t['logo_path']);
                if (is_file($file)) @unlink($file);
            }
            $db->exec("DELETE FROM tiles WHERE id=$id");
            echo json_encode(['ok'=>true]);
            break;

        case 'reorder_tiles':
            $items = $body['items'] ?? [];
            $stmt  = $db->prepare('UPDATE tiles SET sort_order=? WHERE id=?');
            foreach ($items as $i => $tileId) {
                canModifyTile((int)$tileId, false);
                $stmt->execute([$i, (int)$tileId]);
            }
            echo json_encode(['ok'=>true]);
            break;

        // ── GROUPS ────────────────────────────────────────────────────────────
        case 'add_group':
            $name = trim($body['name'] ?? '');
            if (!$name) throw new \InvalidArgumentException('Group name required');
            $db->prepare(
                'INSERT INTO tile_groups (user_id,name,icon,sort_order,is_global) VALUES (?,?,?,
                  (SELECT COALESCE(MAX(sort_order),0)+1 FROM tile_groups),?)'
            )->execute([$user['id'], $name, $body['icon'] ?? 'grid', isAdmin() ? 1 : 0]);
            $gid   = $db->lastInsertId();
            $group = $db->query("SELECT * FROM tile_groups WHERE id=$gid")->fetch();
            echo json_encode(['ok'=>true, 'group'=>$group]);
            break;

        case 'edit_group':
            $gid  = (int)($body['id'] ?? 0);
            $name = trim($body['name'] ?? '');
            if (!$gid || !$name) throw new \InvalidArgumentException('Missing required fields');
            canModifyGroup($gid);
            $db->prepare('UPDATE tile_groups SET name=?,icon=? WHERE id=?')
               ->execute([$name, $body['icon'] ?? 'grid', $gid]);
            $group = $db->query("SELECT * FROM tile_groups WHERE id=$gid")->fetch();
            echo json_encode(['ok'=>true, 'group'=>$group]);
            break;

        case 'delete_group':
            $gid = (int)($body['id'] ?? 0);
            if (!$gid) throw new \InvalidArgumentException('Missing group id');
            canModifyGroup($gid);
            $db->exec("DELETE FROM tile_groups WHERE id=$gid"); // tiles cascade
            echo json_encode(['ok'=>true]);
            break;

        case 'reorder_groups':
            $items = $body['items'] ?? [];
            $stmt  = $db->prepare('UPDATE tile_groups SET sort_order=? WHERE id=?');
            foreach ($items as $i => $gid) {
                $stmt->execute([$i, (int)$gid]);
            }
            echo json_encode(['ok'=>true]);
            break;

        // ── LOGO UPLOAD ───────────────────────────────────────────────────────
        case 'upload_logo':
            if (empty($_FILES['logo'])) throw new \RuntimeException('No file uploaded');
            $file = $_FILES['logo'];
            if ($file['error'] !== UPLOAD_ERR_OK) throw new \RuntimeException('Upload error: ' . $file['error']);
            if ($file['size'] > MAX_UPLOAD_BYTES) throw new \RuntimeException('File too large (max 2 MB)');

            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, ALLOWED_MIME, true)) throw new \RuntimeException('Invalid file type');

            $ext  = match($mime) {
                'image/png'     => 'png',
                'image/jpeg'    => 'jpg',
                'image/gif'     => 'gif',
                'image/svg+xml' => 'svg',
                'image/webp'    => 'webp',
                default         => 'ico',
            };
            $name = bin2hex(random_bytes(12)) . '.' . $ext;
            $dest = UPLOAD_DIR . '/' . $name;
            if (!move_uploaded_file($file['tmp_name'], $dest)) throw new \RuntimeException('Failed to save file');

            echo json_encode(['ok'=>true, 'path'=> UPLOAD_URL . '/' . $name]);
            break;

        // ── ICON REPOSITORY ──────────────────────────────────────────────────
        case 'list_icons':
            $manifest = '/var/www/html/app-icons/icons.json';
            if (!file_exists($manifest)) {
                echo json_encode(['ok'=>true,'icons'=>[]]);
                break;
            }
            $icons = json_decode(file_get_contents($manifest), true) ?: [];
            echo json_encode(['ok'=>true,'icons'=>$icons]);
            break;

        case 'download_icons':
            if (!isAdmin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Admin only']); exit; }
            $baseUrl    = 'https://raw.githubusercontent.com/Marcel-Balk/UltimateDashboard/main/Icons-Repo/';
            $iconsDir   = '/var/www/html/app-icons/icons';
            $manifestPath = '/var/www/html/app-icons/icons.json';
            if (!is_dir($iconsDir)) mkdir($iconsDir, 0755, true);

            $raw = @file_get_contents($baseUrl . 'icons.json');
            if (!$raw) { echo json_encode(['ok'=>false,'error'=>'Could not fetch manifest from GitHub']); break; }
            file_put_contents($manifestPath, $raw);

            $newIcons = json_decode($raw, true) ?: [];
            $downloaded = 0;
            foreach ($newIcons as $icon) {
                $file = $iconsDir . '/' . basename($icon['file']);
                if (file_exists($file)) continue;
                $svg = @file_get_contents($baseUrl . 'icons/' . basename($icon['file']));
                if ($svg) { file_put_contents($file, $svg); $downloaded++; }
            }
            echo json_encode(['ok'=>true,'downloaded'=>$downloaded,'total'=>count($newIcons)]);
            break;

        // ── FAVICON FETCH ─────────────────────────────────────────────────────
        case 'get_favicon':
            $url = trim($_GET['url'] ?? $body['url'] ?? '');
            if (!$url) throw new \InvalidArgumentException('URL required');
            $host    = parse_url($url, PHP_URL_HOST);
            $scheme  = parse_url($url, PHP_URL_SCHEME) ?: 'https';
            $favicon = $scheme . '://' . $host . '/favicon.ico';
            // Try Google's favicon service as fallback
            $gfav = 'https://www.google.com/s2/favicons?domain=' . urlencode($host) . '&sz=64';
            echo json_encode(['ok'=>true, 'favicon'=>$favicon, 'google_favicon'=>$gfav, 'host'=>$host]);
            break;

        // ── SETTINGS ─────────────────────────────────────────────────────────
        case 'save_settings':
            if (!isAdmin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Admin only']); exit; }
            $appName = trim($body['app_name'] ?? '');
            $appLogo = trim($body['app_logo'] ?? '');
            if ($appName) setSetting('app_name', $appName);
            setSetting('app_logo', $appLogo);
            echo json_encode(['ok'=>true]);
            break;

        case 'upload_app_logo':
            if (!isAdmin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Admin only']); exit; }
            if (empty($_FILES['logo'])) throw new \RuntimeException('No file');
            $file = $_FILES['logo'];
            if ($file['error'] !== UPLOAD_ERR_OK) throw new \RuntimeException('Upload error');
            if ($file['size'] > MAX_UPLOAD_BYTES) throw new \RuntimeException('Too large');
            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, ALLOWED_MIME, true)) throw new \RuntimeException('Invalid type');
            $ext  = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png';
            $name = 'app_logo_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = UPLOAD_DIR . '/' . $name;
            if (!move_uploaded_file($file['tmp_name'], $dest)) throw new \RuntimeException('Save failed');
            $path = UPLOAD_URL . '/' . $name;
            setSetting('app_logo', $path);
            echo json_encode(['ok'=>true, 'path'=>$path]);
            break;

        // ── USERS ─────────────────────────────────────────────────────────────
        case 'add_user':
            if (!isAdmin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Admin only']); exit; }
            $uname = trim($body['username'] ?? '');
            $pass  = $body['password'] ?? '';
            $dname = trim($body['display_name'] ?? '');
            $admin = !empty($body['is_admin']);
            if (!$uname || strlen($pass) < 4) throw new \InvalidArgumentException('Username and password (min 4 chars) required');
            $db->prepare('INSERT INTO users (username,password,display_name,is_admin) VALUES (?,?,?,?)')
               ->execute([$uname, password_hash($pass, PASSWORD_DEFAULT), $dname ?: $uname, $admin ? 1 : 0]);
            echo json_encode(['ok'=>true, 'id'=>$db->lastInsertId()]);
            break;

        case 'delete_user':
            if (!isAdmin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Admin only']); exit; }
            $uid = (int)($body['id'] ?? 0);
            if ($uid === $user['id']) throw new \InvalidArgumentException("Cannot delete yourself");
            $db->exec("DELETE FROM users WHERE id=$uid");
            echo json_encode(['ok'=>true]);
            break;

        case 'change_password':
            $uid     = (int)($body['id'] ?? $user['id']);
            $newPass = $body['password'] ?? '';
            if (!isAdmin() && $uid !== $user['id']) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Forbidden']); exit; }
            if (strlen($newPass) < 4) throw new \InvalidArgumentException('Password must be at least 4 characters');
            $db->prepare('UPDATE users SET password=? WHERE id=?')
               ->execute([password_hash($newPass, PASSWORD_DEFAULT), $uid]);
            echo json_encode(['ok'=>true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok'=>false,'error'=>'Unknown action: '.$action]);
    }
} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

// ── Authorization helpers ─────────────────────────────────────────────────────
function canModifyTile(int $id, bool $throw = true): bool {
    $t = getDb()->query("SELECT user_id FROM tiles WHERE id=$id")->fetch();
    if (!$t) { if ($throw) throw new \RuntimeException("Tile not found"); return false; }
    if (!isAdmin() && (int)$t['user_id'] !== currentUser()['id']) {
        if ($throw) throw new \RuntimeException("Permission denied");
        return false;
    }
    return true;
}
function canModifyGroup(int $id, bool $throw = true): bool {
    $g = getDb()->query("SELECT user_id FROM tile_groups WHERE id=$id")->fetch();
    if (!$g) { if ($throw) throw new \RuntimeException("Group not found"); return false; }
    if (!isAdmin() && (int)$g['user_id'] !== currentUser()['id']) {
        if ($throw) throw new \RuntimeException("Permission denied");
        return false;
    }
    return true;
}
