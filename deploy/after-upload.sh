#!/bin/bash
set -e

APP_DIR="$HOME/public_html/gs_management"
ZIP_FILE="$APP_DIR/deploy_latest.zip"

# --- Select a PHP >= 8.2 binary ---
# The default CLI `php` on this cPanel host is 8.1, but the app requires >= 8.2.
# Prefer an explicit EasyApache / CloudLinux alt-php binary.
PHP_BIN=""
for candidate in \
    /opt/cpanel/ea-php82/root/usr/bin/php \
    /opt/cpanel/ea-php83/root/usr/bin/php \
    /opt/cpanel/ea-php84/root/usr/bin/php \
    /opt/alt/php82/usr/bin/php \
    /opt/alt/php83/usr/bin/php \
    /opt/alt/php84/usr/bin/php; do
    if [ -x "$candidate" ]; then
        PHP_BIN="$candidate"
        break
    fi
done

if [ -z "$PHP_BIN" ]; then
    PHP_BIN="$(command -v php)"
fi

# Put the chosen PHP first on PATH so composer's shebang resolves to it too.
export PATH="$(dirname "$PHP_BIN"):$PATH"

echo "==> Using PHP: $PHP_BIN"
"$PHP_BIN" -v | head -1

echo "==> Extracting archive..."
unzip -o "$ZIP_FILE" -d "$APP_DIR/"
rm "$ZIP_FILE"

echo "==> Removing Vite dev-server hot file (forces production build assets)..."
rm -f "$APP_DIR/public/hot"

echo "==> Running artisan commands..."
cd "$APP_DIR"
"$PHP_BIN" "$(command -v composer)" install --no-dev --optimize-autoloader
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan storage:link
"$PHP_BIN" artisan optimize
chmod -R 755 storage bootstrap/cache

echo ""
echo "Deployment complete!"
