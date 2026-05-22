#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RELEASE_DIR="$ROOT_DIR/release"
STAGING_DIR="$RELEASE_DIR/fuhrpark-app-ready"
ARCHIVE="$RELEASE_DIR/fuhrpark-app-ready.tar.gz"

rm -rf "$STAGING_DIR" "$ARCHIVE" "$RELEASE_DIR/SHA256SUMS"
mkdir -p "$STAGING_DIR"

tar \
  --exclude='.git' \
  --exclude='.env' \
  --exclude='node_modules' \
  --exclude='release' \
  --exclude='storage/framework/cache/data/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='storage/logs/*' \
  --exclude='vendor' \
  -cf - -C "$ROOT_DIR" . | tar -xf - -C "$STAGING_DIR"

(
  cd "$STAGING_DIR"
  composer install --no-dev --optimize-autoloader --no-interaction
  npm install --no-audit --no-fund
  npm run build
  rm -rf node_modules
)

cat > "$STAGING_DIR/DEPLOYMENT.txt" <<'EOF'
Fuhrpark Management - ready-to-upload Laravel bundle

This folder already contains:
- Laravel application source
- production Composer dependencies in vendor/
- built frontend assets in public/build/

The webserver does NOT need npm.
The webserver does NOT need Composer for dependency installation.

Required on the webserver:
- PHP 8.3+
- PHP extensions: pdo_mysql, mbstring, fileinfo, gd, zip, xml, curl, openssl
- MySQL or MariaDB database
- Apache or Nginx with document root pointing to this folder's public/ directory

FTP/browser deployment:
1. Upload/extract this folder to the server, for example:
   /var/www/fuhrpark-app

2. Configure the webserver document root to:
   /var/www/fuhrpark-app/public

3. Open the browser installer:
   https://your-domain.example/install.php

4. Enter:
   - APP URL
   - MySQL/MariaDB database credentials
   - first admin user
   - optional fleet manager user
   - local or SFTP file storage

5. The installer will:
   - create .env
   - test the database connection
   - run migrations
   - create the admin user
   - create default vehicle categories
   - create the storage link if possible
   - cache Laravel config/routes/views
   - create storage/app/install.lock

6. For security, delete public/install.php by FTP after successful setup.

If the installer reports that folders are not writable, set these folders writable
through the hosting control panel or FTP permissions:

- storage
- storage/app
- storage/framework
- bootstrap/cache

The admin login is the email/password you enter in the installer.
EOF

tar -czf "$ARCHIVE" -C "$RELEASE_DIR" fuhrpark-app-ready
(
  cd "$RELEASE_DIR"
  sha256sum "$(basename "$ARCHIVE")" > SHA256SUMS
)

echo "Created $ARCHIVE"
echo "Created $RELEASE_DIR/SHA256SUMS"
