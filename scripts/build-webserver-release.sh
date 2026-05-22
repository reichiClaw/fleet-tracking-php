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

Deployment:
1. Upload/extract this folder to the server, for example:
   /var/www/fuhrpark-app

2. Create the production environment file:
   cp .env.example .env

3. Edit .env:
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.example
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=fuhrpark
   DB_USERNAME=fuhrpark_user
   DB_PASSWORD=your_secure_password

4. Run the Laravel setup commands:
   php artisan key:generate
   php artisan migrate --seed --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache

5. Set writable permissions:
   chown -R www-data:www-data storage bootstrap/cache
   chmod -R 775 storage bootstrap/cache

6. Configure the webserver document root to:
   /var/www/fuhrpark-app/public

Seed login:
- admin@example.com / password
- verwaltung@example.com / password

Change both passwords immediately after first login.
EOF

tar -czf "$ARCHIVE" -C "$RELEASE_DIR" fuhrpark-app-ready
(
  cd "$RELEASE_DIR"
  sha256sum "$(basename "$ARCHIVE")" > SHA256SUMS
)

echo "Created $ARCHIVE"
echo "Created $RELEASE_DIR/SHA256SUMS"
