<?php
// Paths
define('DB_PATH',      getenv('DB_PATH')      ?: '/data/dashboard.db');
define('UPLOAD_DIR',   getenv('UPLOAD_DIR')   ?: '/var/www/html/uploads/logos');
define('UPLOAD_URL',   '/uploads/logos');
define('APP_VERSION',  '1.0.0');

// Auth cookies / session
define('REMEMBER_COOKIE',   'ud_auth_token');
define('REMEMBER_LIFETIME', 365 * 24 * 3600);   // 1 year – permanent browser start page
define('SESSION_NAME',      'ud_session');

// Allowed logo MIME types
define('ALLOWED_MIME', ['image/png','image/jpeg','image/gif','image/svg+xml','image/webp','image/x-icon']);
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024); // 2 MB

// Make uploads dir if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}
