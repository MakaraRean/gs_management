#!/bin/bash
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="$SCRIPT_DIR/.env.deploy"

if [ ! -f "$ENV_FILE" ]; then
    echo "Error: $ENV_FILE not found."
    echo "Copy deploy/.env.deploy.example to deploy/.env.deploy and fill in your cPanel credentials."
    exit 1
fi

while IFS='=' read -r key value; do
    [[ "$key" =~ ^#.*$ || -z "$key" ]] && continue
    export "$key"="$value"
done < "$ENV_FILE"

WEBDAV_BASE="https://${WEBDAV_HOST}:${WEBDAV_PORT}"
ZIP_NAME="deploy_latest.zip"
LOCAL_ZIP="/tmp/$ZIP_NAME"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "==> Building frontend assets..."
cd "$PROJECT_ROOT"
npm run build

echo "==> Creating deployment archive..."
zip -r "$LOCAL_ZIP" . \
    --exclude "*.git*" \
    --exclude "node_modules/*" \
    --exclude "deploy/*" \
    --exclude "tests/*" \
    --exclude ".env*" \
    --exclude "*.log" \
    --exclude ".DS_Store" \
    --exclude "*.sqlite" \
    --exclude "*.sqlite-journal" \
    --exclude "storage/logs/*" \
    --exclude "storage/framework/cache/*" \
    --exclude "storage/framework/sessions/*" \
    --exclude "storage/framework/views/*" \
    --exclude "bootstrap/cache/*"

echo "==> Uploading archive to server (this may take a while)..."
curl -T "$LOCAL_ZIP" \
    --user "${WEBDAV_USER}:${WEBDAV_PASS}" \
    "${WEBDAV_BASE}/public_html/gs_management/${ZIP_NAME}" \
    --progress-bar \
    --ssl-no-revoke

echo ""
echo "==> Uploading after-upload script..."
curl -T "$SCRIPT_DIR/after-upload.sh" \
    --user "${WEBDAV_USER}:${WEBDAV_PASS}" \
    "${WEBDAV_BASE}/public_html/gs_management/after-upload.sh" \
    --progress-bar \
    --ssl-no-revoke

echo ""
echo "==> Cleaning up local archive..."
rm "$LOCAL_ZIP"

echo ""
echo "Upload complete! Run this single command in cPanel Terminal:"
echo ""
echo "  bash ~/public_html/gs_management/after-upload.sh"
echo ""
