<?php
function getDb(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
    initSchema($pdo);
    return $pdo;
}

function initSchema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            username     TEXT UNIQUE NOT NULL,
            password     TEXT NOT NULL,
            display_name TEXT,
            is_admin     INTEGER NOT NULL DEFAULT 0,
            created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS auth_tokens (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER NOT NULL,
            token      TEXT UNIQUE NOT NULL,
            expires_at INTEGER NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS tile_groups (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER,
            name       TEXT NOT NULL,
            icon       TEXT NOT NULL DEFAULT 'grid',
            sort_order INTEGER NOT NULL DEFAULT 0,
            is_global  INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS tiles (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            group_id     INTEGER NOT NULL,
            user_id      INTEGER,
            name         TEXT NOT NULL,
            url          TEXT NOT NULL,
            description  TEXT,
            logo_path    TEXT,
            color        TEXT NOT NULL DEFAULT '#00d4ff',
            open_new_tab INTEGER NOT NULL DEFAULT 1,
            sort_order   INTEGER NOT NULL DEFAULT 0,
            is_global    INTEGER NOT NULL DEFAULT 0,
            created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES tile_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)  REFERENCES users(id)        ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS settings (
            key   TEXT PRIMARY KEY,
            value TEXT
        );
    ");

    // Seed default admin if no users exist
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
        $hash = password_hash('admin', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password, display_name, is_admin) VALUES (?, ?, ?, 1)")
            ->execute(['admin', $hash, 'Administrator']);

        // Default settings
        $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)")
            ->execute(['app_name', 'Ultimate Dashboard']);
        $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)")
            ->execute(['app_logo', '']);

        // Demo groups & tiles
        $pdo->exec("INSERT INTO tile_groups (user_id, name, icon, sort_order, is_global) VALUES (1,'Monitoring','activity',0,1)");
        $gid = $pdo->lastInsertId();
        $tiles = [
            [$gid, 1, 'Grafana',    'https://grafana.com',    'Metrics & dashboards',     '#F46800', 1],
            [$gid, 1, 'Zabbix',     'https://zabbix.com',     'Infrastructure monitoring', '#D40000', 1],
            [$gid, 1, 'Prometheus', 'https://prometheus.io',  'Time-series metrics',       '#E6522C', 1],
        ];
        $pdo->exec("INSERT INTO tile_groups (user_id, name, icon, sort_order, is_global) VALUES (1,'Infrastructure','server',1,1)");
        $gid2 = $pdo->lastInsertId();
        $tiles2 = [
            [$gid2, 1, 'Proxmox',   'https://proxmox.com',   'VM management',      '#E57000', 1],
            [$gid2, 1, 'Portainer', 'https://portainer.io',  'Docker management',  '#13BEF9', 1],
            [$gid2, 1, 'Traefik',   'https://traefik.io',    'Reverse proxy',      '#24A1C1', 1],
        ];
        $stmt = $pdo->prepare("INSERT INTO tiles (group_id,user_id,name,url,description,color,sort_order,is_global) VALUES (?,?,?,?,?,?,?,?)");
        foreach (array_merge($tiles, $tiles2) as $i => $t) {
            $stmt->execute($t);
        }
    }
}

function getSetting(string $key, string $default = ''): string {
    try {
        $stmt = getDb()->prepare('SELECT value FROM settings WHERE key=?');
        $stmt->execute([$key]);
        $v = $stmt->fetchColumn();
        return $v !== false ? $v : $default;
    } catch (\Throwable $e) { return $default; }
}

function setSetting(string $key, string $value): void {
    getDb()->prepare('INSERT OR REPLACE INTO settings (key,value) VALUES (?,?)')->execute([$key, $value]);
}
