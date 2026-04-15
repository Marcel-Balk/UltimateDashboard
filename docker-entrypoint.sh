#!/bin/sh
# Fix permissions on volume-mounted directories so www-data can write
chown -R www-data:www-data /data /var/www/html/uploads 2>/dev/null || true
chmod -R 755 /data /var/www/html/uploads 2>/dev/null || true
# Keep acme.json secure if it ended up in /data
chmod 600 /data/acme.json 2>/dev/null || true
exec "$@"
