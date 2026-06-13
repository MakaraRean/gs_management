#!/bin/bash
set -e

APP_DIR="$HOME/public_html/gs_management"
ZIP_FILE="$APP_DIR/deploy_latest.zip"

echo "==> Extracting archive..."
unzip -o "$ZIP_FILE" -d "$APP_DIR/"
rm "$ZIP_FILE"

echo "==> Running artisan commands..."
cd "$APP_DIR"
php artisan migrate --force
php artisan storage:link
php artisan optimize
chmod -R 755 storage bootstrap/cache

echo ""
echo "Deployment complete!"
